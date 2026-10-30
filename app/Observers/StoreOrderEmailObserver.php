<?php

namespace App\Observers;

use App\Models\StoreOrder;
use App\Services\StoreOrderMailService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Avisa por correo al cliente cuando se crea un pedido de la tienda o cambia su estatus
 * (ver StoreOrderMailService). Corre después del commit: al crear el pedido, sus partidas se
 * guardan dentro de la misma transacción y deben existir cuando se arma el correo.
 * Un error aquí nunca debe romper el checkout ni el cambio de estatus.
 */
class StoreOrderEmailObserver implements ShouldHandleEventsAfterCommit
{
    public function created(StoreOrder $order): void
    {
        $this->run(fn (StoreOrderMailService $mail) => $mail->handleCreated($order->fresh() ?? $order));
    }

    public function updated(StoreOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $this->run(fn (StoreOrderMailService $mail) => $mail->handleStatusChanged($order->fresh() ?? $order));
    }

    private function run(callable $callback): void
    {
        try {
            $callback(app(StoreOrderMailService::class));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
