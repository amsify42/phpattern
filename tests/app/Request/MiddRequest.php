<?php

namespace App\Request;

use PHPattern\Request\Form;

class MiddRequest extends Form
{
	protected function rules()
	{
		return [
			'id' => 'required'
		];
	}

}