<?php
// app/Models/Booking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'room_name',
        'check_in',
        'check_out',
        'guests',
        'price_per_night',
        'total_price',
        'nights',
        'status',
        'special_requests',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'price_per_night' => 'decimal:2',
        'total_price' => 'decimal:2',
        'guests' => 'integer',
        'nights' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('check_in', '>', now())
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeActive($query)
    {
        return $query->where('check_in', '<=', now())
                    ->where('check_out', '>=', now())
                    ->where('status', 'confirmed');
    }

    // Accessors
    public function getIsUpcomingAttribute(): bool
    {
        return $this->check_in->isFuture() && in_array($this->status, ['pending', 'confirmed']);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->check_in->isPast() 
            && $this->check_out->isFuture() 
            && $this->status === 'confirmed';
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->check_in->isFuture() 
            && in_array($this->status, ['pending', 'confirmed'])
            && $this->check_in->diffInHours(now()) > 24;
    }
}