<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class ManageUserRoleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:role 
                            {user : ID, username, atau email pengguna} 
                            {role? : Peran baru (admin, reviewer, surveyor)}';

    /**
     * @var string
     */
    protected $description = 'Periksa atau ubah peran (role) pengguna';

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

        $this->info("Pengguna ditemukan: {$user->name} ({$user->email}) - Peran saat ini: [{$user->role->value}]");

        $newRoleInput = $this->argument('role');

        if (! $newRoleInput) {
            if (! $this->input->isInteractive()) {
                return self::SUCCESS;
            }

            $newRoleInput = select(
                label: 'Pilih peran baru untuk pengguna ini:',
                options: [
                    UserRole::Admin->value => 'Administrator (Akses penuh ke semua menu/konfigurasi)',
                    UserRole::Reviewer->value => 'Reviewer (Pemeriksaan & review hasil survei)',
                    UserRole::Surveyor->value => 'Surveyor (Pengisian survei lapangan)',
                ],
                default: $user->role->value,
            );
        }

        $newRole = UserRole::tryFrom((string) $newRoleInput);

        if (! $newRole) {
            $this->error("Peran '{$newRoleInput}' tidak valid. Pilihan yang tersedia: admin, reviewer, surveyor.");

            return self::FAILURE;
        }

        $user->update(['role' => $newRole]);

        $this->info("Berhasil memperbarui peran {$user->name} ({$user->email}) menjadi: [{$newRole->value}] ({$newRole->getLabel()})");

        return self::SUCCESS;
    }
}
