<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'metric_type',
        'target_value',
        'period_start',
        'period_end',
        'owner_id',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function progress(): array
    {
        $current = 0;

        switch ($this->metric_type) {
            case 'deals_won_amount':
                $current = Deal::where('status', 'won')
                    ->whereBetween('closed_at', [$this->period_start, $this->period_end])
                    ->when($this->owner_id, fn ($q) => $q->where('owner_id', $this->owner_id))
                    ->sum('amount');
                break;

            case 'deals_won_count':
                $current = Deal::where('status', 'won')
                    ->whereBetween('closed_at', [$this->period_start, $this->period_end])
                    ->when($this->owner_id, fn ($q) => $q->where('owner_id', $this->owner_id))
                    ->count();
                break;

            case 'emails_sent':
                $current = EmailSend::whereBetween('sent_at', [$this->period_start, $this->period_end])
                    ->count();
                break;

            default:
                $current = 0;
        }

        $target = (float) $this->target_value;
        $percent = $target > 0 ? min(100, ($current / $target) * 100) : 0;

        return [
            'current' => (float) $current,
            'target'  => $target,
            'percent' => (float) $percent,
        ];
    }
}
