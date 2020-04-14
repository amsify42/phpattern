<?php

namespace App\Request;

use PHPattern\Request\Form;

class Sample extends Form
{
	protected function rules()
	{
		return [
			'id' => 'required'
		];
	}

}