<?php

namespace App\Middlewares;

use PHPattern\Middleware;
use PHPattern\Request\Data;

class Sample extends Middleware
{
	public function process(Data $requestData)
	{
		if($requestData->header('Authorization') == 'amsify')
		{
			return true;
		}
		return false;
	}

}