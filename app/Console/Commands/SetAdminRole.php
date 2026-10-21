<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetAdminRole extends Command
{
    protected $signature = 'admin:role {email} {role : admin or superadmin}';

    protected $description = 'Change the role of an existing administrator without changing their password';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $role = (string) $this->argument('role');
        if (! in_array($role, ['admin', 'superadmin'], true)) {
            $this->error('Role must be admin or superadmin.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($email, $role): int {
            $administrators = User::where('is_admin', true)->orderBy('id')->lockForUpdate()->get();
            $user = $administrators->firstWhere('email', $email);
            if (! $user) {
                $this->error('Administrator not found. No account was changed.');

                return self::FAILURE;
            }

            $activeSuperadmins = $administrators->where('is_active', true)->where('is_superadmin', true);
            if ($role === 'admin' && $user->isSuperAdmin() && $user->is_active && $activeSuperadmins->count() <= 1) {
                $this->error('The last active superadmin cannot be demoted.');

                return self::FAILURE;
            }

            $user->forceFill(['is_superadmin' => $role === 'superadmin'])->save();
            $this->info('Administrator role updated: '.$role);

            return self::SUCCESS;
        });
    }
}
