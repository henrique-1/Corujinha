<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastService
{
    /**
     * Dispara um evento de broadcast em tempo real de forma resiliente,
     * impedindo que indisponibilidades no servidor WebSocket causem erro 500 na requisição HTTP.
     */
    public static function dispatch(object $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable $e) {
            Log::warning('Falha ao transmitir evento em tempo real via Reverb.', [
                'event' => $event::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
