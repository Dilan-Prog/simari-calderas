<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSequenceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'order',
        'template_id',
        'delay_days',
    ];

    public function sequence()
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
