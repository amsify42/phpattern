<?php

Route::get('/', function(){
    return response()->json('Welcome', true);
});

Route::get('user', Actions\User::class);

Route::post('user/create', Actions\User::class@create);

Route::get('user/detail', Actions\User::class@detail);

Route::put('user/update', Actions\User::class@update);

Route::delete('user/{$id}/delete', Actions\User::class@delete);

Route::put('user/update', Actions\User::class@update);

Route::post('user/middleware', Actions\User::class@middleware)->middleware(Middlewares\Sample::class);

Route::post('user/request', Actions\User::class@request);

Route::post('user/middleware-request', Actions\User::class@middlewareRequest)->middleware(Middlewares\Request::class);

Route::post('user/typestruct', Actions\User::class@typestruct);