<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // GET: permitido para cualquiera que llegue hasta aquí
        if ($this->isMethod('get')) {
            return true;
        }

        // Para el resto de métodos necesitamos usuario
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Solo admin puede hacer POST o PUT (y si quieres, PATCH/DELETE también)
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            return $user->hasRole('admin');
        }

        // Cualquier otro método lo bloqueas por defecto
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],

        ];
    }
    public function messages(): array
    {
        return [
            // 1. Mensajes del campo 'name'
            'name.required' => 'El nombre es obligatorio.',


        ];
    }
}
