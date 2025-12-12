<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

final class TestStaffUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding usuarios de prueba para password_changed_at...');

        $password = Hash::make('Password123!');

        $users = [
            [
                'name' => 'User Fresh',
                'email' => 'user.fresh@domain.com',
                'password' => $password,
                'updates' => [
                    'password_changed_at' => Date::now(),
                    'last_activity' => Date::now()->subMinutes(2),
                ],
                'desc' => 'User Fresh (password reciente)',
            ],
            [
                'name' => 'User Stale',
                'email' => 'user.stale@domain.com',
                'password' => $password,
                'updates' => [
                    'password_changed_at' => Date::now()->subDays(120),
                    'last_activity' => Date::now()->subDays(1),
                ],
                'desc' => 'User Stale (password vencido)',
            ],
            [
                'name' => 'User Missing',
                'email' => 'user.missing@domain.com',
                'password' => $password,
                'updates' => [
                    'password_changed_at' => null,
                    'last_activity' => Date::now()->subMinutes(1),
                ],
                'desc' => 'User Missing (sin marca de cambio)',
            ],
        ];

        foreach ($users as $userData) {
            $this->createIfNotExists($userData);
        }

        $this->command->info('Seeder completado.');
    }

    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     updates: array<string, mixed>,
     *     desc: string
     * } $data
     */
    private function createIfNotExists(array $data): void
    {
        if (StaffUser::query()->where('email', $data['email'])->exists()) {
            $this->command->info(sprintf('Skip: %s ya existe', $data['name']));

            return;
        }

        /** @var \Modules\Core\Database\Factories\StaffUsersFactory $factory */
        $factory = StaffUser::factory();

        /** @var StaffUser $user */
        $user = $factory->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->forceFill($data['updates'])->save();

        $this->command->info('Creado: '.$data['desc']);
    }
}
