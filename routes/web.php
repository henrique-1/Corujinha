<?php

use App\Http\Controllers\Admin\ProfessorController as AdminProfessorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ConfirmIdentityController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\Professor\AssessmentController;
use App\Http\Controllers\Professor\ClassroomController;
use App\Http\Controllers\Professor\QuestionController;
use App\Http\Controllers\Student\ClassroomPlayerController;
use App\Models\User;
use App\Notifications\ProfessorInviteNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas e de Acesso do Estudante
|--------------------------------------------------------------------------
*/
Route::get('/', [ClassroomPlayerController::class, 'showEnter'])->name('home');
Route::get('/_boost/browser-logs', fn () => redirect()->route('home'));
Route::get('/midia/questoes/{question}', [QuestionController::class, 'showSupportImage'])->name('midia.questao');

Route::prefix('sala')->name('estudante.')->group(function () {
    Route::get('/', [ClassroomPlayerController::class, 'showEnter'])->name('entrar');
    Route::post('/iniciar', [ClassroomPlayerController::class, 'start'])->name('iniciar');
    Route::post('/retomar', [ClassroomPlayerController::class, 'resume'])->name('retomar');

    Route::prefix('{code}')->group(function () {
        Route::get('/atividade', [ClassroomPlayerController::class, 'player'])->name('player');
        Route::post('/atividade/responder', [ClassroomPlayerController::class, 'saveAnswer'])
            ->middleware('throttle:student-answers')
            ->name('salvar_resposta');
        Route::post('/atividade/concluir', [ClassroomPlayerController::class, 'submitAttempt'])
            ->middleware('throttle:student-answers')
            ->name('concluir_atividade');
        Route::get('/revisao', [ClassroomPlayerController::class, 'showReview'])->name('revisao');
        Route::post('/revisao/concluir', [ClassroomPlayerController::class, 'finishReview'])
            ->middleware('throttle:student-answers')
            ->name('concluir_revisao');
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
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/login/passkey/options', [AuthController::class, 'passkeyLoginOptions'])
        ->middleware('throttle:login')
        ->name('login.passkey.options');
    Route::post('/login/passkey', [AuthController::class, 'passkeyLoginVerify'])
        ->middleware('throttle:login')
        ->name('login.passkey.verify');

    Route::get('/login/totp', [AuthController::class, 'showTotpChallenge'])->name('login.totp');
    Route::post('/login/totp', [AuthController::class, 'verifyTotp'])->middleware('throttle:totp');

    Route::get('/cadastro/finalizar/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
    Route::post('/cadastro/finalizar/{token}', [InvitationController::class, 'accept'])->middleware('throttle:password-reset');

    // Recuperação de Senha (NIST SP 800-63B-4 / 30 min)
    Route::get('/esqueci-minha-senha', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/esqueci-minha-senha', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/redefinir-senha/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/redefinir-senha', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Confirmação de Identidade (Step-up Auth) & Central de Segurança (MFA)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role'])->group(function () {
    Route::get('/seguranca/confirmar', [ConfirmIdentityController::class, 'show'])->name('password.confirm');
    Route::post('/seguranca/confirmar', [ConfirmIdentityController::class, 'store'])
        ->middleware('throttle:totp')
        ->name('password.confirm.store');
});

Route::middleware(['auth', 'role'])->prefix('seguranca')->name('security.')->group(function () {
    Route::get('/passkey-sugestao', [SecurityController::class, 'passkeySuggestion'])->name('passkey.suggestion');
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::post('/totp/enable', [SecurityController::class, 'enableTotp'])
        ->middleware('throttle:totp')
        ->name('totp.enable');
    Route::post('/totp/disable', [SecurityController::class, 'disableTotp'])
        ->middleware('password.confirm')
        ->name('totp.disable');

    Route::get('/passkey/options', [SecurityController::class, 'passkeyRegisterOptions'])
        ->middleware('password.confirm')
        ->name('passkey.options');
    Route::post('/passkey', [SecurityController::class, 'passkeyRegisterStore'])
        ->middleware('password.confirm')
        ->name('passkey.store');
    Route::delete('/passkey/{credential}', [SecurityController::class, 'passkeyDestroy'])
        ->middleware('password.confirm')
        ->name('passkey.destroy');
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
    Route::delete('/professores/{professor}/convite', [AdminProfessorController::class, 'destroyInvite'])->name('professores.destroy_invite');
    Route::post('/professores/{professor}/desabilitar-totp', [AdminProfessorController::class, 'disableTotp'])->name('professores.disable_totp');
});

/*
|--------------------------------------------------------------------------
| Módulo do Professor
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:professor'])->prefix('professor')->name('professor.')->group(function () {
    // Banco de Perguntas
    Route::resource('perguntas', QuestionController::class)
        ->except(['show'])
        ->names('perguntas');

    // Avaliações
    Route::resource('avaliacoes', AssessmentController::class)
        ->except(['show'])
        ->names('avaliacoes')
        ->parameters(['avaliacoes' => 'avaliacao']);
    Route::get('/avaliacoes/{avaliacao}/estatisticas', [AssessmentController::class, 'statistics'])->name('avaliacoes.estatisticas');

    // Salas de Aula
    Route::resource('salas', ClassroomController::class)->names('salas');
    Route::post('/salas/{sala}/toggle-status', [ClassroomController::class, 'toggleStatus'])->name('salas.toggle_status');
    Route::post('/salas/{sala}/devolutiva', [ClassroomController::class, 'enterDevolutiva'])->name('salas.devolutiva.enter');
    Route::get('/salas/{sala}/devolutiva', [ClassroomController::class, 'devolutiva'])->name('salas.devolutiva');
    Route::get('/salas/{sala}/tentativas/{tentativa}', [ClassroomController::class, 'showAttempt'])->name('salas.tentativa');
    Route::get('/salas/{sala}/visualizar-prova', [ClassroomController::class, 'previewAssessment'])->name('salas.preview');
});

/*
|--------------------------------------------------------------------------
| Ambiente de Desenvolvimento: Pré-visualização de E-mails
|--------------------------------------------------------------------------
*/
if (app()->environment('local', 'development', 'testing')) {
    Route::prefix('dev/emails')->name('dev.emails.')->group(function () {
        Route::get('/convite-professor', function () {
            $user = new User([
                'name' => 'Prof. Carlos Eduardo',
                'email' => 'carlos.eduardo@escola.sesi.br',
            ]);

            return (new ProfessorInviteNotification('TOKEN_EXEMPLO_PREVIEW_123456'))
                ->toMail($user);
        })->name('convite');

        Route::get('/recuperacao-senha', function () {
            $user = new User([
                'name' => 'Profa. Ana Carolina',
                'email' => 'ana.carolina@escola.sesi.br',
            ]);

            return (new ResetPasswordNotification('TOKEN_EXEMPLO_RESET_123456'))
                ->toMail($user);
        })->name('reset');
    });
}
