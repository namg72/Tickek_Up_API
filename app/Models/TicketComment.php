<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use  App\Models\Ticket;
use  App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketComment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'message',
        'ticket_id',
        'user_id',
        'deleted_at',
        'deleted_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
