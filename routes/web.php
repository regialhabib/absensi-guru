<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = request()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'dashboard');
})->name('home');

require __DIR__.'/guru.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
