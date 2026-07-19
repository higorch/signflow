<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Higor Ferreira',
                'role' => 'root',
                'status' => 'active',
                'email' => 'higor@mail.test',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Guilherme Correia',
                'role' => 'root',
                'status' => 'active',
                'email' => 'guilherme@mail.test',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Erling Haaland',
                'role' => 'customer',
                'status' => 'active',
                'email' => 'haaland@mail.test',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'José Évora Dias',
                'role' => 'signer',
                'status' => 'active',
                'email' => 'vozinha@mail.test',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            $exists = User::where('email_hash', hmac_hash($user['email']))->exists();

            if ($exists) continue;

            $departmentId = match ($user['email']) {
                'higor@mail.test' => \App\Models\Department::where('title', 'Tecnologia da Informação')->value('id'),
                'guilherme@mail.test' => \App\Models\Department::where('title', 'Diretoria')->value('id'),
                'haaland@mail.test' => \App\Models\Department::where('title', 'Jurídico')->value('id'),
                default => null,
            };

            $data = [
                'department_id' => $departmentId,
                'name' => data_get($user, 'name'),
                'email' => data_get($user, 'email'),
                'status' => data_get($user, 'status'),
                'role' => data_get($user, 'role'),
                'email_verified_at' => data_get($user, 'email_verified_at'),
                'password' => 'password',
            ];

            User::create($data);

            $this->command->info("Usuário {$user['name']} criado com sucesso!");
        }
    }
}
