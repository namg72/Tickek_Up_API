<?php

namespace App\Http\Controllers\Api\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;

class TicketImageController extends Controller
{
    public function show(Ticket $ticket)
    {
        if (!$ticket->uri || !Storage::disk('public')->exists($ticket->uri)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($ticket->uri)
        );
    }

    public function download(Ticket $ticket)
    {
        if (!$ticket->uri || !Storage::disk('public')->exists($ticket->uri)) {
            abort(404);
        }

        $path = Storage::disk('public')->path($ticket->uri);
        $filename = basename($path);

        return response()->download($path, $filename);
    }
}
