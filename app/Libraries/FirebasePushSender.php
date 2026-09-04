<?php

namespace App\Libraries;

/** Sends Firebase Cloud Messaging notifications without storing credentials in the repository. */
class FirebasePushSender
{
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $credentials = $this->credentials();
        if (!$credentials || empty($tokens)) {
            return;
        }

        $accessToken = $this->accessToken($credentials);
        if (!$accessToken) {
            log_message('error', 'FCM push skipped: unable to obtain an access token.');
            return;
        }

        $projectId = (string) ($credentials['project_id'] ?? '');
        if ($projectId === '') {
            return;
        }

        foreach (array_unique($tokens) as $token) {
            $ch = curl_init('https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                    ],
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($response === false || $status >= 400) {
                log_message('error', 'FCM push failed ({status}): {error} {response}', [
                    'status' => $status,
                    'error' => $error,
                    'response' => is_string($response) ? substr($response, 0, 500) : '',
                ]);
            }
        }
    }

    private function credentials(): ?array
    {
        $value = (string) env('firebase.serviceAccountJson', '');
        if ($value === '') {
            $path = (string) env('firebase.serviceAccountPath', '');
            if ($path !== '' && is_readable($path)) {
                $value = (string) file_get_contents($path);
            }
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function accessToken(array $credentials): ?string
    {
        $clientEmail = (string) ($credentials['client_email'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');
        if ($clientEmail === '' || $privateKey === '') {
            return null;
        }

        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64Url(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header . '.' . $claim;
        if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $this->base64Url($signature),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string) $response, true);
        return is_array($decoded) && !empty($decoded['access_token']) ? (string) $decoded['access_token'] : null;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
