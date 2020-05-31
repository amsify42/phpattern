<?php

namespace PHPattern\Database;

use PDOException;
use PHPattern\Config;

class Error
{
	public static function response()
	{
		$response = response()->setCode(500);
		if(Config::get('app.response_type') == 'html')
        {
            render_response($response->view('errors.mysql'));
        }
        else
        {
            render_response($response->json('MySQL Error Occured'));
        }
	}

	public static function log($message)
	{
		log_message("\n[".date('D d M Y - h:i:s A')."]\n".$message);
	}

	public static function logException(PDOException $e, $query='')
	{
		$message = "Error : ".$e->getMessage();
		if($query)
		{
			$message .= "\nQuery : ".$query."\n";
		}
		self::log($message);
		self::response();
	}
}