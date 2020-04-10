<?php

namespace PHPattern\Database;

use PHPattern\Database;
use PHPattern\DB;
use PDOException;

class Connection
{
	private static $database = NULL;
	private static $attempts = 0;
	private static $limit 	 = 3;

	public static function get($env='')
	{
		if(self::$database === NULL || self::$database->connection() === NULL || self::$database->env() != $env)
		{
			self::resetAttempts();
			self::$database = new Database($env);
		}
		return self::$database;
	}

	public static function attempts()
	{
		self::$attempts++;
		return self::$attempts;
	}

	public static function resetAttempts()
	{
		self::$attempts = 0;
	}

	public static function isLockWait(PDOException $e)
	{
		$isRetry = ($e->getMessage() == DB::LOCK_WAIT_TIMEOUT && self::attempts() <= self::$limit)? true: false;
		if($isRetry)
		{
			sleep(3);
			self::$database = new Database(self::$database->env(), self::$database->credentials());
		}
		return $isRetry;
	}

	public static function close()
	{
		if(self::$database)
		{
			self::$database->close();
		}
		self::$database = NULL;
	}
}