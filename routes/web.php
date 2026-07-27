<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contacts.confirm');