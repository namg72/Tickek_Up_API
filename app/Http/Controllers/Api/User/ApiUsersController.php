<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiUsersController extends Controller
{
    /**
     * Listado de usuarios (admin / supervisor).
     */
    public function index(Request $request)
    {
        // Solo admin o supervisor pueden listar usuarios
        $this->authorize('viewAny', User::class);

        $authUser = $request->user();

        // Query base con relación roles cargada
        $query = User::with('roles');

        // Filtros según rol
        if ($authUser->hasRole('supervisor')) {

            // Supervisor: solo sus empleados
            $query->where('supervisor_id', $authUser->id);
        } elseif ($authUser->hasRole('admin')) {

            // Admin: puede filtrar por supervisor
            if ($request->filled('supervisor_id')) {
                $query->where('supervisor_id', $request->supervisor_id);
            }

            // Admin: puede filtrar por rol
            if ($request->filled('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role); // admin, supervisor, employee...
                });
            }
        }

        // Filtros comunes (admin y supervisor)

        // Activo / inactivo
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        // Nombre contiene
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Ejecutar consulta

        $users = $query->orderBy('id', 'asc')->paginate(10);

        // Formatear respuesta
        $users = $users->map(function ($user) {
            return [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'supervisor_id' => $user->supervisor_id,
                'is_active'     => $user->is_active,
                'role'          => $user->roles->pluck('name')->first(),
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * No se usa en API (formularios son del front).
     */
    public function create() {}

    /**
     * Crear un nuevo usuario.
     */
    public function store(UserRequest $request)
    {
        // Solo admin (policy: create)
        $this->authorize('create', User::class);

        // Datos validados por UserRequest
        $data = $request->validated();

        // Crear usuario
        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        // Asignar rol (Spatie)
        $user->assignRole($data['role']);

        // Cargar roles para la respuesta
        $user->load('roles');

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'supervisor_id' => $user->supervisor_id,
                'is_active'     => $user->is_active,
                'role'          => $user->roles->pluck('name')->first(),
            ],
        ], 201);
    }

    /**
     * Mostrar un usuario concreto.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'No existe ningún usuario con ese id',
            ], 404);
        }

        // Autorizar sobre el modelo concreto
        $this->authorize('view', $user);

        $user->load('roles');

        return response()->json([
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'supervisor_id' => $user->supervisor_id,
                'is_active'     => $user->is_active,
                'role'          => $user->roles->pluck('name')->first(),
            ],
        ], 200);
    }

    /**
     * No se usa en API (formularios son del front).
     */
    public function edit(string $id) {}

    /**
     * Actualizar un usuario existente.
     *
     * Aquí también usamos UserRequest, con reglas adaptadas para update
     * (idealmente en el propio UserRequest según método o creando un UpdateRequest).
     */
    public function update(UserRequest $request, string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        // Autorizar sobre ese usuario concreto
        $this->authorize('update', $user);

        // Datos ya validados
        $data = $request->validated();

        try {
            // Campos básicos
            $user->fill([
                'name'          => $data['name']        ?? $user->name,
                'email'         => $data['email']       ?? $user->email,
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'is_active'     => $data['is_active']   ?? $user->is_active,
            ]);

            // Password opcional: solo si viene en la request
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            // Actualizar rol (dejamos solo el rol indicado)
            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            $user->save();

            $user->load('roles');

            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'user'    => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'supervisor_id' => $user->supervisor_id,
                    'is_active'     => $user->is_active,
                    'role'          => $user->roles->pluck('name')->first(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al actualizar el usuario',
            ], 500);
        }
    }

    /**
     * Eliminar un usuario.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'El usuario que intenta borrar no existe',
            ], 404);
        }

        // Autorizar sobre el modelo (la policy ya evita borrar a uno mismo)
        $this->authorize('delete', $user);

        try {
            $user->delete();

            return response()->json([
                'message' => 'El usuario ha sido eliminado',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ha ocurrido un error al eliminar el usuario',
            ], 500);
        }
    }
}
