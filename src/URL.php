<?php

namespace PHPattern;

class URL
{
	private static $script 	= 'index.php';
	private static $baseURI = '';
	private static $baseURL = NULL;

	public static function requestURI()
	{
		self::setBaseURI();
		$uriArr = explode('?', $_SERVER['REQUEST_URI']);
		return str_replace(self::$baseURI, '', trim($uriArr[0]));
	}

	public static function baseURI()
	{
		return self::$baseURI;
	}

	public static function base()
	{
		self::setBaseURL();
		return self::$baseURL;
	}

	public static function current($params=[])
	{
		return self::get(self::requestURI(), $params);
	}

	public function get($uri='/', $params=[])
	{
		$url = self::absolute($uri);
		if(sizeof($params)> 0)
		{
			$uriParams = [];
			foreach($params as $param => $value)
			{
				$pattern = '{'.$param.'}';
				if(strpos($url, $pattern) !== false)
				{
					$url = str_replace($pattern, $value, $url);
				}
				else
				{
					$uriParams[$param] = $value;
				}
			}
			if(sizeof($uriParams)> 0)
			{
				$url .= '?'.http_build_query($uriParams);
			}
		}
		return $url;
	}

	private static function absolute($uri='')
	{
		return self::base().self::$baseURI.'/'.((isset($uri[0]) && $uri[0] == '/')? substr($uri, 1): $uri);
	}

	private static function setBaseURI()
	{
		if(!self::$baseURI)
		{
			self::$baseURI = str_replace('/'.self::$script, '', $_SERVER['SCRIPT_NAME']);
		}
	}

	private static function setBaseURL()
	{
		if(!self::$baseURL)
		{
			$prefix = (strpos($_SERVER['HTTP_HOST'], 'local') === false)? 'https://': 'http://';
			self::$baseURL = $prefix.$_SERVER['HTTP_HOST'];
		}
	}
}