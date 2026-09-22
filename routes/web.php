<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/download', function () {
    return redirect()->away('https://drive.google.com/drive/folders/11xW8ol-Zi4qBwSxktrdw9BTjIpTM-KcB?usp=drive_link');
});

Route::get('/app', function () {
    return view('app');
});

Route::get('/dashboard', function () {
    return view('dashboard', ['initialView' => 'calendar']);
});



Route::get('/integrations/google-calendar/connected', function () {
    return view('google-calendar-connected');
});

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/terms', function () {
    return view('terms');
});
