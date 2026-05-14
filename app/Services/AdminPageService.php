<?php

namespace App\Services;

use App\Models\Koperasi;
use App\Models\Sarpras;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminPageService
{
    public function dashboard(): array
    {
        return [
            'users' => User::query()->count(),
            'koperasis' => Koperasi::query()->count(),
            'sarprases' => Sarpras::query()->count(),
            'assigned_sarprases' => Koperasi::query()->has('sarprases')->count(),
        ];
    }

    public function createUser(array $data): User
    {
        return User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);
    }

    public function updateUser(User $user, array $data): User
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user;
    }
}
