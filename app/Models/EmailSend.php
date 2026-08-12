<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_campaign_id',
        'email_sequence_step_id',
        'workflow_step_id',
        'customer_id',
        'tracking_token',
        'sent_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'opened_at'       => 'datetime',
        'clicked_at'      => 'datetime',
        'bounced_at'      => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmailSend $emailSend) {
            if (empty($emailSend->tracking_token)) {
                $emailSend->tracking_token = Str::random(32);
            }
        });
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function sequenceStep()
    {
        return $this->belongsTo(EmailSequenceStep::class, 'email_sequence_step_id');
    }

    public function workflowStep()
    {
        return $this->belongsTo(\App\Models\WorkflowStep::class, 'workflow_step_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function clicks()
    {
        return $this->hasMany(EmailLinkClick::class);
    }
}
