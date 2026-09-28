<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtractedEntity extends Model
{
    protected $table = 'extracted_entity';
    protected $primaryKey = 'entity_id';

    protected $fillable = [
        'evidence_id',
        'entity_type',
        'entity_value',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
    ];

    public function evidence()
    {
        return $this->belongsTo(Evidence::class, 'evidence_id', 'evidence_id');
    }
}
