<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContactController::class, 'index'])
    ->name('contact.index');

Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contacts.confirm');

Route::post('/contacts', [ContactController::class, 'store'])
    ->name('contacts.store');

Route::get('/thanks', function () {
    return view('contact.thanks');
})
    ->name('contact.thanks');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])
        ->name('admin.index');

    Route::get('/admin/contacts/{contact}', [AdminController::class, 'show'])
        ->name('admin.contacts.show');

    Route::get('/contacts/export', [ContactController::class, 'export'])
        ->name('contacts.export');

    Route::post('/admin/tags', [TagController::class, 'store'])
        ->name('admin.tags.store');

    Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit'])
        ->name('admin.tags.edit');
});
