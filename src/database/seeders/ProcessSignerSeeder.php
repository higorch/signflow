<?php

namespace Database\Seeders;

use App\Models\Process;
use App\Models\ProcessSigner;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProcessSignerSeeder extends Seeder
{
    public function run(): void
    {
        $processes = Process::all();

        $signers = User::query()->where('role_hash', hmac_hash('signer'))->where('status', 'active')->get();

        if ($signers->isEmpty()) {
            throw new RuntimeException('Nenhum usuário com role "signer" foi encontrado.');
        }

        foreach ($processes as $process) {
            $users = $signers->random(min(random_int(1, 4), $signers->count()))->values();

            foreach ($users as $sort => $user) {

                $status = match ($process->status) {
                    'approved' => 'signed',
                    'failed' => fake()->boolean(50) ? 'rejected' : 'awaiting-signature',
                    'canceled' => 'rejected',
                    default => 'awaiting-signature',
                };

                $actionAt = in_array($status, ['signed', 'rejected']) ? fake()->dateTimeBetween($process->created_at, $process->updated_at) : null;

                ProcessSigner::create([
                    'user_id' => $user->id,
                    'process_id' => $process->id,
                    'status' => $status,
                    'sort' => $sort + 1,
                    'action_at' => $actionAt,
                    'rejection_reason' => $status === 'rejected' ? fake()->sentence() : null,
                ]);
            }
        }

        $this->command->info('Signatários criados com sucesso.');
    }
}
