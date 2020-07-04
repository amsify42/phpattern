<?php

namespace PHPattern;
use PHPattern\Database\Connection;
use PHPattern\Database\Error;
use PDO;
use PDOException;

class DB
{
	const NOW = 'NOW()';
	const LOCK_WAIT_TIMEOUT = "SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction";
	const SQL_AND = ' AND ';
    const SQL_OR  = ' OR ';

	public static function query($query, $type='', $env='', $isObject=false, $class=NULL)
	{
		try 
		{
			$connection = Connection::get($env);
			$statement 	= $connection->prepare($query);
			$statement->execute();
			$result 	= '';
			if($type == 'insert')
			{
				$result = (int)$connection->lastInsertId();
			}
			else if($type == 'update' || $type == 'delete')
			{
				$result = (int)$statement->rowCount();
			}
			else
			{
				if($isObject)
				{
					if($class)
					{
						$result = $statement->fetchAll(PDO::FETCH_CLASS, $class);
					}
					else
					{
						$result = $statement->fetchAll(PDO::FETCH_OBJ);
					}
				}
				else
				{
					$result = $statement->fetchAll(PDO::FETCH_ASSOC);
				}
			}
			$statement->closeCursor();
			$statement = NULL;
			Connection::resetAttempts();
			return $result;
		}
		catch(PDOException $e)
		{
			/**
			 * Sleeping and attempting multiple times if - Lock wait timeout exceeded
			 */
			if(Connection::isLockWait($e))
			{
				return self::query($query, $type);
			}
			/**
			 * Logging Exception and stopping the script
			 */
			Error::logException($e, $query);
		}
	}
}