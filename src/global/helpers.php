<?php
/**
 * to check app environment
 * @return string
 */
function app_env()
{
	return '';
}

/**
 * var dump and die
 * @param  mixed|multiple
 */
function dd()
{
	$args = func_get_args();
	if(sizeof($args) > 0)
	{
		foreach($args as $akey => $arg)
		{
			var_dump($arg);
		}
	}
	die;
}

/**
 * Dashes To CamelCase
 * @param  string  $string
 * @param  boolean $capitalizeFirst
 * @return string
 */
function dashes_to_camel_case($string, $capitalizeFirst=false) 
{
    $str = str_replace('-', '', ucwords($string, '-'));
    if(!$capitalizeFirst)
    {
    	$str = lcfirst($str);
    }
    return $str;
}

/**
 * CamelCase To Underscore
 * @param  string $string
 * @return string
 */
function class_to_underscore($string)
{
	return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
}

/**
 * Checks if variable is set and not empty
 * @param  mixed  $var
 * @return boolean
 */
function isEmpty($var)
{
	if(!is_array($var) && !is_object($var))
	{
		$var = trim($var); 
	}
	return (empty($var) && $var != '0');
}

/**
 * Checks if array key exist and not empty
 * @param  array $arr
 * @param  string  $key
 * @return boolean
 */
function isKeyValue(&$arr, $key)
{
	return (isset($arr[$key]) && !isEmpty($arr[$key]))? true: false;
}

/**
 * Do SageAPI Logging
 * @param  string $message
 * @return void
 */
function log_message($message)
{
	$filePath = APP_PATH.DS.'logs'.DS.'errors.log';
	if(!file_exists($filePath))
	{
		$fp = fopen($filePath, 'w+');
		fwrite($fp, '');
		fclose($fp);
		chmod($filePath, 0777);
	}
	file_put_contents($filePath, $message.PHP_EOL , FILE_APPEND|LOCK_EX);
}

/**
 * Array to xml
 * @param  array $data
 * @param  SimpleXMLElement $xml
 */
function array_to_xml($data, &$xml)
{
    foreach($data as $key => $value)
    {
        if(is_numeric($key))
        {
            $key = 'item'.$key;
        }
        
        if(is_array($value))
        {
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        }
        else
        {
            $xml->addChild($key, htmlspecialchars($value));
        }
     }
}

/**
 * Get new model instance
 * @param  string $table
 * @return \PHPattern\Model $model
 */
function get_model($table='')
{
	$model = new \PHPattern\Database\Model();
	if($table)
	{
		$model->setTable($table);
	}
	return $model;
}

/**
 * Get new response instance
 * @return App\Helpers\Response $response
 */
function response()
{
	return new \PHPattern\Response();
}

/**
 * Render response data
 * @param  App\Helpers\Response $response
 * @return void
 */
function render_response(PHPattern\Response $response)
{
	\PHPattern\Database\Connection::close();
	if(!\PHPattern\Request::isCLI())
	{
		http_response_code($response->getCode());
		if($response->getType())
		{
			header('Content-Type: '.$response->getType());
		}
		header('App-Env: '.app_env());
	}
	echo $response->getContent();
	exit;
}