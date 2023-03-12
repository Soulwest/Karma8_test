<?php
// Add to every minute cron

require_once 'connection.php';
// Connect to DB in parent
$dbLink = dbConnection();

$expiredIn = 3; //days
$send_from_email = 'our@email.com';

$query = "
	SELECT u.*, e.checked, e.valid FROM users u
    LEFT JOIN emails e
        on u.email = e.email
	WHERE validts < ( now() + INTERVAL ? DAY)
		AND u.confirmed = true
	LIMIT 50, 20
";
// Prepare query for execution (procedure style ...)
$stmt = mysqli_prepare($dbLink, $query);
mysqli_stmt_bind_param($stmt, "d", $expiredIn);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
while ($user = mysqli_fetch_assoc($result)) {
	if (!$user['checked'])
	{
		$user['valid'] = FALSE;

		// Checking email
		$emailValid = check_email($user['email']);
		if ($emailValid)
		{
			$user['valid'] = TRUE;
			//TODO: saveto database (valid and checked)
		}
		$user['checked'] = TRUE;
	}

	if ($user['checked'] && $user['valid'])
	{
		send_email(
			$user['email'],
			$send_from_email,
			$user['email'],
			'Your subscription is expiring',
			ucfirst($user['username']).', your subscription is expiring soon' // /*mb_ucfirst*/
		);
	}
}
/* close statement */
mysqli_stmt_close($stmt);

function check_email( string $email ): bool {
	echo "Checking email...\n";
	sleep(random_int(1,60));
	echo "Email checked!\n";
	return str_starts_with($email, 'bad');
}

function send_email(string $email, string $from, string $to, string $subj, string $body):bool {
	echo "Sending email...\n";
	sleep(random_int(1,10));
	echo "Email sent!\n";
	file_put_contents(__DIR__ . '/logs/mails.txt', "$email $from -> $to\n$subj\n$body\n====================\n\n\n", FILE_APPEND);

	return TRUE;
}

