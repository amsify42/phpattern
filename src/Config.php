<?php

namespace PHPattern;

use Amsify42\PHPVarsData\Data\ArraySimple;

class Config
{
	private static $array = NULL;

	private static function checkInit()
	{
		if(!self::$array)
		{
			self::$array = new ArraySimple([]);
		}
	}

	public static function set($path, $value)
	{
		self::checkInit();
		$pathArr = explode('.', $path);
		if(sizeof($pathArr)> 0)
		{
			if(isset(self::$array[$pathArr[0]]))
			{
				return self::$array->setValue($pathArr, $value);
			}
			else if(file_exists(self::configPath($pathArr[0])))
			{
				self::extract($pathArr[0]);
				return self::$array->setValue($pathArr, $value);
			}
		}
	}

	public static function get($path, $default=NULL)
	{
		self::checkInit();
		$pathArr = explode('.', $path);
		if(sizeof($pathArr)> 0)
		{
			if(isset(self::$array[$pathArr[0]]))
			{
				return self::$array->getValue($pathArr, $default);
			}
			else if(file_exists(self::configPath($pathArr[0])))
			{
				self::extract($pathArr[0]);
				return self::$array->getValue($pathArr, $default);
			}
		}
		return $default;
	}

	private static function extract($key)
	{
		self::$array->setFileData($key, self::configPath($key));
	}

	private static function configPath($file)
	{
		return APP_PATH.DS.'config'.DS.$file.'.php';
	}
}