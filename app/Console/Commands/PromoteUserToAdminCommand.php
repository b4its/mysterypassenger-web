<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:promote-admin {user : ID, username, atau email pengguna}';

    /**
     * @var string
     */
    protected $description = 'Jadikan pengguna sebagai Administrator panel Filament';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');

        /** @var User|null $user */
        $user = User::query()
            ->where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("Pengguna dengan identitas '{$identifier}' tidak ditemukan.");

            return self::FAILURE;
        }

        $user->update(['role' => UserRole::Admin]);

        $this->info("Pengguna {$user->name} ({$user->email}) kini memiliki peran Administrator.");

        return self::SUCCESS;
    }
}
