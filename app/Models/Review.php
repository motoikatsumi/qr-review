<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'rating',
        'comment',
        'ai_generated_text',
        'status',
        'gender',
        'age',
        'visit_type',
        'item',
        'persona',
    ];

    protected $casts = [
        'persona' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
