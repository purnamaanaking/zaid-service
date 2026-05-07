<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/integrations/google-calendar/connected', function () {
    return view('google-calendar-connected');
});
