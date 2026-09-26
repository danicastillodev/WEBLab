<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CrearAdmin extends Command
{
    protected $signature = 'weblab:crear-admin
                            {username : Usuario con el que se inicia sesión}
                            {email : Correo del administrador}
                            {--name= : Nombre visible (por defecto, el username)}
                            {--password= : Contraseña; si se omite se genera una al azar}';

    protected $description = 'Crea un administrador del sistema (o actualiza su contraseña si ya existe)';

    /**
     * Pensado para un hosting sin shell: no pregunta nada y escribe el
     * resultado en su salida, de modo que se pueda lanzar desde un cron de un
     * solo uso redirigido a un archivo, y leer ahí la contraseña generada.
     */
    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $email = (string) $this->argument('email');
        $existente = User::where('username', $username)->first();

        $validador = Validator::make([
            'username' => $username,
            'email' => $email,
            'password' => $this->option('password'),
        ], [
            'username' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($existente?->id),
            ],
            'password' => ['nullable', 'string', 'min:12'],
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $generada = $this->option('password') === null;
        $password = $this->option('password') ?? Str::password(20);

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $this->option('name') ?: $username,
                'email' => $email,
                'password' => $password,
                'es_admin' => true,
            ]
        );

        // Fuera de $fillable a propósito, así que se asigna aparte. Hoy el
        // modelo no implementa MustVerifyEmail, pero si algún día se activa,
        // el administrador no debe quedarse fuera de su propio sistema.
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->info(($existente ? 'Administrador actualizado' : 'Administrador creado').": {$user->username} <{$user->email}>");

        if ($generada) {
            $this->line('Contraseña generada: '.$password);
            $this->warn('Cámbiala al entrar y borra el registro donde haya quedado escrita.');
        }

        return self::SUCCESS;
    }
}
