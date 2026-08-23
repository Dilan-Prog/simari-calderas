<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'menu';
    }

    protected $fillable = ['name', 'location', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function rootItems()
    {
        return $this->items()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }]);
    }
}
