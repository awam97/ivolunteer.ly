<?php

    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class NotificationSender extends Model
    {
    
        private $defaultCountryCode = '218';
    
        public function __construct()
        {
            
        }

        private function isWhatsAppEnabled()
        {
            $db = \Config\Database::connect();
            $setting = $db->table('settings')->where('setting_key', 'whatsapp_enabled')->get()->getRow();
            return $setting ? (int)$setting->setting_value : 0;
        }

        private function getTestNumber()
        {
            $db = \Config\Database::connect();
            $setting = $db->table('settings')->where('setting_key', 'whatsapp_test_number')->get()->getRow();
            return $setting ? $this->formatPhoneNumber($setting->setting_value) : '';
        }

        private function getApiKey()
        {
            $db = \Config\Database::connect();
            $setting = $db->table('settings')->where('setting_key', 'whatsapp_api_key')->get()->getRow();
            return ($setting && !empty($setting->setting_value)) ? trim($setting->setting_value) : trim((string) env('app.notificationApiKey', ''));
        }

        private function getApiUrl()
        {
            return rtrim((string) env('app.notificationApiUrl', 'https://www.wasenderapi.com/api/send-message'), '/');
        }

        private function getUploadUrl()
        {
            return preg_replace('#/api/send-message$#', '/api/upload', $this->getApiUrl());
        }

        private function request($payload, ?string $url = null, ?string $contentType = null)
        {
            $apiKey = $this->getApiKey();
            if ($apiKey === '') {
                return ['success' => false, 'error' => 'WasenderAPI key is not configured.'];
            }

            $ch = curl_init($url ?: $this->getApiUrl());
            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ];

            if ($contentType === null) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            } else {
                $headers[] = 'Content-Type: ' . $contentType;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);

            $body = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false) {
                return ['success' => false, 'error' => $error ?: 'WasenderAPI request failed.'];
            }

            $decoded = json_decode($body, true);
            $success = is_array($decoded)
                && ($decoded['success'] ?? false) === true
                && $httpCode >= 200
                && $httpCode < 300;

            return [
                'success' => $success,
                'error' => $success ? '' : (is_array($decoded) ? ($decoded['message'] ?? $body) : $body),
                'response' => $decoded ?? $body,
                'http_code' => $httpCode,
            ];
        }

        private function sendToNumber(string $phone, string $message)
        {
            return $this->request([
                'to' => $phone,
                'text' => $message,
            ]);
        }

        private function getAdminNumbers()
        {
            $db = \Config\Database::connect();
            $admins = $db->table('admin')->select('phone')->where('phone !=', '')->get()->getResult();
            $numbers = [];
            foreach ($admins as $admin) {
                $numbers[] = $this->formatPhoneNumber($admin->phone);
            }
            return array_unique($numbers);
        }

        private function formatPhoneNumber($phoneNumber)
        {
            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
            if (strpos($phoneNumber, '00') === 0) { $phoneNumber = '+' . substr($phoneNumber, 2); }
            if (substr($phoneNumber, 0, 1) === '+') { return $phoneNumber; }
            if (substr($phoneNumber, 0, 2) === '09' || substr($phoneNumber, 0, 2) === '07') { return '+' . $this->defaultCountryCode . substr($phoneNumber, 1); }
            if (strpos($phoneNumber, $this->defaultCountryCode) === 0) { return '+' . $phoneNumber; }
            return '+' . $this->defaultCountryCode . $phoneNumber;
        }

        public function shouldSend($case, $target = 'user')
        {
            $db = \Config\Database::connect();
            $key = "wa_{$case}_{$target}";
            $setting = $db->table('settings')->where('setting_key', $key)->get()->getRow();
            return $setting ? (int)$setting->setting_value === 1 : false;
        }
    
        public function sendToAdmin(string $message)
        {
            $adminPhones = $this->getAdminNumbers();
            if (empty($adminPhones)) return false;
            return $this->sendText($adminPhones, $message);
        }
        
        public function sendTextHandler(array $recipients, string $message)
        {
            $mode = $this->isWhatsAppEnabled();
            if ($mode === 0 || empty($recipients)) {
                return true;
            }
            
            $testNumber = ($mode === 2) ? $this->getTestNumber() : null;
            
            $db = \Config\Database::connect();
            $builder = $db->table('pending_messages');

            // In Test Mode (2), we only want to receive ONE message representing the whole group
            if ($mode === 2 && !empty($testNumber)) {
                $recipients = [$testNumber];
            }

            foreach ($recipients as $recipient) 
            {
                $formattedRecipient = $this->formatPhoneNumber($recipient);
                
                $builder->insert([
                    'phone'     => $formattedRecipient,
                    'message'   => $message,
                    'status'    => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Use fallback if Windows or curl not available for backgrounding
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows backgrounding is complex
            } else {
                $cmd = 'nohup curl -s https://portal.i-volunteer.ly/SendGroup > /dev/null 2>&1 &';
                shell_exec($cmd);
            }
            return true;
        }
    
        public function sendText(array $recipients, string $message)
        {
            $results = [];
            $mode = $this->isWhatsAppEnabled();
            if ($mode === 0) {
                return [];
            }
            $testNumber = ($mode === 2) ? $this->getTestNumber() : null;
            foreach ($recipients as $index => $recipient) 
            {
                $phone = $this->formatPhoneNumber($recipient);
                // In Test Mode (2), redirect ALL messages to the test number
                $targetPhone = ($mode === 2 && !empty($testNumber)) ? $testNumber : $phone;

                $results[] = array_merge(
                    ['phone' => $targetPhone],
                    $this->sendToNumber($targetPhone, $message)
                );
            }
            return $results;
        }
        
        public function sendTexts($recipient, string $message, bool $bypassToggle = false)
        {
            $results = [];
            $phone = $this->formatPhoneNumber($recipient);
            $mode = $this->isWhatsAppEnabled();
            $testNumber = ($mode === 2) ? $this->getTestNumber() : null;

            // Block if OFF or if TEST mode and caller is NOT the test number (unless bypassed)
            $isAllowed = $bypassToggle || ($mode === 1) || ($mode === 2 && $phone === $testNumber);
            
            if (!$isAllowed) {
                $results[] = ['phone' => $phone, 'success' => true, 'error' => '(Simulated Success - Mode: ' . $mode . ')'];
                return $results;
            }

            $results[] = array_merge(
                ['phone' => $phone],
                $this->sendToNumber($phone, $message)
            );
            return $results;
        }
    
        public function sendDocument(array $recipients, string $filePath, string $message = '')
        {
            $results = [];
            $mode = $this->isWhatsAppEnabled();
            if ($mode === 0) {
                return [];
            }
            $testNumber = ($mode === 2) ? $this->getTestNumber() : null;
            foreach ($recipients as $recipient) 
            {
                $phone = $this->formatPhoneNumber($recipient);
                // In Test Mode (2), redirect ALL messages to the test number
                $targetPhone = ($mode === 2 && !empty($testNumber)) ? $testNumber : $phone;

                if (!is_readable($filePath)) {
                    $results[] = ['phone' => $targetPhone, 'success' => false, 'error' => 'Document file is not readable.'];
                    continue;
                }

                $mimeType = function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream';
                $upload = $this->request(file_get_contents($filePath), $this->getUploadUrl(), $mimeType);
                $publicUrl = is_array($upload['response'] ?? null) ? ($upload['response']['publicUrl'] ?? '') : '';

                if (!$upload['success'] || $publicUrl === '') {
                    $results[] = array_merge(['phone' => $targetPhone], $upload);
                    continue;
                }

                $results[] = array_merge(
                    ['phone' => $targetPhone],
                    $this->request([
                        'to' => $targetPhone,
                        'text' => $message,
                        'documentUrl' => $publicUrl,
                        'fileName' => basename($filePath),
                    ])
                );
            }
            return $results;
        }
    }
