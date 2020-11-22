<?php

namespace PHPattern\Database;

use PHPattern\Database\Error;
use PHPattern\ENV;

class Credentials
{
	private $env   = '';
	private $host, $port, $user, $password, $name;

	function __construct($env='')
	{
		if($env)
		{
			$this->env = $env;
		}
		else
		{
			$this->env = app_env();
		}
		$this->setCredentials();
	}

	private function setCredentials()
	{
		$creds = $this->getCreds();
		$this->host 	= $creds['host'];
		$this->port 	= $creds['port'];
		$this->user 	= $creds['user'];
		$this->password = $creds['password'];
		$this->name 	= $creds['name'];
	}

	private function getCreds()
	{
		return [
			'host' 		=> ENV::get('DB_HOST', '127.0.0.1'),
			'port' 		=> ENV::get('DB_PORT', '3306'),
			'user' 		=> ENV::get('DB_USER', 'root'),
			'password' 	=> ENV::get('DB_PASSWORD', ''),
			'name' 		=> ENV::get('DB_NAME', '')
		];
	}

	public function host()
	{
		return $this->host;
	}

	public function port()
	{
		return $this->port;
	}

	public function user()
	{
		return $this->user;
	}

	public function password()
	{
		return $this->password;
	}

	public function name()
	{
		return $this->name;
	}
}