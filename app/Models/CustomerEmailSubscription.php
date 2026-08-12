<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerEmailSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'subscribed',
        'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed'      => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function isSubscribed(int $customerId): bool
    {
        $subscription = static::where('customer_id', $customerId)->first();

        if (! $subscription) {
            return true;
        }

        return (bool) $subscription->subscribed;
    }
}
