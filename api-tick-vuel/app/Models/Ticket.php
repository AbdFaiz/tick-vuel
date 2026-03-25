<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'title',
        'description',
        'status',
        'priority',
        'attachment',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function ticketReplies() {
        return $this->hasMany(TicketReply::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
