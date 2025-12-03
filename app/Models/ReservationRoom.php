<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'room_id',
        'quantity',
        'nights',
        'price_per_night',
        'subtotal',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
