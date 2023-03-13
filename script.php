<?php
/**
 * Add to minutely cron
 */
set_time_limit(5 * 60); // Max 5 min

const EXPIRES_IN = 3; //days
const SENDER_EMAIL = 'no-reply@karma8.io';
const USER_LIMIT = 100; // 150 users * 24h * 60m = 216k (~20% from 1m base)
const MAX_PROCESS = 15;

require_once 'connection.php';
// Connect to DB in parent process
$dbLink = dbConnection();

$q = "
	SELECT u.*, e.checked, e.valid FROM users u
    LEFT JOIN emails e
        on u.email = e.email
	WHERE validts < ( now() + INTERVAL ? DAY)
	    AND validts > now() 
		AND u.confirmed = true
		AND (last_notification IS NULL OR last_notification < NOW() - INTERVAL ? DAY)
	LIMIT ?
";

$validatedEmails = [];
$userLimit = USER_LIMIT;
$expiresIN = EXPIRES_IN; // TODO remove var

// Prepare query for execution (procedure style ...)
$stmt = mysqli_prepare($dbLink, $q);
mysqli_stmt_bind_param($stmt, "iii", $expiresIN, $expiresIN, $userLimit);
mysqli_stmt_execute($stmt);


// First mark user as a received notification, to avoid duplicates
$result = mysqli_stmt_get_result($stmt);
$userUpdated = [];
while ($userId = mysqli_fetch_column($result, 0))
{
	$userUpdated[] = $userId;
}
if ( ! empty($userUpdated))
{
	$qMarks = trim(str_repeat('?,', count($userUpdated)), ',');
	$q = "UPDATE users SET last_notification = NOW() WHERE id IN ($qMarks)";
	$stmt = mysqli_prepare($dbLink, $q);
	mysqli_stmt_execute($stmt, $userUpdated);
}

mysqli_data_seek($result, 0);
$shmId = shmop_open(ftok(__FILE__, 'a'), "c", 0644, MAX_PROCESS);

const emailStorageSize = (320 + 3) * USER_LIMIT; // 320 max email len + ",1|"
$shmEId = shmop_open(ftok(__FILE__, 'v'), "c", 0644, emailStorageSize);

if ( ! $shmId || !$shmEId)
{
	// Error
	exit(1);
}
// Clear data by in shared memory
shmop_write($shmId, str_repeat(0, MAX_PROCESS), 0);

$cntStartedProcess = 0;
$runningProcess = 0;
while (TRUE)
{
	if ($runningProcess < MAX_PROCESS)
	{
		$user = mysqli_fetch_assoc($result);
		if ( ! $user)
		{
			echo "Stack is over\n";
			break;
		}
		$cntStartedProcess++;

		$pid = pcntl_fork();

		if ($pid === -1) // Error: could not fork
		{
			exit(1);
		}

		if ($pid === 0)
		{
			$shmData = shmop_read($shmId, 0, MAX_PROCESS);
			$idleProcess = strpos($shmData, 0);
			#printf("Start child process: %s\n", $idleProcess);

			// mark process as active
			shmop_write($shmId, 1, $idleProcess);

			// Main Load
			try
			{
				handle_email_logic($user);
				// read emails from shared memory and save new one to the end of string
				$emailsDB = shmop_read($shmEId, 0, emailStorageSize);
				shmop_write(
					$shmEId,
					$user['email'].','.($user['valid'] ? 1 : 0).'|',
					strlen(trim($emailsDB))
				);
			}
			catch (Exception $e)
			{
			} // No need to catch Exceptions, we need clean memory first

			// mark process as idle
			shmop_write($shmId, 0, $idleProcess);
			exit(0);
		}
		usleep(10000);// microsleep for forking and shmop
	}
	else
	{
		usleep(5000 * $runningProcess); //debouncing
	}

	// If we've reached the limit, wait for running processes to finish
	$shmData = shmop_read($shmId, 0, MAX_PROCESS);
	$runningProcess = substr_count($shmData, 1);
	#printf("Processes (%s busy / %s started / %s total): %s\n", $runningProcess, $cntStartedProcess, count($userUpdated), $shm_data);
}

mysqli_stmt_close($stmt);
while (pcntl_waitpid(0, $status) !== -1)
{
	usleep(100000);
} // Wait until all child process exited


// Save checked emails into database.
// TODO: pcntl_signal(SIGINT, "signal_handler"), BUT we already have it in shared memory
// First of all take everything from a storage
$emailsDB = shmop_read($shmEId, 0, emailStorageSize);
if ( ! empty(trim($emailsDB)))
{
	// reset email storage
	shmop_write($shmEId, str_repeat("\0", emailStorageSize), 0); // instead shmop_delete($shmEId);

	try
	{
		mysqli_ping($dbLink);
	}
	catch (Exception)
	{
		$dbLink = dbConnection();
	}

	// Save emails
	$q = 'INSERT INTO emails (`email`, `valid`, `checked`) VALUES';

	$emailsDB = explode('|', trim($emailsDB, "|\0"));
	foreach ($emailsDB as $emailValidateStr)
	{
		[$email, $valid] = explode(',', $emailValidateStr, 2);
		if (empty($email))
		{
			continue;
		}
		$q .= '(\''.mysqli_real_escape_string($dbLink, $email).'\','.$valid.',1),';
	}
	$q = trim($q, ', ');

	$q .= 'ON DUPLICATE KEY UPDATE checked=VALUES(checked), `valid`=VALUES(`valid`)';
	mysqli_query($dbLink, $q);
	echo "Validate ".count($emailsDB)." emails\n";
}

shmop_delete($shmId);


function handle_email_logic(array &$user) : void
{
// Child process
	if ( ! $user['checked'])
	{
		// Checking email
		$user['valid'] = check_email($user['email']);
		$user['checked'] = TRUE;
	}

	if ($user['checked'] && $user['valid'])
	{
		send_email(
			$user['email'],
			SENDER_EMAIL,
			$user['email'],
			'Your subscription is expiring',
			ucfirst($user['username']).', your subscription is expiring soon' // /*mb_ucfirst*/
		);
	}
}

function check_email(string $email): bool
{
	echo "Checking email $email...\n";
	sleep(random_int(1, 15));
	echo "Email $email checked!\n";

	return ! str_starts_with($email, 'bad');
}

function send_email(string $email, string $from, string $to, string $subj, string $body): bool
{
	echo "Sending email $email...\n";
	sleep(random_int(1, 10));
	echo "Email $email sent!\n";
	file_put_contents(__DIR__.'/logs/mails.txt', "$email $from -> $to\n$subj\n$body\n====================\n\n\n", FILE_APPEND);

	return TRUE;
}
