<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class ProcessSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()->where('role_hash', hmac_hash('customer'))->where('status', 'active')->get();

        if ($customers->isEmpty()) {
            throw new RuntimeException('Nenhum usuário com role "customer" foi encontrado.');
        }

        $categories = Category::query()->where('taxonomy', 'process')->where('status', 'active')->get();

        if ($categories->isEmpty()) {
            throw new RuntimeException('Nenhuma categoria com taxonomy "process" foi encontrada.');
        }

        $faker = fake();

        $statuses = [
            'draft',
            'awaiting-approval',
            'awaiting-approval',
            'awaiting-approval',
            'awaiting-approval',
            'awaiting-approval',
            'approved',
            'approved',
            'approved',
            'failed',
            'canceled',
        ];

        foreach (range(1, 200) as $i) {
            do {
                $reference = yearNumberRandom();
            } while (
                Process::query()->where('reference', $reference)->exists()
            );

            Process::create([
                'owner_id' => $customers->random()->id,
                'category_id' => $categories->random()->id,
                'reference' => $reference,
                'title' => Str::headline($faker->words(random_int(2, 5), true)),
                'description' => $faker->boolean(80) ? $faker->paragraph(random_int(1, 3)) : null,
                'status' => Arr::random($statuses),
                'sequential_signing' => $faker->boolean(),
                'sign_deadline_at' => $faker->boolean(70) ? now()->addDays(random_int(3, 30)) : null,
                'expires_at' => $faker->boolean(50) ? now()->addDays(random_int(30, 180)) : null,
            ]);
        }

        $this->command->info('100 processos criados com sucesso.');
    }
}
