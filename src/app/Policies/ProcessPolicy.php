<?php

namespace App\Policies;

use App\Models\Process;
use App\Models\User;

class ProcessPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Process $process): bool
    {
        return $this->ownsProcess($user, $process);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Process $process): bool
    {
        return $this->ownsProcess($user, $process);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Process $process): bool
    {
        return $this->ownsProcess($user, $process);
    }

    /**
     * Determine whether the user owns the process.
     */
    private function ownsProcess(User $user, Process $process): bool
    {
        if ($user->role_hash === hmac_hash('customer')) {
            $ownerIds = $user->internalUsers()->pluck('users.id')->push($user->id);
            return $ownerIds->contains($process->owner_id);
        }

        return $process->owner_id === $user->id;
    }
}
