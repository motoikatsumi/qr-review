<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class StoreWordPress extends Model
{
    protected $table = 'store_wordpress';

    protected $fillable = [
        'store_id',
        'install_path',
        'site_url',
        'admin_url',
        'db_name',
        'db_mode',
        'table_prefix',
        'admin_username',
        'admin_password_encrypted',
        'admin_email',
        'app_password_encrypted',
        'status',
        'installed_plugins',
        'connected_services',
        'last_error',
        'installed_at',
    ];

    protected $casts = [
        'installed_plugins' => 'array',
        'connected_services' => 'array',
        'installed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function setAdminPasswordAttribute(string $password): void
    {
        $this->attributes['admin_password_encrypted'] = Crypt::encryptString($password);
    }

    public function getAdminPasswordAttribute(): ?string
    {
        $enc = $this->attributes['admin_password_encrypted'] ?? null;
        if (!$enc) return null;
        try {
            return Crypt::decryptString($enc);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setAppPasswordAttribute(?string $password): void
    {
        $this->attributes['app_password_encrypted'] = $password ? Crypt::encryptString($password) : null;
    }

    public function getAppPasswordAttribute(): ?string
    {
        $enc = $this->attributes['app_password_encrypted'] ?? null;
        if (!$enc) return null;
        try {
            return Crypt::decryptString($enc);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
