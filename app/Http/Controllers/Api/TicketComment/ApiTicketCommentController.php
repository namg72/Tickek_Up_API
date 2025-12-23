<?php

namespace App\Http\Controllers\Api\TicketComment;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;


class ApiTicketCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $id)
    {
        $user = $request->user();

        $hasPermission = true;

        $ticket = Ticket::find($id);

        $comments = TicketComment::where('ticket_id', $id)->get();

        if (!$ticket) {
            return response()->json([
                'message'  => 'No existe ticket con este id.',

            ], 404);
        }


        if ($user->hasRole('employee') && ($user->id !==  $ticket->user_id)) {
            $hasPermission = false;
        }

        if ($user->hasRole('supervisor') && ($user->id !==  $ticket->supervisor_id)) {
            $hasPermission = false;
        }

        if (!$hasPermission) {

            return response()->json([
                'message'  => 'No tienes permisos para ver los comentarios de este ticket.',

            ], 403);
        }

        if ($comments->isEmpty()) {
            return response()->json([
                'message'  => 'Este ticket no tiene comentarios.',
                'comments' => [],
            ], 200);
        }

        return response()->json([
            'comments' => $comments,
        ], 200);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $id)

    {
        // 1. Validar el mensaje
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        // 2. Buscar el ticket
        $ticket = Ticket::find($id);

        if (! $ticket) {
            return response()->json([
                'message' => 'Ticket no encontrado',
            ], 404);
        }

        // 3. Usuario autenticado
        $user = $request->user();

        // 4. Comprobar permisos:
        // - employee → solo sus tickets
        // - supervisor → solo tickets de sus empleados
        // - admin → pasa porque no entra en ninguna condición
        if (
            ($user->hasRole('employee') && $user->id !== $ticket->user_id) ||
            ($user->hasRole('supervisor') && $user->id !== $ticket->supervisor_id)
        ) {
            return response()->json([
                'message' => 'No tienes permiso para crear un comentario',
            ], 403);
        }

        try {
            // 5. Crear el comentario
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
                'message'   => $validated['message'],
            ]);

            // 6. Recargar ticket con comentarios y usuario (si quieres devolverlo completo)
            $ticket->load(['comments.user', 'user']);

            return response()->json([
                'message' => 'Comentario creado correctamente',
                'comment' => $comment,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ha ocurrido un error al intentar guardar el comentario',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // 1. Buscar comentario
        $comment = TicketComment::find($id);

        if (! $comment) {
            return response()->json([
                'message' => 'Comentario no encontrado.',
            ], 404);
        }
        // Ya está eliminado (soft delete)
        if ($comment->deleted_at) {
            return response()->json([
                'message' => 'Este comentario ya está eliminado y no se puedse editar.',
            ], 410); // o 200 si prefieres
        }

        $canUpdate = false;

        if ($user->id === $comment->user_id) {
            $canUpdate = true;
        }



        $data = $request->validate([
            'message' => [
                'required',
                'string',
                'max:1000',

            ],
        ]);
        if ($canUpdate) {

            $comment->update(['message' => $data['message']]);

            return response()->json([
                'message' => 'Comentario editado correctamente',
                'comment' => $comment
            ], 200);
        } else {
            return response()->json([
                'message' => 'No tienes permiso para editar este comentario',
            ], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        // 1. Buscar comentario
        $comment = TicketComment::find($id);

        if (! $comment) {
            return response()->json([
                'message' => 'Comentario no encontrado.',
            ], 404);
        }

        // Ya está eliminado (soft delete)
        if ($comment->deleted_at) {
            return response()->json([
                'message' => 'Este comentario ya está eliminado.',
            ], 410); // o 200 si prefieres
        }

        // 2. Buscar ticket del comentario
        // Si tienes relación $comment->ticket, mejor usarla:
        $ticket = Ticket::find($comment->ticket_id);

        if (! $ticket) {
            return response()->json([
                'message' => 'Ticket asociado al comentario no encontrado.',
            ], 404);
        }

        // 3. Permiso para VER el ticket (igual que en tu index)
        $canViewTicket = true;

        if ($user->hasRole('employee') && $user->id !== $ticket->user_id) {
            $canViewTicket = false;
        }

        if ($user->hasRole('supervisor') && $user->id !== $ticket->supervisor_id) {
            $canViewTicket = false;
        }

        // Admin puede ver todos los tickets, no le bloqueamos aquí
        if (! $canViewTicket && ! $user->hasRole('admin')) {
            return response()->json([
                'message' => 'No tienes permisos para ver este ticket.',
            ], 403);
        }

        // 4. Lógica de borrado según rol

        $canDelete  = false;
        $within24h  = $comment->created_at->gt(now()->subDay()); // creado hace menos de 24h
        $isAuthor   = $comment->user_id === $user->id;

        $isTicketSupervisor = $ticket->supervisor_id === $user->id;

        // ADMIN: puede borrar siempre
        if ($user->hasRole('admin')) {
            $canDelete = true;
        }
        // SUPERVISOR:
        // - puede borrar sus propios comentarios
        // - y los comentarios de tickets donde es supervisor
        // - no puede borrar comentarios del admin u otor supervisor
        elseif ($user->hasRole('supervisor')) {

            $author = $comment->user;
            $authorIsAdmin      = $author && $author->hasRole('admin');
            $authorIsSupervisor = $author && $author->hasRole('supervisor');
            $authorIsEmployee   = $author && $author->hasRole('employee');

            if ($isAuthor) {
                // si el comentario es suyo, siempre puede borrarlo
                $canDelete = true;
            } elseif ($authorIsAdmin || $authorIsSupervisor) {
                // no puede borrar comentarios de admin ni de otros supervisores
                $canDelete = false;
            } elseif ($isTicketSupervisor && $authorIsEmployee) {
                // puede borrar comentarios de empleados en tickets que supervisa
                $canDelete = true;
            }
        }
        // EMPLOYEE:
        // - solo puede borrar comentarios suyos
        // - y solo dentro de 24h desde que se crearon
        elseif ($user->hasRole('employee')) {
            if ($isAuthor && $within24h) {
                $canDelete = true;
            }
        }

        if (! $canDelete) {
            $message = 'No tienes permiso para eliminar este comentario.';

            // Mensaje más concreto para el caso típico de ventana de 24h
            if ($user->hasRole('employee') && $isAuthor && ! $within24h) {
                $message = 'Ya no puedes eliminar este comentario porque han pasado más de 24 horas desde que se creó.';
            }

            return response()->json([
                'message' => $message,
            ], 403);
        }

        // 5. Borrado lógico: marcamos quién lo borra y cuándo

        $comment->deleted_by = $user->id;
        $comment->deleted_at = now();
        $comment->save();

        // Si usas SoftDeletes en el modelo:
        // $comment->deleted_by = $user->id;
        // $comment->save();
        // $comment->delete();

        return response()->json([
            'message' => 'Comentario eliminado correctamente.',
        ], 200);
    }
}
