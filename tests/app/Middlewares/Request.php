<?php

namespace App\Middlewares;

use PHPattern\Middleware;
use PHPattern\Request\Data;

class Request extends Middleware
{
	public function process(Data $requestData)
	{
		if($requestData->header('Some') == 'value')
		{
			return true;
		}
		return false;
	}

}