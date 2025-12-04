<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory; // tambahkan ini

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory; // tambahkan HasFactory di sini

    protected $casts = [
        'expires_at'   => 'datetime',
        'expired_at'   => 'datetime',
        'valid_until'  => 'datetime',
        'expiry_date'  => 'datetime',
        'approved'     => 'boolean',
        'activated'    => 'boolean',
        'active'       => 'boolean',
        'is_admin'     => 'boolean',
    ];

    public function expiryDate(): ?Carbon
    {
        $candidates = [
            $this->expires_at,
            $this->expired_at,
            $this->valid_until,
            $this->expiry_date,
        ];

        foreach ($candidates as $dt) {
            if ($dt instanceof Carbon) return $dt;
            if ($dt) {
                try { return Carbon::parse($dt); } catch (\Throwable $e) {}
            }
        }
        return null;
    }

    public function isExpired(): bool
    {
        $exp = $this->expiryDate();
        return $exp ? now()->greaterThan($exp) : false;
    }

    public function isAdmin(): bool
    {
        $role = strtolower((string)($this->role ?? ''));
        return $role === 'admin' || (bool)($this->is_admin ?? false);
    }
}