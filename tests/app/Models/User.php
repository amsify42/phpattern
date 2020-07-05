<?php

namespace App\Models;

use PHPattern\Database\Model;

class User extends Model
{
    protected $timestamps = true;

    public function categories()
    {
        return $this->hasMany('user_id', \App\Models\Category::class);
    }

    public function student()
    {
        return $this->hasOne('user_id', \App\Models\Student::class);
    }
}