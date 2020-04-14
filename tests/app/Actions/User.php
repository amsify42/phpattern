<?php

namespace App\Actions;

use PHPattern\Action;
use App\Request\Sample;
use App\Request\MiddRequest;
use App\Request\Simpul;

class User extends Action
{
	public function index()
	{
		return response()->json('User route', true);
	}

	public function create()
	{
		return response()->json('User create', true, \PHPattern\Request::all());	
	}

	public function detail()
	{
		return response()->json('User detail route', true);	
	}

	public function update()
	{
		return response()->json('User update', true, \PHPattern\Request::all());	
	}

	public function delete($id)
	{
		return response()->json('User delete route', true, ['id' => $id]);	
	}

	public function middleware()
	{
		return response()->json('User middleware route', true);
	}

	public function request(Sample $request)
	{
		return response()->json('User request route', true, $request->all());
	}

	public function middlewareRequest(MiddRequest $request)
	{
		return response()->json('User middleware request route', true, $request->all());
	}

	public function typestruct(Simpul $request)
	{
		return response()->json('User typestruct route', true, $request->all());
	}
}