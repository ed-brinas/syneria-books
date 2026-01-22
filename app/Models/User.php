<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_hash', 
        'password',
        'tenant_id',
        'first_name',
        'last_name',
        'phone',
        'position',
        'role', 
        'status',
        'profile_photo_path',
        'mfa_enabled',
        'mfa_secret',
        'mfa_recovery_codes',   
        'last_login_at',        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email' => 'encrypted', 
            'first_name' => 'encrypted', 
            'last_name' => 'encrypted',
            'name' => 'encrypted',
            'phone' => 'encrypted', 
            'last_login_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'mfa_recovery_codes' => 'encrypted:array',
            'mfa_secret' => 'encrypted',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            if ($user->isDirty('email') && !empty($user->email)) {
                $user->email_hash = hash_hmac('sha256', strtolower($user->email), config('app.key'));
            }
        });
    }

    // Helper to get photo URL or default placeholder
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            // Generates the full S3 URL (e.g., https://bucket.s3.region.amazonaws.com/path)
            return Storage::disk('s3')->url($this->profile_photo_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->first_name . ' ' . $this->last_name).'&color=7F9CF5&background=EBF4FF';
    }
}