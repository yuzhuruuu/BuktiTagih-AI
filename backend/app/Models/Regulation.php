<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    protected $table = 'regulation';
    protected $primaryKey = 'regulation_id';

    protected $fillable = [
        'source',
        'category',
        'topic',
        'title',
        'page',
        'content',
        'keywords',
    ];

    protected $casts = [
        'keywords' => 'array', // Jika keywords disimpan sebagai JSON
    ];
}
