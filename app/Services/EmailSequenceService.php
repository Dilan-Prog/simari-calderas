<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\EmailSend;
use App\Models\EmailSequence;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailSequenceStep;
use App\Jobs\SendMarketingEmailJob;
use Illuminate\Support\Facades\DB;

class EmailSequenceService
{
    /**
     * Inscribe a un customer en una sequence. Si ya existe un enrollment
     * activo para ese customer+sequence, lo devuelve tal cual en lugar de
     * crear uno duplicado.
     */
    public function enroll(EmailSequence $sequence, Customer $customer): EmailSequenceEnrollment
    {
        $existing = EmailSequenceEnrollment::where('sequence_id', $sequence->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return $existing;
        }

        return EmailSequenceEnrollment::create([
            'sequence_id'  => $sequence->id,
            'customer_id'  => $customer->id,
            'current_step' => 0,
            'status'       => 'active',
            'next_send_at' => now(),
        ]);
    }

    /**
     * Avanza el enrollment al siguiente step de la sequence: envía el
     * correo del step actual y programa el siguiente next_send_at. Si no
     * hay más steps, marca el enrollment como completed.
     */
    public function advance(EmailSequenceEnrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $step = EmailSequenceStep::where('sequence_id', $enrollment->sequence_id)
                ->where('order', $enrollment->current_step + 1)
                ->first();

            if (!$step) {
                $enrollment->status = 'completed';
                $enrollment->next_send_at = null;
                $enrollment->save();

                return;
            }

            $send = EmailSend::create([
                'email_sequence_step_id' => $step->id,
                'customer_id'             => $enrollment->customer_id,
            ]);

            SendMarketingEmailJob::dispatch($send);

            $nextStep = EmailSequenceStep::where('sequence_id', $enrollment->sequence_id)
                ->where('order', $step->order + 1)
                ->first();

            $enrollment->current_step = $step->order;
            $enrollment->next_send_at = $nextStep
                ? now()->addDays($nextStep->delay_days)
                : null;
            $enrollment->save();
        });
    }

    /**
     * Recorre todos los enrollments activos cuyo next_send_at ya venció y
     * los avanza. Pensado para ser invocado desde un Artisan Command
     * programado (fase de Jobs-Mail).
     */
    public function tick(): void
    {
        EmailSequenceEnrollment::where('status', 'active')
            ->where('next_send_at', '<=', now())
            ->get()
            ->each(function (EmailSequenceEnrollment $enrollment) {
                $this->advance($enrollment);
            });
    }
}
