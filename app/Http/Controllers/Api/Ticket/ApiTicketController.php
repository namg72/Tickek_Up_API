<?php


namespace App\Http\Controllers\Api\Ticket;


use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;

class ApiTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['user:id,name', 'category:id,name', 'supervisor:id,name']);

        $user = $request->user();

        //Filtro por tipo de usuario para traernos los ticket de los empleados
        if ($user->hasRole('admin')) {
        } elseif ($user->hasRole('supervisor')) {
            $employeeIds = $user->employees->pluck('id');

            $query->whereIn('user_id', $employeeIds);
        } else {
            $query->where('user_id', $user->id);
        }

        //ticket por categoria 
        $totalTickets = (clone $query)->count();

        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $reviewCount = (clone $query)->where('status', 'review')->count();
        $approvedCount = (clone $query)->where('status', 'approved')->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();
        //Filtrado por año


        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        //Filtrado por mes
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        //SubFiltrado por empleados
        if ($request->filled('user_id')) {
            if ($user->hasRole('admin')) {
                $query->where('user_id', $request->user_id);
            } elseif ($user->hasRole('supervisor')) {
                // supervisor solo puede filtrar dentro de sus empleados
                if ($user->employees->pluck('id')->contains($request->user_id)) {
                    $query->where('user_id', $request->user_id);
                }
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10);

        $supervisor = User::find($user->supervisor_id);


        $years = Ticket::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        if ($user->hasRole('admin')) {
            $users = User::all(['id', 'name']);
        } elseif ($user->hasRole('supervisor')) {
            $users = $user->employees()->get(['id', 'name']);
        } else {
            $users = collect([$user]);
        }
        return response()->json([
            'tickets' => $tickets,
            'user_supervisor' => $supervisor,
            'filters' => [
                'year' => $request->year,
                'month' => $request->month,
                'user_id' => $request->user_id,
            ],
            'years' => $years,
            'allUser' => $users,
            'currentUser' => $user,
            'ticketsCounts' => [
                'totalTickets' => $totalTickets,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'review' => $reviewCount,
                'rejected' => $rejectedCount,

            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketRequest $request)
    {


        $user = $request->user();

        if (!$user->hasRole('employee')) {
            return response()->json([
                'message' => "No está autorizado a subir un nuevo gasto"
            ], 403);
        }

        $uploadFile = $request->file('image');

        if (!$uploadFile) {
            return response()->json([
                'message' => 'Imagen de ticket requerida'
            ], 422);
        }

        $extension = $uploadFile->getClientOriginalExtension();

        $uri = $user->id . '-' . now()->format('YmdHis') . '.' . $extension;

        $path = $uploadFile->storeAs(
            'tickets',
            $uri,
            'public'
        );


        $total = $request->total_amount;
        $base = round($total / 1.21, 2);
        $iva  = round($total - $base, 2);

        $tickets = [
            'user_id' => $user->id,
            'supervisor_id' => $user->supervisor_id,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'uri' => $path,
            'amount' => $base,
            'iva_amount' => $iva,
            'total_amount' => $total,

        ];
        try {
            $ticket = Ticket::create($tickets);
        } catch (\Throwable $e) {

            // si la inserción en BD falla, borramos la imagen que acabamos de subir
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'message' => 'Error al guardar el ticket en la base de datos',
            ], 500);
        }

        return response()->json([
            'message' => 'Gasto subido correctamente',
            'ticket' => $ticket
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ticket = Ticket::with(['comments.user', 'user', 'category:id,name', 'supervisor:id,name'])->findOrFail($id);
        $categories = TicketCategory::all();



        return response()->json([

            'categories' => $categories,
            'ticket' => $ticket

        ], 200);
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
    public function update(TicketRequest $request, string $id)
    {


        $ticket = Ticket::find($id);



        $uploadFile = $request->file('image');

        if (!$ticket) {
            return response()->json([
                'message' => 'Gasto no encontrado',
            ], 404);
        }

        $uri = $ticket->uri;



        if ($uploadFile !== null) {

            if (!empty($ticket->uri) &&  Storage::disk('public')->exists($ticket->uri)) {
                Storage::disk('public')->delete($ticket->uri);
            }
            $extension = $uploadFile->getClientOriginalExtension();
            $uri = $ticket->user_id . '-' . now()->format('YmdHis') . '.' . $extension;

            $path = $uploadFile->storeAs(
                'tickets',
                $uri,
                'public'


            );



            $uri = $path;
        }



        try {

            $total = $request->total_amount;
            $base = round($total / 1.21, 2);
            $iva  = round($total - $base, 2);

            $ticket->fill([
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'uri' => $uri,
                'total_amount' => $total,
                'amount' => $base,
                'iva_amount' => $iva,

            ]);

            $ticket->save();

            return response()->json([
                'message' => 'Gasto actualizado correctamente',
                'ticket' => $ticket
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                'message' => 'Error al actualizar el gasto',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'message' => 'El gasto que intenta borrar no existe '
            ], 404);
        }

        try {
            $ticket->delete();
            if (Storage::disk('public')->exists($ticket->uri)) {
                Storage::disk('public')->delete($ticket->uri);
            }
            return response()->json([
                'message' => 'El gasto ha sido elmiinado '
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ha ocurrido un error al eliminar el gasto '
            ], 500);
        };
    }

    public function ticketStatus(Request $request, string $id)
    {
        $user = $request->user();

        // Estados desde config
        $statusPending  = config('constants.ticket_statuses.pending');
        $statusApproved = config('constants.ticket_statuses.approved');
        $statusRejected = config('constants.ticket_statuses.rejected');

        // Solo supervisor o admin pueden cambiar el estado
        if (! $user->hasAnyRole(['supervisor', 'admin'])) {
            return response()->json([
                'message' => 'No estás autorizado para cambiar el estado del ticket.',
            ], 403);
        }

        // Buscar ticket
        $ticket = Ticket::find($id);

        if (! $ticket) {
            return response()->json([
                'message' => 'Ticket no encontrado.',
            ], 404);
        }

        // Validar datos de entrada
        $validated = $request->validate([
            'action'  => 'required|string|in:approve,reject,request_revision',
            'comment' => 'nullable|string|max:1000',
        ]);

        $action  = $validated['action'];
        $comment = $validated['comment'] ?? null;

        // Si se pide "request_revision", obligamos a mandar comentario
        if ($action === 'request_revision' && empty($comment)) {
            return response()->json([
                'message' => 'El comentario es obligatorio cuando se solicita revisión.',
            ], 422);
        }

        // Supervisor NO puede tocarlo si ya está finalizado por admin
        if ($user->hasRole('supervisor') && $ticket->finalized_by_admin) {
            return response()->json([
                'message' => 'Este ticket ya ha sido finalizado por un administrador.',
            ], 403);
        }

        // LÓGICA DE CAMBIO SEGÚN ROL Y ACCIÓN
        if ($user->hasRole('supervisor')) {

            // SUPERVISOR
            switch ($action) {
                case 'request_revision':
                    // Solo se puede pedir revisión si está pendiente
                    if ($ticket->status !== $statusPending) {
                        return response()->json([
                            'message' => 'Solo se puede solicitar revisión cuando el ticket está pendiente.',
                        ], 422);
                    }

                    $ticket->needs_revision      = true;
                    // status se mantiene en "pending"
                    // finalized_by_admin sigue false
                    break;

                case 'approve':
                    $ticket->status             = $statusApproved;
                    $ticket->needs_revision      = false;
                    // finalized_by_admin sigue false (decisión de supervisor)
                    break;

                case 'reject':
                    $ticket->status             = $statusRejected;
                    $ticket->needs_revision      = false;
                    // finalized_by_admin sigue false
                    break;
            }
        } elseif ($user->hasRole('admin')) {

            // ADMIN
            switch ($action) {
                case 'request_revision':
                    // El admin puede reabrir y pedir revisión
                    $ticket->status             = $statusPending;
                    $ticket->needs_revision      = true;
                    $ticket->finalized_by_admin = false;
                    break;

                case 'approve':
                    $ticket->status             = $statusApproved;
                    $ticket->needs_revision      = false;
                    $ticket->finalized_by_admin = true; // decisión final de admin
                    break;

                case 'reject':
                    // Incluye el caso: pending + needs_revision = true
                    $ticket->status             = $statusRejected;
                    $ticket->needs_revision      = false;
                    $ticket->finalized_by_admin = true; // decisión final de admin
                    break;
            }
        }

        // Guardar cambios del ticket
        $ticket->save();

        // Si viene comentario, lo guardamos como TicketComment
        if ($comment !== null && $comment !== '') {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
                'message'   => $comment,
            ]);
        }

        return response()->json([
            'message' => 'Estado del ticket actualizado correctamente.',
            'ticket'  => $ticket,
        ], 200);
    }

    public function image(Request $request, string $id)
    {
        $user = $request->user();

        $ticket = Ticket::find($id);

        if (! $ticket) {
            return response()->json([
                'message' => 'Ticket no encontrado',
            ], 404);
        }

        // 🔐 Permisos básicos:
        // - employee: solo su propio ticket
        if ($user->hasRole('employee') && $ticket->user_id !== $user->id) {
            return response()->json([
                'message' => 'No está autorizado para ver la imagen de este ticket',
            ], 403);
        }

        // - supervisor: solo tickets de sus empleados
        if ($user->hasRole('supervisor') && $ticket->supervisor_id !== $user->id) {
            return response()->json([
                'message' => 'No está autorizado para ver la imagen de este ticket',
            ], 403);
        }

        // - admin: puede verlo todo (no hacemos check extra)

        // Ruta física del archivo en storage/app/public/...
        $path = Storage::disk('public')->path($ticket->uri);

        if (! file_exists($path)) {
            return response()->json([
                'message' => 'Imagen no encontrada en el servidor',
            ], 404);
        }

        // ⬅️ Aquí el cambio: devolvemos el fichero directamente
        return response()->file($path);
    }
}
