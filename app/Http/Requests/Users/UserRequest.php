<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Monolog\Handler\IFTTTHandler;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $user = $this->user();

        if ($user->hasRole('admin')) {
            return true;
        }



        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // id del usuario que estamos editando (null en create)
        $user = $this->route('user');
        $userIdToIgnore = $user ? $user->id : null;
        $rules = [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                // en create: unique normal
                // en update: ignora el user con id = $userId
                Rule::unique('users', 'email')->ignore($userIdToIgnore),
            ],

            'supervisor_id' => [
                // Es requerido SOLO si el 'roleType' enviado es 'employee'
                'required_if:roleType,employee',

                // Permitir null si no es requerido (ej. si es supervisor)
                'nullable',

                'integer',
                'exists:users,id'
            ],
            'is_active' => ['sometimes', 'boolean'],


            //'role' => ['nullable', 'string', Rule::in(['admin', 'supervisor', 'employee'])],
        ];

        // Password:
        // - POST (create): requerida
        // - PUT/PATCH (update): opcional, si viene se valida
        /*   if ($this->isMethod('post')) {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        } */

        return $rules;
    }

    // app/Http/Requests/Users/UserRequest.php

    // ... (después del método rules())

    public function messages(): array
    {
        return [
            // 1. Mensajes del campo 'name' (ejemplo)
            'name.required' => 'El nombre es obligatorio.',

            // 2. Mensajes del campo 'email'
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El formato del email es incorrecto (debe ser: ejemplo@dominio.com).',
            'email.unique' => 'Este email ya está registrado y pertenece a otro usuario.',

            // 3. Mensajes del campo 'supervisor_id'
            'supervisor_id.required' => 'Debe seleccionar un supervisor.',
            'supervisor_id.exists' => 'El supervisor seleccionado no es válido.',

            // 4. Mensajes del campo 'roleType' (si lo estás validando así)
            // 'roleType.required' => 'El tipo de rol es obligatorio.',

            // 5. Mensajes de 'password' (si aplica en creación)
            'password.required' => 'La contraseña es obligatoria en la creación.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ];
    }
}
