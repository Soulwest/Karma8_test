<?php

// DB Connect

// fork

// request users WHERE validts < now() - INTERVAL 3 DAY AND confirmed = TRUE
// JOIN emails WHERE checked=0

// request users WHERE validts < now() - INTERVAL 3 DAY AND confirmed = TRUE
// JOIN emails WHERE valid=1 and checked=1

function check_email( string $email ): bool {
	sleep(random_int(1,60));
	return str_starts_with($email, 'bad');
}

function send_email(string $email, string $from, string $to, string $subj, string $body):bool {
	sleep(random_int(1,10));
	file_put_contents(__DIR__ . '/logs/mails.txt', "$email $from -> $to\n$subj\n$body\n====================\n\n\n", FILE_APPEND);

	return TRUE;
}

