<?php
require_once('config/db_credentials.php');

function dbConnection()
{
	$connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

	if(mysqli_connect_errno()) {
		$msg = "Database connection failed: ";
		$msg .= mysqli_connect_error();
		$msg .= " : " . mysqli_connect_errno();
		exit($msg);
	}
	return $connection;
}
