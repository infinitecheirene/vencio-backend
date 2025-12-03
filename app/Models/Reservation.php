<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_number',
        'venue_id',
        'event_type',
        'event_date',
        'check_in_date',
        'check_out_date',
        'nights',
        'attendees',
        'needs_rooms',
        'organization',
        'event_name',
        'contact_person',
        'position',
        'email',
        'phone',
        'details',
        'venue_total',
        'rooms_total',
        'total_amount',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'needs_rooms' => 'boolean',
        'venue_total' => 'decimal:2',
        'rooms_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            $reservation->reservation_number = self::generateReservationNumber();
        });
    }

    public static function generateReservationNumber()
    {
        $prefix = 'VG';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        return $prefix . $date . $random;
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function reservationRooms()
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
