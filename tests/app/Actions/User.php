<?php

namespace App\Actions;

use PHPattern\Action;
use App\Request\Sample;
use App\Request\MiddRequest;
use App\Request\Simpul;
use App\Models\User as UserModel;

class User extends Action
{
	public function index()
	{
		return response()->json('User route', true, UserModel::all());
	}

	public function create()
	{
		$data = [
			'req' => \PHPattern\Request::all(),
			'id' => UserModel::insert(['name' => \PHPattern\Request::get('name')])
		];
		return response()->json('User create', true, $data);
	}

	public function detail($id)
	{
		return response()->json('User detail route', true, ['id' => $id]);
	}

	public function update($id)
	{
		$data = [
			'req' => \PHPattern\Request::all(),
			'updatedRows' => UserModel::update(['name' => \PHPattern\Request::get('name')], $id)
		];
		return response()->json('User update', true, $data);
	}

	public function delete($id)
	{
		return response()->json('User delete route', true);
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