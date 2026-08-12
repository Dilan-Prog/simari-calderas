<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'list_id',
        'status',
        'scheduled_at',
        'sent_at',
        'ab_variant_of',
        'ab_split_percent',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function list()
    {
        return $this->belongsTo(EmailList::class);
    }

    public function abVariant()
    {
        return $this->belongsTo(EmailCampaign::class, 'ab_variant_of');
    }

    public function sends()
    {
        return $this->hasMany(EmailSend::class, 'email_campaign_id');
    }

    public function openRate(): float
    {
        $total = $this->sends()->count();

        if ($total === 0) {
            return 0.0;
        }

        $opened = $this->sends()->whereNotNull('opened_at')->count();

        return round(($opened / $total) * 100, 2);
    }

    public function clickRate(): float
    {
        $total = $this->sends()->count();

        if ($total === 0) {
            return 0.0;
        }

        $clicked = $this->sends()->whereNotNull('clicked_at')->count();

        return round(($clicked / $total) * 100, 2);
    }
}
