<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['old_path', 'new_path', 'status_code'];

    /**
     * Records old->new, collapsing chains so a lookup is never more than one
     * hop: if $newPath is itself already some redirect's old_path, follows
     * it to the real final destination before saving; and repoints any
     * existing redirect that pointed at $oldPath so it lands on that same
     * final destination directly (A->B->C becomes A->C and B->C both).
     */
    public static function record(string $oldPath, string $newPath): self
    {
        $oldPath = '/' . ltrim($oldPath, '/');
        $newPath = '/' . ltrim($newPath, '/');

        $finalTarget = $newPath;
        $seen = [];
        while ($next = static::where('old_path', $finalTarget)->value('new_path')) {
            if (isset($seen[$finalTarget])) {
                break; // cycle guard
            }
            $seen[$finalTarget] = true;
            $finalTarget = $next;
        }

        $redirect = static::updateOrCreate(
            ['old_path' => $oldPath],
            ['new_path' => $finalTarget, 'status_code' => 301]
        );

        static::where('new_path', $oldPath)->update(['new_path' => $finalTarget]);

        return $redirect;
    }

    /**
     * Shared lookup used by Route::fallback() (no route pattern matched at
     * all) and by controllers whose route DID match but the slug lookup
     * missed (e.g. CatalogController::category(), CollectionController::show()).
     */
    public static function resolve(string $path): ?self
    {
        $path = '/' . ltrim($path, '/');
        return static::where('old_path', $path)->first();
    }
}
