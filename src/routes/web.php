<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('panel.dashboard.index');
});

Route::group(['prefix' => 'auth', 'as' => 'auth.', 'middleware' => 'auth.guests'], function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/signup', 'pages::auth.signup')->name('signup');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('forgot.password');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('reset.password');
});

Route::group(['prefix' => 'panel', 'as' => 'panel.', 'middleware' => 'user.auth'], function () {
    Route::livewire('dashboard', 'pages::panel.dashboard.index')->name('dashboard.index');

    Route::group(['prefix' => 'signers', 'as' => 'signers.'], function () {
        Route::livewire('/', 'pages::panel.signer.index')->name('index');
        Route::livewire('/create', 'pages::panel.signer.save')->name('create');
        Route::livewire('/{ulid}/edit', 'pages::panel.signer.save')->name('edit');
    });

    Route::group(['prefix' => 'processes', 'as' => 'processes.'], function () {
        Route::livewire('/', 'pages::panel.process.index')->name('index');
    });
});
