<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'is_active',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function steps()
    {
        return $this->hasMany(EmailSequenceStep::class, 'sequence_id')->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(EmailSequenceEnrollment::class, 'sequence_id');
    }
}
