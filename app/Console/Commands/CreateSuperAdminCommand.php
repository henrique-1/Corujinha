<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin
                            {--name= : Nome completo do Super-Usuário}
                            {--email= : E-mail de acesso}
                            {--cpf= : CPF válido (com ou sem pontuação)}
                            {--password= : Senha de 15 a 64 caracteres (NIST SP 800-63B-4)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria uma conta de Super-Usuário para administrar o sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Criação de Super-Usuário (Corujinha) ===');

        $name = $this->option('name') ?: $this->ask('Nome completo');
        $email = $this->option('email') ?: $this->ask('E-mail institucional');
        $cpf = $this->option('cpf') ?: $this->ask('CPF (11 dígitos)');

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Senha (mínimo de 15 e máximo de 64 caracteres - NIST SP 800-63B-4)');
            $passwordConfirmation = $this->secret('Confirme a senha');

            if ($password !== $passwordConfirmation) {
                $this->error('As senhas não coincidem!');

                return self::FAILURE;
            }
        }

        $sanitizedCpf = CpfRule::sanitize($cpf);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'cpf' => $sanitizedCpf,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'cpf' => ['required', 'string', new CpfRule, 'unique:users,cpf'],
            'password' => [
                'required',
                'string',
                Password::min(15)->max(64),
                function (string $attribute, mixed $value, \Closure $fail) use ($name, $email, $sanitizedCpf) {
                    $lowerValue = mb_strtolower((string) $value);
                    $lowerName = mb_strtolower((string) $name);
                    $emailUser = mb_strtolower(explode('@', (string) $email)[0] ?? '');

                    if (! empty($lowerName) && str_contains($lowerValue, $lowerName)) {
                        $fail('A senha não deve conter seu nome completo.');
                    }

                    if (! empty($emailUser) && strlen($emailUser) >= 3 && str_contains($lowerValue, $emailUser)) {
                        $fail('A senha não deve conter o prefixo do seu e-mail.');
                    }

                    if (! empty($sanitizedCpf) && str_contains($lowerValue, $sanitizedCpf)) {
                        $fail('A senha não deve conter seu CPF.');
                    }
                },
            ],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Já existe um usuário cadastrado com este e-mail.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado no sistema.',
            'password.min' => 'A senha deve possuir no mínimo 15 caracteres (NIST SP 800-63B-4).',
            'password.max' => 'A senha deve possuir no máximo 64 caracteres (NIST SP 800-63B-4).',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error("• {$error}");
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'cpf' => $sanitizedCpf,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->info("✓ Super-Usuário [{$user->name}] criado com sucesso!");
        $this->line("E-mail: {$user->email}");
        $this->line('CPF: '.CpfRule::format($user->cpf));

        return self::SUCCESS;
    }
}
