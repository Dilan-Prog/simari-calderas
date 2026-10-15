<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRatingAnswer extends Model
{
    protected $fillable = ['service_rating_id', 'survey_question_id', 'question_label', 'score'];

    protected $casts = ['score' => 'integer'];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(ServiceRating::class, 'service_rating_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }
}
