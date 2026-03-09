<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'rating',
        'comment',
        'ai_generated_text',
        'status',
        'gender',
        'age',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
