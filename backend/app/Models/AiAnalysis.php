<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalysis extends Model
{
    protected $table = 'ai_analysis';
    protected $primaryKey = 'analysis_id';

    protected $fillable = [
        'evidence_id',
        'category',
        'severity',
        'reason',
        'regulation_reference',
        'confidence'
    ];
}
