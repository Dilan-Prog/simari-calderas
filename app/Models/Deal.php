<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function logEntityType(): string
    {
        return 'deal';
    }

    protected $fillable = [
        'folio',
        'pipeline_id',
        'pipeline_stage_id',
        'name',
        'amount',
        'currency',
        'expected_close_date',
        'closed_at',
        'owner_id',
        'customer_id',
        'company_snapshot',
        'contact_snapshot_name',
        'contact_snapshot_email',
        'contact_snapshot_phone',
        'source',
        'status',
        'lost_reason',
        'notes',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'closed_at'           => 'datetime',
        'amount'               => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Deal $deal) {
            if (empty($deal->folio)) {
                $deal->folio = self::generateFolio();
            }
        });
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Customer::class, 'deal_contacts')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function quotes()
    {
        return $this->belongsToMany(Quote::class, 'deal_quote')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'deal_tag')
            ->withTimestamps();
    }

    public function stageHistory()
    {
        return $this->hasMany(DealStageHistory::class)->orderByDesc('moved_at');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeStalled($query, int $days = 14)
    {
        return $query->open()->whereHas('stageHistory', function ($q) use ($days) {
            $q->whereRaw('deal_stage_history.moved_at = (select max(dsh2.moved_at) from deal_stage_history dsh2 where dsh2.deal_id = deal_stage_history.deal_id)')
              ->where('moved_at', '<', now()->subDays($days));
        });
    }

    // Auto-generate folio: NEG-YYYY-XXXX
    //
    // Hallazgo de QA (integración Fase 11-15): Deal usa SoftDeletes, así que
    // el scope global excluye por defecto los negocios eliminados de esta
    // consulta. Sin withTrashed(), max('folio') "olvida" el folio más alto
    // ya usado por un Deal borrado (soft delete) y genera el mismo folio de
    // nuevo — la UNIQUE de la columna lo rechaza con un 500 real en cuanto
    // se borra y luego se crea otro negocio dentro del mismo año. Con
    // withTrashed() la secuencia sigue avanzando sin reutilizar folios ya
    // emitidos, igual que se espera de un consecutivo tipo folio/PO.
    public static function generateFolio(): string
    {
        $year = date('Y');
        $last = static::withTrashed()->whereYear('created_at', $year)->max('folio');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;
        return 'NEG-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
