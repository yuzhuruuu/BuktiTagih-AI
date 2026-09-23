<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    protected $table = 'evidence';
    protected $primaryKey = 'evidence_id';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_type',
        'upload_time',
        'hash_file',
        'file',
    ];

    public function aiAnalysis()
    {
        return $this->hasMany(AiAnalysis::class, 'evidence_id', 'evidence_id');
    }
}
