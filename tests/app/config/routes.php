<?php

Route::get('/', function(){
    return response()->json('Welcome', true);
});

Route::get('user', Actions\User::class);

Route::get('user/detail', Actions\User::class@detail);