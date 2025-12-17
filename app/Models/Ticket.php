<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TicketComment;
use App\Models\TicketCategory;
use App\Models\User;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'supervisor_id',
        'title',
        'description',
        'uri',
        'category_id',
        'amount',
        'iva_amount',
        'total_amount',
        'finalized_by_admin'

    ];



    //Evitamos que la bd nos devuelva 0 ó 1 como string
    protected $casts = [
        'needs_revision' => 'boolean',
        'finalized_by_admin' => 'boolean',
        'amount'              => 'decimal:2',
        'iva_amount'          => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'finalized_by_admin'  => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }


    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }
    public function category()

    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
