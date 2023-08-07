<?php

namespace PHPattern;
use PHPattern\Database\Connection;
use PHPattern\Database\Error;
use PHPattern\Database\Raw;
use PDO;
use PDOException;

class DB
{
	const NOW = 'NOW()';
	const LOCK_WAIT_TIMEOUT = "SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction";
	const SQL_AND = ' AND ';
    const SQL_OR  = ' OR ';

    private static $bindValues = [];

	private static $whereInKeys = [];

	private static $whereInStrs = [];

	private static function resetBindValues()
	{
		self::$bindValues 	= [];
		self::$whereInKeys 	= [];
		self::$whereInStrs 	= [];
	}

	private static function replaceOccurrences($string, $token, $value)
    {
        $strArr = explode($token, $string);
        $newStr = '';
        for($i=0; $i<count($strArr); ++$i)
        {
            if($i == count($strArr) - 1)
            {
                $newStr .= $strArr[$i];
            }
            else
            {
                $newStr .= $strArr[$i].(array_key_exists($i, $value)? (is_string($value[$i])? "'{$value[$i]}'": (($value[$i] === NULL)? 'NULL': $value[$i])): $token);
            }
        }
        return $newStr;
    }

    private static function setBindValues($bindValues=[])
	{
		$finalBindValues  = [];

		$pos = 0;
		foreach($bindValues as $bindKey => $bindValue)
		{
			$bKey = $bindKey;
			if(is_numeric($bindKey))
			{
				$bKey = $pos;
				if(!is_array($bindValue))
				{
					$pos++;
				}
			}
			if(is_array($bindValue))
			{
				if(sizeof($bindValue)> 0)
				{
					$whereInKey = ':where_in_'.$bindKey;
					$whereInStr = '';
					foreach($bindValue as $bvk => $bValue)
					{
						$cKey = $bindKey.'_'.($bvk + 1);
						if(is_numeric($bKey))
						{
							$whereInStr .= ($whereInStr)? ',?': '?';
							$cKey = $pos;
							$pos++;
						}
						else
						{
							$whereInStr .= ($whereInStr)? ',:'.$cKey: ':'.$cKey;
						}
						self::setBindValue($finalBindValues, $cKey, $bValue);
					}

					if($whereInStr)
					{
						self::$whereInKeys[] = $whereInKey;
						self::$whereInStrs[] = $whereInStr;
					}
				}
			}
			else
			{
				self::setBindValue($finalBindValues, $bKey, $bindValue);
			}
		}

		self::$bindValues = $finalBindValues;
	}

	private static function setBindValue(&$finalBindValues, $key, $value)
	{
		$finalBindValues[$key] = $value;
	}

	private static function checkBindValues($stmt)
	{
		if(self::$bindValues && sizeof(self::$bindValues)> 0)
		{
			foreach(self::$bindValues as $bindName => $bindValue)
			{
				$stmt->bindValue(is_numeric($bindName)? ($bindName+1): ':'.$bindName, $bindValue);
			}
		}
	}

	public static function toSql($query, $bindValues)
	{
		self::checkWhereInClause($query);

		$isKeyValue = true;
		foreach(self::$bindValues as $bvk => $bindValue)
		{
			if(is_numeric($bvk))
			{
				$isKeyValue = false;
			}
			$query = str_replace(':'.$bvk, (is_numeric($bindValue)? $bindValue: (($bindValue === NULL)? 'NULL': "'{$bindValue}'")), $query);
		}
		/**
		 * If bindValues are not key value pairs and with '?' placeholder
		 */
		if($isKeyValue === false)
		{
			$query = self::replaceOccurrences($query, '?', self::$bindValues);
		}

		return $query;
	}

	private static function checkWhereInClause(&$query)
	{
		if(sizeof(self::$whereInKeys) > 0)
		{
			$query = str_replace(self::$whereInKeys, self::$whereInStrs, $query);
		}
	}

    public static function raw($string)
    {
    	return new Raw($string);
    }

    public static function setBindValues($bindValues=[])
    {
    	self::$bindValues = $bindValues;
    }

	public static function execute($query, $type='', $env='', $isObject=false, $class=NULL)
	{
		self::checkWhereInClause($query);
		try 
		{
			$connection = Connection::get($env);
			$statement 	= $connection->prepare($query);

			if(sizeof(self::$bindValues)> 0)
			{
				foreach(self::$bindValues as $bindName => $bindValue)
				{
					$statement->bindValue(is_numeric($bindName)? ($bindName+1): ':'.$bindName, $bindValue);
				}
			}

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
			self::$bindValues = [];
			return $result;
		}
		catch(PDOException $e)
		{
			/**
			 * Sleeping and attempting multiple times if - Lock wait timeout exceeded
			 */
			if(Connection::isLockWait($e))
			{
				return self::execute($query, $type, $env, $isObject, $class);
			}
			/**
			 * Logging Exception and stopping the script
			 */
			Error::logException($e, $query);
		}
	}

	public static function select($query, $bindValues=[])
	{
		self::setBindValues($setBindValues);
		return self::execute($query, 'select');
	}

	public static function insert($query, $bindValues=[])
	{
		self::setBindValues($setBindValues);
		return self::execute($query, 'insert', true);
	}

	public static function update($query, $bindValues=[])
	{
		self::setBindValues($setBindValues);
		return self::execute($query, 'update', false, true);
	}
}