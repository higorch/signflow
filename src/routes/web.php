<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'panel', 'as' => 'panel.'], function () {
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
