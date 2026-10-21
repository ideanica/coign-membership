<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name=Administrator} {--role=admin : admin or superadmin} {--generate-password : Generate and show a new password once}';

    protected $description = 'Create an administrator without promoting or replacing an existing member';

    public function handle(): int
    {
        $role = (string) $this->option('role');
        if (! in_array($role, ['admin', 'superadmin'], true)) {
            $this->error('Role must be admin or superadmin.');

            return self::FAILURE;
        }
        $email = Str::lower(trim((string) $this->argument('email')));
        if (User::where('email', $email)->exists()) {
            $this->error('This email already exists. No account was changed.');

            return self::FAILURE;
        }
        $password = $this->option('generate-password') ? Str::password(20, symbols: false) : $this->secret('Administrator password (minimum 12 characters)');
        $values = ['name' => $this->option('name'), 'email' => $email, 'password' => $password];
        $validator = Validator::make($values, ['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'min:12']]);
        if ($validator->fails()) {
            $this->error(implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }
        DB::transaction(function () use ($validator, $role): void {
            $user = User::create($validator->validated());
            $user->forceFill(['is_admin' => true, 'is_superadmin' => $role === 'superadmin', 'is_active' => true])->save();
        });
        $this->info('Administrator created: '.$email);
        if ($this->option('generate-password')) {
            $this->line('Password: '.$password);
        }

        return self::SUCCESS;
    }
}
