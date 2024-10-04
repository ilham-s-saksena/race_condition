<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return false;
        }
        $user = Auth::user();
        return $user->createToken('AuthToken')->plainTextToken;
    }
}
