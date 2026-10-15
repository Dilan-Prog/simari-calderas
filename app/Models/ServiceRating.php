<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Calificación de un servicio por el cliente, una por reporte de servicio firmado.
 * El nombre del cliente es un snapshot del reporte/servicio (no lo captura el cliente).
 */
class ServiceRating extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'service_report_id', 'service_page_id', 'customer_id', 'customer_name', 'rating', 'survey_card_id',
        'card_label', 'comment', 'status', 'submitted_at', 'service_page_review_id',
    ];

    protected $casts = ['rating' => 'integer', 'submitted_at' => 'datetime'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }

    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(SurveyCard::class, 'survey_card_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ServiceRatingAnswer::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ServicePageReview::class, 'service_page_review_id');
    }

    public function scopeSubmitted($q)
    {
        return $q->where('status', self::STATUS_SUBMITTED);
    }
}
