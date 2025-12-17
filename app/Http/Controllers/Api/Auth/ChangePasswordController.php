<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;

class ChangePasswordController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user(); // usuario autenticado por Sanctum

        $request->validate([
            'current_password'      => ['required'],
            'new_password'              => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Comprobar que la contraseña actual es correcta
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        // Guardar nueva contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ], 200);
    }
}
