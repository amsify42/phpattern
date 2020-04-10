<?php

namespace PHPattern;

use PHPattern\Request\Data;

class Request
{
	public static $data = NULL;

	public static function __callStatic($method, $args)
	{
		self::setData();
		/**
		 * Making this class instance call static methods of PHPattern\RequestData class
		 */
		if(method_exists(self::$data, $method) && is_callable([self::$data, $method]))
		{
			return call_user_func_array([self::$data, $method], $args);
		}
	}

	private static function setData()
	{
		if(!self::$data)
		{
			self::$data = new Data();
		}
	}

	public static function data()
	{
		self::setData();
		return self::$data;
	}

	public static function isCLI()
	{
		return (PHP_SAPI === 'cli');
	}

	public static function isGet()
	{
		return ($_SERVER['REQUEST_METHOD'] === 'GET');
	}

	public static function isPost()
	{
		return ($_SERVER['REQUEST_METHOD'] === 'POST');
	}

	public static function isPut()
	{
		return ($_SERVER['REQUEST_METHOD'] === 'PUT');
	}

	public static function isDelete()
	{
		return ($_SERVER['REQUEST_METHOD'] === 'DELETE');
	}

	public static function isRequest($type)
	{
		if(is_array($type))
		{
			return (in_array($_SERVER['REQUEST_METHOD'], strtoupper($type)));
		}
		else
		{
			return ($_SERVER['REQUEST_METHOD'] === strtoupper($type));
		}
	}
}