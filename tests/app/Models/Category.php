<?php

namespace App\Models;

use PHPattern\Database\Model;

class Category extends Model
{
    protected $timestamps = true;

    public function user()
    {
        return $this->belongsTo('user_id', \App\Models\User::class);
    }
}