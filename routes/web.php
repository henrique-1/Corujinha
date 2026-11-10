<?php

use App\Http\Controllers\Admin\ProfessorController as AdminProfessorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\Professor\AssessmentController;
use App\Http\Controllers\Professor\ClassroomController;
use App\Http\Controllers\Professor\QuestionController;
use App\Http\Controllers\Student\ClassroomPlayerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas e de Acesso do Estudante
|--------------------------------------------------------------------------
*/
Route::get('/', [ClassroomPlayerController::class, 'showEnter'])->name('home');

Route::prefix('sala')->name('estudante.')->group(function () {
    Route::get('/', [ClassroomPlayerController::class, 'showEnter'])->name('entrar');
    Route::post('/iniciar', [ClassroomPlayerController::class, 'start'])->name('iniciar');

    Route::prefix('{code}')->group(function () {
        Route::get('/atividade', [ClassroomPlayerController::class, 'player'])->name('player');
        Route::post('/atividade/responder', [ClassroomPlayerController::class, 'saveAnswer'])->name('salvar_resposta');
        Route::post('/atividade/concluir', [ClassroomPlayerController::class, 'submitAttempt'])->name('concluir_atividade');
        Route::get('/revisao', [ClassroomPlayerController::class, 'showReview'])->name('revisao');
        Route::post('/revisao/concluir', [ClassroomPlayerController::class, 'finishReview'])->name('concluir_revisao');
        Route::get('/resultado', [ClassroomPlayerController::class, 'showResult'])->name('resultado');
    });
});

/*
|--------------------------------------------------------------------------
| Autenticação, Convite de Professores e Segurança (NIST SP 800-63B-4)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/login/totp', [AuthController::class, 'showTotpChallenge'])->name('login.totp');
    Route::post('/login/totp', [AuthController::class, 'verifyTotp']);

    Route::get('/cadastro/finalizar/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
    Route::post('/cadastro/finalizar/{token}', [InvitationController::class, 'accept']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Segurança e Credenciais (MFA: TOTP e Passkeys)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role'])->prefix('seguranca')->name('security.')->group(function () {
    Route::get('/passkey-sugestao', [SecurityController::class, 'passkeySuggestion'])->name('passkey.suggestion');
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::post('/totp/enable', [SecurityController::class, 'enableTotp'])->name('totp.enable');
    Route::post('/totp/disable', [SecurityController::class, 'disableTotp'])->name('totp.disable');

    Route::get('/passkey/options', [SecurityController::class, 'passkeyRegisterOptions'])->name('passkey.options');
    Route::post('/passkey', [SecurityController::class, 'passkeyRegisterStore'])->name('passkey.store');
    Route::delete('/passkey/{credential}', [SecurityController::class, 'passkeyDestroy'])->name('passkey.destroy');
});

/*
|--------------------------------------------------------------------------
| Módulo Super-Administrador
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/professores', [AdminProfessorController::class, 'index'])->name('professores.index');
    Route::get('/professores/criar', [AdminProfessorController::class, 'create'])->name('professores.create');
    Route::post('/professores', [AdminProfessorController::class, 'store'])->name('professores.store');
    Route::post('/professores/{professor}/reenviar-convite', [AdminProfessorController::class, 'resendInvite'])->name('professores.resend_invite');
    Route::post('/professores/{professor}/desabilitar-totp', [AdminProfessorController::class, 'disableTotp'])->name('professores.disable_totp');
});

/*
|--------------------------------------------------------------------------
| Módulo do Professor
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:professor'])->prefix('professor')->name('professor.')->group(function () {
    // Banco de Perguntas
    Route::resource('perguntas', QuestionController::class)->names('perguntas');

    // Avaliações
    Route::resource('avaliacoes', AssessmentController::class)->names('avaliacoes');

    // Salas de Aula
    Route::resource('salas', ClassroomController::class)->names('salas');
    Route::post('/salas/{sala}/toggle-status', [ClassroomController::class, 'toggleStatus'])->name('salas.toggle_status');
    Route::get('/salas/{sala}/tentativas/{tentativa}', [ClassroomController::class, 'showAttempt'])->name('salas.tentativa');
});
