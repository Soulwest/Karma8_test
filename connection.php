<?php
function dbConnection()
{
	if (! getenv("DB_SERVER"))
	{
		// try to load from .env manually
		$env = parse_ini_file('.env');
		foreach ($env as $varName => $varVal)
		{
			putenv("$varName=$varVal");
		}
	}

	try
	{
		$connection = mysqli_connect(getenv("DB_SERVER"), getenv("DB_USER"), getenv("DB_PASS"), getenv("DB_NAME"));
	}
	catch (Exception)
	{
		$msg = "Database connection failed: ";
		if (mysqli_connect_errno())
		{
			$msg .= mysqli_connect_error();
			$msg .= " : ".mysqli_connect_errno();
		}
		exit($msg);
	}

	return $connection;
}
