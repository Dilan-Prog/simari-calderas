<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'is_shared',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reports()
    {
        return $this->belongsToMany(Report::class, 'dashboard_reports')
            ->withPivot('position')
            ->orderBy('dashboard_reports.position');
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'dashboard_shares');
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->is_shared) {
            return true;
        }

        if ($user->id === $this->owner_id) {
            return true;
        }

        return $this->sharedWith()->where('users.id', $user->id)->exists();
    }
}
