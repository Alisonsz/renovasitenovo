<?php

use App\Http\Controllers\Store\CategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');
Route::get('/quem-somos', fn () => Inertia::render('QuemSomos'))->name('quem-somos');
Route::get('/nossa-tecnologia', fn () => Inertia::render('NossaTecnologia'))->name('nossa-tecnologia');
Route::get('/depilacao-feminina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-feminina')
    ->name('store.feminine');
Route::get('/depilacao-masculina', [CategoryController::class, 'show'])
    ->defaults('slug', 'depilacao-masculina')
    ->name('store.masculine');
Route::get('/loja/categoria/{slug}', [CategoryController::class, 'show'])
    ->name('store.category');
