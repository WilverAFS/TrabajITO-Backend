<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

//Ruta temporal para las pruebas de recuperacion de contraseña
Route::get(
    '/reset-password/{token}',
    function (
        Request $request,
        string $token
    ) {
        return response()->json([
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }
)->name('password.reset');