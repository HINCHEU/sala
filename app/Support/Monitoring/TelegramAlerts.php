<?php

namespace App\Support\Monitoring;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Sends operator alerts to one Telegram chat. Never throws: a failed alert must not fail what raised it. */
class TelegramAlerts
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.alert_chat_id'));
    }

    public function send(string $message): bool
    {
        if (! $this->configured()) {
            return false;
        }

        try {
            return Http::timeout(10)
                ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                    'chat_id' => config('services.telegram.alert_chat_id'),
                    'text' => '['.config('app.name').' '.config('app.env')."] {$message}",
                ])->successful();
        } catch (Throwable $e) {
            // The exception's message would carry the URL, and with it the bot token.
            Log::warning('Telegram alert failed: '.class_basename($e));

            return false;
        }
    }
}
