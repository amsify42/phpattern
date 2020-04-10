<?php

namespace PHPattern\Database;

use PDOException;

class Error
{
	public static function response()
	{
		render_response(response()->setCode(500)->json('MySQL Error Occured'));
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