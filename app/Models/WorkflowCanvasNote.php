<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCanvasNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'text',
        'position_x',
        'position_y',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
