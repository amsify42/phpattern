<?php

namespace PHPattern;

use PHPattern\Database\Credentials;
use PHPattern\Database\Error;
use PDO;
use PDOException;

class Database
{
	private $env 		= '';
	private $credentials= NULL;
	private $connection = NULL;

	function __construct($env='', $credentials=NULL)
	{
		$this->env 			= $env;
		$this->credentials 	= ($credentials)? $credentials: new Credentials($env);
		$this->connect();
	}

	function __call($method, $args)
	{
		if(method_exists($this->connection, $method) && is_callable([$this->connection, $method]))
		{
			return call_user_func_array([$this->connection, $method], $args);
		}
	}

	private function connect()
	{
		try
		{
			$this->connection = new PDO("mysql:host={$this->credentials->host()};dbname={$this->credentials->name()};port={$this->credentials->port()};charset=utf8mb4;", $this->credentials->user(), $this->credentials->password());
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e)
		{
			Error::logException($e);
		}
	}

	public function env()
	{
		return $this->env;
	}

	public function credentials()
	{
		return $this->credentials;
	}

	public function connection()
	{
		if($this->connection && $this->connection instanceof PDO)
		{
			if(!$this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS))
			{
				$this->close();	
			}
		}
		return $this->connection;
	}

	public function prepare($query)
	{
		return $this->connection->prepare($query);
	}

	public function lastInsertId()
	{
		return $this->connection->lastInsertId();
	}

	public function close()
	{
		$this->connection = NULL;
	}
}