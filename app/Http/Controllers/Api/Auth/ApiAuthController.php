<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // 1. Validación manual rápida para probar
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => "El email o la contraseña no coinciden"
                ], 401);
            }

            // 2. Generar token
            $token = $user->createToken('auth_token')->plainTextToken;

            // 3. Obtener el rol de forma segura
            $role = $user->getRoleNames()->first() ?? 'no_role';

            return response()->json([
                "token" => $token,
                "token_type" => "Bearer",
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "role" => $role
                ]
            ]);
        } catch (\Exception $e) {
            // Si da 500, esto nos dirá por qué en storage/logs/laravel.log

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed',],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;



        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
