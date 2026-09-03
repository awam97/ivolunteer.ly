<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public webhook endpoints. Authentication is performed with the provider
 * signature instead of the normal admin session.
 */
class Webhook extends BaseController
{
    public function wasender(): ResponseInterface
    {
        $secret = $this->getWebhookSecret();
        $signature = trim($this->request->getHeaderLine('X-Webhook-Signature'));

        if ($secret === '' || $signature === '' || !hash_equals($secret, $signature)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Invalid webhook signature.',
            ]);
        }

        $rawBody = $this->request->getBody();
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Invalid JSON payload.',
            ]);
        }

        $event = (string) ($payload['event'] ?? 'unknown');
        $message = $payload['data']['messages'] ?? [];
        $key = is_array($message) ? ($message['key'] ?? []) : [];
        $sender = is_array($key) ? ($key['cleanedSenderPn'] ?? $key['remoteJid'] ?? '') : '';
        $text = is_array($message) ? ($message['messageBody'] ?? '') : '';

        log_message('info', 'Wasender webhook received: {event} from {sender}: {text}', [
            'event' => $event,
            'sender' => is_scalar($sender) ? (string) $sender : '',
            'text' => is_scalar($text) ? (string) $text : '',
        ]);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'success',
            'received' => true,
            'event' => $event,
        ]);
    }

    private function getWebhookSecret(): string
    {
        $setting = \Config\Database::connect()->table('settings')
            ->where('setting_key', 'whatsapp_webhook_secret')
            ->get()
            ->getRow();

        return trim((string) ($setting->setting_value ?? env('app.whatsappWebhookSecret', '')));
    }
}
