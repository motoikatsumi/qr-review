<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePostTemplate extends Model
{
    protected $fillable = [
        'store_id',
        'template_text',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
