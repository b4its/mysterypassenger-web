<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use Filament\Commands\MakeUserCommand as FilamentMakeUserCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeUserCommand extends FilamentMakeUserCommand
{
    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption(
                name: 'role',
                shortcut: null,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Peran pengguna (admin, reviewer, surveyor)',
                default: null,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserData(): array
    {
        $data = parent::getUserData();

        $roleInput = $this->options['role'] ?? null;

        if (! $roleInput) {
            if (! $this->input->isInteractive()) {
                $roleInput = UserRole::Admin->value;
            } else {
                $roleInput = select(
                    label: 'Peran Pengguna (Role)',
                    options: [
                        UserRole::Admin->value => 'Administrator (Akses penuh ke seluruh menu & konfigurasi)',
                        UserRole::Reviewer->value => 'Reviewer (Pemeriksaan & persetujuan hasil survei)',
                        UserRole::Surveyor->value => 'Surveyor (Pengisian survei lapangan)',
                    ],
                    default: UserRole::Admin->value,
                );
            }
        }

        $data['role'] = UserRole::tryFrom((string) $roleInput) ?? UserRole::Admin;

        return $data;
    }
}
