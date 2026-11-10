<?php

use App\Http\Controllers\AtividadeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AtividadeController::class, 'index'])->name('atividade.index');
