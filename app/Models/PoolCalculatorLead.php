<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolCalculatorLead extends Model
{
    public const STATUSES = ['nuevo', 'contactado', 'cotizado', 'descartado'];

    protected $fillable = [
        'ref', 'visitor_uuid', 'home_section_id', 'payload', 'status', 'matched_quote_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function adVisit()
    {
        return $this->belongsTo(AdVisit::class, 'visitor_uuid', 'visitor_uuid');
    }

    public function homeSection()
    {
        return $this->belongsTo(HomeSection::class);
    }

    public function matchedQuote()
    {
        return $this->belongsTo(Quote::class, 'matched_quote_id');
    }
}
