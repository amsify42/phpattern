<?php

namespace PHPattern\Database;

use PHPattern\Database\Error;

class Credentials
{
	private $types = ['prod', 'dev', 'staging', 'seed'];
	private $env   = 'dev';
	private $host, $user, $password, $db;

	function __construct($env='')
	{
		if($env)
		{
			if(in_array($env, $this->types))
			{
				$this->env = $env;
			}
			else
			{
				Error::log('Invalid DB Type');
				Error::response();
			}
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
		$this->user 	= $creds['user'];
		$this->password = $creds['password'];
		$this->db 		= $creds['db'];
	}

	private function getCreds()
	{
		return [
			'host' => '',
			'user' => '',
			'password' => '',
			'db' => ''
		];
	}

	public function host()
	{
		return $this->host;
	}

	public function user()
	{
		return $this->user;
	}

	public function password()
	{
		return $this->password;
	}

	public function db()
	{
		return $this->db;
	}
}