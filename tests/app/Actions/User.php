<?php

namespace App\Actions;

use PHPattern\Action;

class User extends Action
{
	public function index()
	{
		return response()->json('User route', true);
	}

	public function detail()
	{
		return response()->json('User detail route', true);	
	}
}