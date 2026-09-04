<?php

namespace App\Libraries;

use App\Models\NotificationSender;
use App\Libraries\FirebasePushSender;

class MobileApiService
{
    protected $db;
    protected $request;
    protected $response;
    protected $notificationsender;
    protected $firebasePush;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        $this->notificationsender = new NotificationSender();
        $this->firebasePush = new FirebasePushSender();
    }

    private function json(array $payload, int $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string|false
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }

    private function currentDate(): string
    {
        return date('Y-m-d');
    }

    private function volunteerImageUrl(?int $volunteerId): ?string
    {
        if (!$volunteerId) {
            return null;
        }

        $matches = glob(FCPATH . 'uploads/volunteers_files/' . $volunteerId . '.*');
        if (!empty($matches)) {
            return base_url('uploads/volunteers_files/' . basename($matches[0]));
        }

        return null;
    }

    private function activityImageUrl(?int $activityId): ?string
    {
        if (!$activityId) {
            return null;
        }

        $matches = glob(FCPATH . 'uploads/activities_files/' . $activityId . '.*');
        if (!empty($matches)) {
            return base_url('uploads/activities_files/' . basename($matches[0]));
        }

        return base_url('uploads/activities_files/default.png');
    }

    private function userImageUrl(string $folder, ?int $userId): ?string
    {
        if (!$userId) {
            return null;
        }

        $matches = glob(FCPATH . 'uploads/' . $folder . '_files/' . $userId . '.*');
        if (!empty($matches)) {
            return base_url('uploads/' . $folder . '_files/' . basename($matches[0]));
        }

        return base_url('uploads/user.jpg');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
        return $row ? (string) $row->setting_value : $default;
    }

    private function createToken(object $user, string $type): string
    {
        $payload = [
            'id' => (int) $user->id,
            'type' => $type,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60),
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $encodedPayload = $this->base64UrlEncode($payloadJson);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $user->password, true));

        return $encodedPayload . '.' . $signature;
    }

    private function findAuthenticatedUserById(array $payload): array|false
    {
        $type = $payload['type'] ?? '';
        $table = $type === 'admin' ? 'admin' : ($type === 'volunteer' ? 'volunteers' : null);
        if ($table === null) {
            return false;
        }

        $user = $this->db->table($table)->where('id', (int) $payload['id'])->get()->getRow();
        if (!$user) {
            return false;
        }

        $decodedType = $table === 'admin' ? 'admin' : 'volunteer';

        return [
            'payload' => $payload,
            'user' => $user,
            'type' => $decodedType,
        ];
    }

    private function decodeToken(string $token): array|false
    {
        if (!str_contains($token, '.')) {
            return false;
        }

        [$encodedPayload, $signature] = explode('.', $token, 2);
        $decodedPayload = $this->base64UrlDecode($encodedPayload);
        if ($decodedPayload === false) {
            return false;
        }

        $payload = json_decode($decodedPayload, true);
        if (!is_array($payload) || !isset($payload['id'], $payload['exp'], $payload['type'])) {
            return false;
        }

        $decoded = $this->findAuthenticatedUserById($payload);
        if ($decoded === false) {
            return false;
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $decoded['user']->password, true));
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        return [$payload, $decoded['user'], $decoded['type']];
    }

    private function getTokenFromRequest(): ?string
    {
        $authorization = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        $token = $this->request->getGet('token');
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $json = $this->request->getJSON();
        if (is_object($json) && isset($json->token) && is_string($json->token)) {
            return $json->token;
        }

        return null;
    }

    private function authenticate(): array
    {
        $token = $this->getTokenFromRequest();
        if (!$token || !str_contains($token, '.')) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'Missing or invalid token.'], 401)];
        }

        [$encodedPayload, $signature] = explode('.', $token, 2);
        $decodedPayload = $this->base64UrlDecode($encodedPayload);
        if ($decodedPayload === false) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'Invalid token payload.'], 401)];
        }

        $payload = json_decode($decodedPayload, true);
        if (!is_array($payload) || !isset($payload['id'], $payload['exp'], $payload['type'])) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'Invalid token payload.'], 401)];
        }

        if ((int) $payload['exp'] < time()) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'Session expired.'], 401)];
        }

        $decoded = $this->findAuthenticatedUserById($payload);
        if ($decoded === false) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'User not found.'], 401)];
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $decoded['user']->password, true));
        if (!hash_equals($expectedSignature, $signature)) {
            return ['user' => null, 'type' => null, 'error' => $this->json(['status' => 'error', 'message' => 'Invalid token signature.'], 401)];
        }

        return [
            'user' => $decoded['user'],
            'type' => $decoded['type'],
            'error' => null,
        ];
    }

    private function getAuthenticatedVolunteer(): array
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return [null, $auth['error']];
        }

        if (($auth['type'] ?? null) !== 'volunteer') {
            return [null, $this->json(['status' => 'error', 'message' => 'Volunteer account required.'], 403)];
        }

        return [$auth['user'], null];
    }

    private function getAuthenticatedAdmin(): array
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return [null, $auth['error']];
        }

        if (($auth['type'] ?? null) !== 'admin') {
            return [null, $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403)];
        }

        return [$auth['user'], null];
    }

    private function volunteerPayload(object $volunteer): array
    {
        $city = null;
        if (!empty($volunteer->city_id)) {
            $city = $this->db->table('cities')->where('id', $volunteer->city_id)->get()->getRow();
        }

        return [
            'id' => (int) $volunteer->id,
            'name' => $volunteer->name ?? '',
            'username' => $volunteer->username ?? '',
            'email' => $volunteer->email ?? '',
            'phone' => $volunteer->phone ?? '',
            'city_id' => isset($volunteer->city_id) ? (int) $volunteer->city_id : null,
            'city_name' => $city->name ?? null,
            'language' => $volunteer->language ?? 'ar',
            'image_url' => $this->volunteerImageUrl((int) $volunteer->id),
        ];
    }

    private function adminPayload(object $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'name' => $admin->name ?? '',
            'username' => $admin->username ?? '',
            'email' => $admin->email ?? '',
            'phone' => $admin->phone ?? '',
            'city_id' => null,
            'city_name' => null,
            'language' => $admin->language ?? 'ar',
            'image_url' => $this->userImageUrl('admin', (int) $admin->id),
        ];
    }

    private function activityPayload(object $activity, ?int $volunteerId = null): array
    {
        $city = null;
        if (!empty($activity->city_id)) {
            $city = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        }

        $enrollment = null;
        if ($volunteerId) {
            $enrollment = $this->db->table('volunteer_activities')
                ->where('volunteer_id', $volunteerId)
                ->where('activity_id', $activity->id)
                ->get()
                ->getRow();
        }

        return [
            'id' => (int) $activity->id,
            'name' => $activity->name ?? '',
            'organisation' => $activity->organisation ?? '',
            'city_id' => isset($activity->city_id) ? (int) $activity->city_id : null,
            'city_name' => $city->name ?? null,
            'date_from' => $activity->date_from ?? null,
            'date_to' => $activity->date_to ?? null,
            'hours' => isset($activity->hours) ? (int) $activity->hours : null,
            'description' => $activity->description ?? '',
            'required_files' => $activity->required_files ?? '',
            'transportation' => isset($activity->transportation) ? (int) $activity->transportation : 0,
            'residency' => isset($activity->residency) ? (int) $activity->residency : 0,
            'expenses' => isset($activity->expenses) ? (int) $activity->expenses : 0,
            'training' => isset($activity->training) ? (int) $activity->training : 0,
            'image_url' => $this->activityImageUrl((int) $activity->id),
            'is_enrolled' => $enrollment ? 1 : 0,
            'enrollment_id' => $enrollment->id ?? null,
            'enrollment_status' => isset($enrollment->status) ? (int) $enrollment->status : null,
            'public_certificate' => $enrollment ? !empty($enrollment->public_certificate) : false,
            'private_certificate' => $enrollment ? !empty($enrollment->private_certificate) : false,
        ];
    }

    private function adminActivityPayload(object $activity): array
    {
        $city = null;
        if (!empty($activity->city_id)) {
            $city = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        }

        $totalEnrollments = $this->db->table('volunteer_activities')
            ->where('activity_id', (int) $activity->id)
            ->countAllResults();

        $pendingEnrollments = $this->db->table('volunteer_activities')
            ->where('activity_id', (int) $activity->id)
            ->where('status', 0)
            ->countAllResults();

        $approvedEnrollments = $this->db->table('volunteer_activities')
            ->where('activity_id', (int) $activity->id)
            ->where('status', 1)
            ->countAllResults();

        return [
            'id' => (int) $activity->id,
            'name' => $activity->name ?? '',
            'organisation' => $activity->organisation ?? '',
            'city_id' => isset($activity->city_id) ? (int) $activity->city_id : null,
            'city_name' => $city->name ?? null,
            'date_from' => $activity->date_from ?? null,
            'date_to' => $activity->date_to ?? null,
            'hours' => isset($activity->hours) ? (int) $activity->hours : null,
            'description' => $activity->description ?? '',
            'required_files' => $activity->required_files ?? '',
            'transportation' => isset($activity->transportation) ? (int) $activity->transportation : 0,
            'residency' => isset($activity->residency) ? (int) $activity->residency : 0,
            'expenses' => isset($activity->expenses) ? (int) $activity->expenses : 0,
            'training' => isset($activity->training) ? (int) $activity->training : 0,
            'image_url' => $this->activityImageUrl((int) $activity->id),
            'total_enrollments' => $totalEnrollments,
            'pending_enrollments' => $pendingEnrollments,
            'approved_enrollments' => $approvedEnrollments,
        ];
    }

    private function adminVolunteerPayload(object $volunteer): array
    {
        $city = null;
        if (!empty($volunteer->city_id)) {
            $city = $this->db->table('cities')->where('id', $volunteer->city_id)->get()->getRow();
        }

        $totalRequests = $this->db->table('volunteer_activities')
            ->where('volunteer_id', (int) $volunteer->id)
            ->countAllResults();

        $approvedRequests = $this->db->table('volunteer_activities')
            ->where('volunteer_id', (int) $volunteer->id)
            ->where('status', 1)
            ->countAllResults();

        return [
            'id' => (int) $volunteer->id,
            'name' => $volunteer->name ?? '',
            'username' => $volunteer->username ?? '',
            'email' => $volunteer->email ?? '',
            'phone' => $volunteer->phone ?? '',
            'city_id' => isset($volunteer->city_id) ? (int) $volunteer->city_id : null,
            'city_name' => $city->name ?? null,
            'language' => $volunteer->language ?? 'ar',
            'image_url' => $this->volunteerImageUrl((int) $volunteer->id),
            'total_requests' => $totalRequests,
            'approved_requests' => $approvedRequests,
        ];
    }

    private function adminRequestPayload(object $request): array
    {
        $volunteer = $this->db->table('volunteers')->where('id', $request->volunteer_id)->get()->getRow();
        $activity = $this->db->table('activities')->where('id', $request->activity_id)->get()->getRow();
        $city = null;
        if ($activity && !empty($activity->city_id)) {
            $city = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        }

        return [
            'id' => (int) $request->id,
            'status' => isset($request->status) ? (int) $request->status : 0,
            'public_certificate' => !empty($request->public_certificate),
            'private_certificate' => !empty($request->private_certificate),
            'created_at' => $request->created_at ?? null,
            'volunteer' => $volunteer ? $this->adminVolunteerPayload($volunteer) : null,
            'activity' => $activity ? $this->adminActivityPayload($activity) : null,
            'city_name' => $city->name ?? null,
        ];
    }

    public function ping()
    {
        return $this->json([
            'status' => 'success',
            'message' => 'Mobile API is alive.',
            'data' => [
                'timestamp' => date('c'),
                'base_url' => getenv('app.baseURL') ?: 'unknown',
                'environment' => getenv('CI_ENVIRONMENT') ?: 'unknown',
            ],
        ]);
    }

    public function login()
    {
        try {
            $payload = null;
            $rawBody = trim((string) $this->request->getBody());
            if ($rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            if (!is_array($payload)) {
                $payload = $this->request->getPost();
            }

            $identifier = trim((string) ($payload['identifier'] ?? ($payload['username'] ?? '')));
            $password = (string) ($payload['password'] ?? '');
            if ($identifier === '' || $password === '') {
                return $this->json(['status' => 'error', 'message' => 'Username and password are required.'], 422);
            }

            $volunteer = $this->db->table('volunteers')
                ->where('username', $identifier)
                ->get()
                ->getRow();

            if (!$volunteer || !password_verify($password, (string) $volunteer->password)) {
                $admin = $this->db->table('admin')
                    ->where('username', $identifier)
                    ->get()
                    ->getRow();

                if (!$admin || !password_verify($password, (string) $admin->password)) {
                    return $this->json(['status' => 'error', 'message' => 'Invalid username or password.'], 401);
                }

                $token = $this->createToken($admin, 'admin');

                return $this->json([
                    'status' => 'success',
                    'message' => 'Login successful.',
                    'data' => [
                        'token' => $token,
                        'type' => 'admin',
                        'volunteer' => $this->adminPayload($admin),
                    ],
                ]);
            }

            $token = $this->createToken($volunteer, 'volunteer');

            return $this->json([
                'status' => 'success',
                'message' => 'Login successful.',
                'data' => [
                    'token' => $token,
                    'type' => 'volunteer',
                    'volunteer' => $this->volunteerPayload($volunteer),
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Mobile login failed: {message} in {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->json([
                'status' => 'error',
                'message' => 'Server error while processing login.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function register()
    {
        try {
            $payload = json_decode((string) $this->request->getBody(), true);
            if (!is_array($payload)) {
                $payload = $this->request->getPost();
            }

            $name = trim((string) ($payload['name'] ?? ''));
            $username = trim((string) ($payload['username'] ?? ''));
            $phone = trim((string) ($payload['phone'] ?? ''));
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $password = (string) ($payload['password'] ?? '');
            $cityId = (int) ($payload['city_id'] ?? 0);

            if ($name === '' || $username === '' || $phone === '' || $password === '' || $cityId < 1) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'الاسم واسم المستخدم والهاتف وكلمة المرور والمدينة مطلوبة.',
                ], 422);
            }
            if (strlen($password) < 6) {
                return $this->json(['status' => 'error', 'message' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.'], 422);
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(['status' => 'error', 'message' => 'البريد الإلكتروني غير صحيح.'], 422);
            }
            if (!$this->db->table('cities')->where('id', $cityId)->countAllResults()) {
                return $this->json(['status' => 'error', 'message' => 'المدينة المحددة غير موجودة.'], 422);
            }

            $volunteerQuery = $this->db->table('volunteers')
                ->groupStart()
                ->where('username', $username)
                ->orWhere('phone', $phone);
            if ($email !== '') {
                $volunteerQuery->orWhere('email', $email);
            }
            $duplicate = $volunteerQuery->groupEnd()->get()->getRow();

            $adminQuery = $this->db->table('admin')
                ->groupStart()
                ->where('username', $username)
                ->orWhere('phone', $phone);
            if ($email !== '') {
                $adminQuery->orWhere('email', $email);
            }
            $adminDuplicate = $adminQuery->groupEnd()->get()->getRow();

            if ($duplicate || $adminDuplicate) {
                $field = $duplicate?->username === $username || $adminDuplicate?->username === $username
                    ? 'اسم المستخدم'
                    : (($duplicate?->phone === $phone || $adminDuplicate?->phone === $phone) ? 'رقم الهاتف' : 'البريد الإلكتروني');
                return $this->json(['status' => 'error', 'message' => "{$field} مستخدم بالفعل."], 409);
            }

            $data = [
                'name' => $name,
                'username' => $username,
                'phone' => $phone,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'city_id' => $cityId,
                'address' => trim((string) ($payload['address'] ?? '')),
                'birthdate' => trim((string) ($payload['birthdate'] ?? '')),
                'gender' => (int) ($payload['gender'] ?? 0),
                'identity' => trim((string) ($payload['identity'] ?? '')),
                'academic_value' => trim((string) ($payload['academic_value'] ?? '')),
                'hobbies' => trim((string) ($payload['hobbies'] ?? '')),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->table('volunteers')->insert($data);
            $id = (int) $this->db->insertID();

            $template = $this->getSetting('msg_registration', 'مرحباً {name}، تم استلام تسجيلك في منصة أنا متطوع.');
            if ($this->notificationsender->shouldSend('reg', 'user')) {
                $this->notificationsender->sendText([$phone], str_replace('{name}', $name, $template));
            }
            if ($this->notificationsender->shouldSend('reg', 'admin')) {
                $this->notificationsender->sendToAdmin("🔔 *متطوع جديد انضم للمنصة!*

👤 الاسم: {$name}
📞 الهاتف: {$phone}");
            }
            $adminTokens = $this->db->table('mobile_device_tokens')
                ->select('token')->where('user_type', 'admin')->get()->getResultArray();
            $this->firebasePush->send(
                array_column($adminTokens, 'token'),
                'متطوع جديد',
                "انضم {$name} إلى المنصة.",
                ['type' => 'volunteer_registered', 'volunteer_id' => (string) $id]
            );

            return $this->json(['status' => 'success', 'message' => 'تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.', 'data' => ['id' => $id]], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Mobile registration failed: {message}', ['message' => $e->getMessage()]);
            return $this->json(['status' => 'error', 'message' => 'تعذر إنشاء الحساب حالياً.'], 500);
        }
    }

    public function deviceToken()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $payload = $this->request->getJSON(true) ?: [];
        $deviceToken = trim((string) ($payload['token'] ?? ''));
        if ($deviceToken === '' || strlen($deviceToken) > 4096) {
            return $this->json(['status' => 'error', 'message' => 'A valid device token is required.'], 422);
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS mobile_device_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_type VARCHAR(20) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(4096) NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY mobile_device_token_unique (token(191)),
            KEY mobile_device_user (user_type, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $userType = $auth['type'] === 'admin' ? 'admin' : 'volunteer';
        if ($this->request->getMethod() === 'delete') {
            $this->db->table('mobile_device_tokens')->where('token', $deviceToken)->delete();
        } else {
            $this->db->table('mobile_device_tokens')->where('token', $deviceToken)->delete();
            $this->db->table('mobile_device_tokens')->insert([
                'user_type' => $userType,
                'user_id' => (int) $auth['user']->id,
                'token' => $deviceToken,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->json(['status' => 'success', 'message' => 'Device token updated.']);
    }

    public function refresh()
    {
        $token = $this->getTokenFromRequest();
        if (!$token) {
            return $this->json(['status' => 'error', 'message' => 'Missing or invalid token.'], 401);
        }

        $decoded = $this->decodeToken($token);
        if ($decoded === false) {
            return $this->json(['status' => 'error', 'message' => 'Invalid token.'], 401);
        }

        [, $user, $type] = $decoded;
        $newToken = $this->createToken($user, $type);
        $profile = $type === 'admin' ? $this->adminPayload($user) : $this->volunteerPayload($user);

        return $this->json([
            'status' => 'success',
            'message' => 'Session refreshed successfully.',
            'data' => [
                'token' => $newToken,
                'type' => $type,
                'volunteer' => $profile,
            ],
        ]);
    }

    public function logout()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Logout successful.',
            'data' => [
                'volunteer_id' => (int) $auth['user']->id,
            ],
        ]);
    }

    public function me()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $user = $auth['user'];
        $type = $auth['type'];
        $profile = $type === 'admin' ? $this->adminPayload($user) : $this->volunteerPayload($user);

        if ($type === 'admin') {
            $totalActivities = $this->db->table('activities')->countAllResults();
            $approvedActivities = $this->db->table('volunteer_activities')->where('status', 1)->countAllResults();
            $completedActivities = $this->db->table('volunteer_activities')->where('status', 2)->countAllResults();
        } else {
            $volunteerId = (int) $user->id;
            $totalActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->countAllResults();
            $approvedActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->where('status', 1)->countAllResults();
            $completedActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->where('status', 2)->countAllResults();
        }

        $totalVolunteers = $this->db->table('volunteers')->countAllResults();
        $totalCities = $this->db->table('cities')->countAllResults();
        $totalCertificates = $this->db->table('volunteer_activities')->where('status', 2)->countAllResults();

        return $this->json([
            'status' => 'success',
            'data' => [
                'type' => $type,
                'volunteer' => $profile,
                'stats' => [
                    'total_activities' => $totalActivities,
                    'approved_activities' => $approvedActivities,
                    'completed_activities' => $completedActivities,
                    'total_volunteers' => $totalVolunteers,
                    'total_cities' => $totalCities,
                    'total_certificates' => $totalCertificates,
                    'pending_enrollments' => $this->db->table('volunteer_activities')->where('status', 0)->countAllResults(),
                ],
            ],
        ]);
    }

    public function cities()
    {
        $cities = $this->db->table('cities')->orderBy('name', 'ASC')->get()->getResult();
        return $this->json([
            'status' => 'success',
            'data' => $cities,
        ]);
    }

    public function updateProfile()
    {
        $auth = $this->authenticate();
        if ($auth['error']) return $auth['error'];

        $json = $this->request->getJSON(true) ?: [];
        $user = $auth['user'];
        $type = $auth['type'];
        $name = trim((string) ($json['name'] ?? $user->name ?? ''));
        $username = trim((string) ($json['username'] ?? $user->username ?? ''));
        $phone = trim((string) ($json['phone'] ?? $user->phone ?? ''));
        $email = strtolower(trim((string) ($json['email'] ?? $user->email ?? '')));
        if ($name === '' || $username === '' || $phone === '') {
            return $this->json(['status' => 'error', 'message' => 'الاسم واسم المستخدم والهاتف مطلوبة.'], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['status' => 'error', 'message' => 'البريد الإلكتروني غير صحيح.'], 422);
        }
        $table = $type === 'admin' ? 'admin' : 'volunteers';
        $otherTable = $type === 'admin' ? 'volunteers' : 'admin';
        foreach ([['username', $username, 'اسم المستخدم'], ['phone', $phone, 'رقم الهاتف'], ['email', $email, 'البريد الإلكتروني']] as [$field, $value, $label]) {
            if ($value === '') continue;
            $same = $this->db->table($table)->where($field, $value)->where('id !=', (int) $user->id)->countAllResults();
            $other = $this->db->table($otherTable)->where($field, $value)->countAllResults();
            if ($same || $other) return $this->json(['status' => 'error', 'message' => "{$label} مستخدم بالفعل."], 409);
        }
        $this->db->table($table)->where('id', (int) $user->id)->update(compact('name', 'username', 'phone', 'email'));
        return $this->json(['status' => 'success', 'message' => 'تم تحديث بيانات الملف الشخصي.']);
    }

    public function activities()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $user = $auth['user'];
        $type = $auth['type'];
        $volunteerId = $type === 'volunteer' ? (int) $user->id : null;
        $defaultCityId = $type === 'volunteer' && isset($user->city_id) ? (int) $user->city_id : null;
        $cityId = $this->request->getGet('city_id');
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $builder = $this->db->table('activities')
            ->select('activities.*, cities.name as city_name')
            ->join('cities', 'cities.id = activities.city_id', 'left')
            ->where('activities.date_from >=', $this->currentDate())
            ->orderBy('activities.date_from', 'ASC');

        if ($type === 'volunteer') {
            $builder->join('volunteer_activities', 'volunteer_activities.activity_id = activities.id AND volunteer_activities.volunteer_id = ' . $volunteerId, 'left');
        }

        if ($cityId !== null && $cityId !== '' && $cityId !== 'all') {
            $builder->where('activities.city_id', (int) $cityId);
        } elseif ($defaultCityId) {
            $builder->where('activities.city_id', $defaultCityId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('activities.name', $search)
                ->orLike('activities.organisation', $search)
                ->orLike('activities.description', $search)
                ->groupEnd();
        }

        $activities = $builder->get()->getResult();
        $data = array_map(fn ($activity) => $this->activityPayload($activity, $volunteerId), $activities);

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function adminActivities()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $activities = $this->db->table('activities')
            ->orderBy('date_from', 'DESC')
            ->get()
            ->getResult();

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn ($activity) => $this->adminActivityPayload($activity), $activities),
        ]);
    }

    public function deleteAdminActivity(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }
        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }
        if ($id <= 0 || !$this->db->table('activities')->where('id', $id)->get()->getRow()) {
            return $this->json(['status' => 'error', 'message' => 'Activity not found.'], 404);
        }

        $this->db->transStart();
        $this->db->table('volunteer_activities')->where('activity_id', $id)->delete();
        $this->db->table('activities')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->json(['status' => 'error', 'message' => 'Activity could not be deleted.'], 500);
        }
        return $this->json(['status' => 'success', 'message' => 'Activity deleted successfully.']);
    }

    public function adminVolunteers()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $volunteers = $this->db->table('volunteers')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn ($volunteer) => $this->adminVolunteerPayload($volunteer), $volunteers),
        ]);
    }

    public function deleteAdminVolunteer(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }
        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }
        if ($id <= 0 || !$this->db->table('volunteers')->where('id', $id)->get()->getRow()) {
            return $this->json(['status' => 'error', 'message' => 'Volunteer not found.'], 404);
        }

        $this->db->transStart();
        $this->db->table('volunteer_activities')->where('volunteer_id', $id)->delete();
        $this->db->table('volunteers')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->json(['status' => 'error', 'message' => 'Volunteer could not be deleted.'], 500);
        }
        return $this->json(['status' => 'success', 'message' => 'Volunteer deleted successfully.']);
    }

    public function adminRequests()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $requests = $this->db->table('volunteer_activities')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn ($request) => $this->adminRequestPayload($request), $requests),
        ]);
    }

    public function notifications()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $volunteers = $this->db->table('volunteers')
            ->select('volunteers.id, volunteers.name, volunteers.phone, volunteers.created_at, cities.name as city_name')
            ->join('cities', 'cities.id = volunteers.city_id', 'left')
            ->orderBy('volunteers.id', 'DESC')
            ->limit(50)
            ->get()
            ->getResult();

        $data = array_map(static function ($volunteer) {
            return [
                'id' => 'volunteer_registration_' . (int) $volunteer->id,
                'type' => 'volunteer_registered',
                'title' => 'متطوع جديد مسجل',
                'message' => 'انضم ' . ($volunteer->name ?? 'متطوع جديد') . ' إلى المنصة.',
                'volunteer_id' => (int) $volunteer->id,
                'volunteer_name' => $volunteer->name ?? '',
                'phone' => $volunteer->phone ?? '',
                'city_name' => $volunteer->city_name ?? 'غير محددة',
                'created_at' => $volunteer->created_at ?? null,
            ];
        }, $volunteers);

        return $this->json(['status' => 'success', 'data' => $data]);
    }

    public function updateRequestStatus()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $json = $this->request->getJSON();
        $requestId = isset($json->id) ? (int) $json->id : 0;
        $status = isset($json->status) ? (int) $json->status : null;

        if ($requestId <= 0 || $status === null) {
            return $this->json(['status' => 'error', 'message' => 'Request id and status are required.'], 422);
        }

        $request = $this->db->table('volunteer_activities')->where('id', $requestId)->get()->getRow();
        if (!$request) {
            return $this->json(['status' => 'error', 'message' => 'Request not found.'], 404);
        }

        $this->db->table('volunteer_activities')->where('id', $requestId)->update(['status' => $status]);

        $volunteer = $this->db->table('volunteers')->where('id', $request->volunteer_id)->get()->getRow();
        $activity = $this->db->table('activities')->where('id', $request->activity_id)->get()->getRow();
        $templates = [
            1 => 'تمت الموافقة على طلب انضمامك إلى النشاط: {activity_name}.',
            2 => 'تم تسجيل مشاركتك كمكتملة في النشاط: {activity_name}.',
            3 => 'نأسف، تم رفض طلب انضمامك إلى النشاط: {activity_name}.',
        ];
        $message = $activity
            ? str_replace('{activity_name}', (string) $activity->name, $templates[$status] ?? 'تم تحديث حالة طلبك للنشاط: {activity_name}.')
            : 'تم تحديث حالة طلب التطوع.';
        if ($volunteer && $activity && $this->notificationsender->shouldSend('status', 'user')) {
            $this->notificationsender->sendText([$volunteer->phone], $message);
        }

        if ($volunteer && $activity) {
            $tokens = $this->db->table('mobile_device_tokens')
                ->select('token')
                ->where('user_type', 'volunteer')
                ->where('user_id', (int) $volunteer->id)
                ->get()->getResultArray();
            $this->firebasePush->send(
                array_column($tokens, 'token'),
                'تحديث طلب التطوع',
                $message ?? 'تم تحديث حالة طلبك.',
                ['type' => 'enrollment_status', 'request_id' => (string) $requestId]
            );
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Request updated successfully.',
        ]);
    }

    public function updateCertificate()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $json = $this->request->getJSON(true) ?: [];
        $requestId = (int) ($json['id'] ?? 0);
        $type = (string) ($json['type'] ?? '');
        $enabled = filter_var($json['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $enabled = $enabled === null ? true : $enabled;
        $field = match ($type) {
            'public' => 'public_certificate',
            'private' => 'private_certificate',
            default => null,
        };

        if ($requestId <= 0 || $field === null) {
            return $this->json(['status' => 'error', 'message' => 'Request id and certificate type are required.'], 422);
        }

        $request = $this->db->table('volunteer_activities')->where('id', $requestId)->get()->getRow();
        if (!$request) {
            return $this->json(['status' => 'error', 'message' => 'Request not found.'], 404);
        }
        if ((int) $request->status !== 2) {
            return $this->json(['status' => 'error', 'message' => 'Certificates can only be issued for completed activities.'], 422);
        }

        $this->db->table('volunteer_activities')->where('id', $requestId)->update([$field => $enabled ? 1 : 0]);

        if ($enabled) {
            $volunteer = $this->db->table('volunteers')->where('id', $request->volunteer_id)->get()->getRow();
            $activity = $this->db->table('activities')->where('id', $request->activity_id)->get()->getRow();
            if ($volunteer && $activity && $this->notificationsender->shouldSend('cert', 'user')) {
                $this->notificationsender->sendText([
                    $volunteer->phone,
                ], 'أصبحت شهادة ' . ($type === 'public' ? 'المشاركة العامة' : 'المشاركة الخاصة') . ' للنشاط جاهزة: ' . $activity->name);
            }
        }

        return $this->json([
            'status' => 'success',
            'message' => $enabled ? 'Certificate issued successfully.' : 'Certificate revoked successfully.',
            'data' => ['id' => $requestId, 'type' => $type, 'enabled' => $enabled],
        ]);
    }

    public function certificate(int $id, string $type)
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $field = match ($type) {
            'public' => 'public_certificate',
            'private' => 'private_certificate',
            default => null,
        };
        if ($field === null) {
            return $this->json(['status' => 'error', 'message' => 'Invalid certificate type.'], 422);
        }

        $enrollment = $this->db->table('volunteer_activities')->where('id', $id)->get()->getRow();
        if (!$enrollment || (int) $enrollment->status !== 2 || empty($enrollment->{$field})) {
            return $this->json(['status' => 'error', 'message' => 'Certificate is not available.'], 404);
        }
        if ($auth['type'] === 'volunteer' && (int) $enrollment->volunteer_id !== (int) $auth['user']->id) {
            return $this->json(['status' => 'error', 'message' => 'You are not allowed to access this certificate.'], 403);
        }

        $generator = new \Picqer\src\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
        $data = [
            'barcode' => '<img style="padding-left:30px" src="data:image/png;base64,' . base64_encode($barcode) . '" />',
            'entityName' => 'volunteer_activities',
            'entities' => [$enrollment],
            'id' => $id,
            'db' => $this->db,
        ];

        $view = $type === 'public' ? 'public_certificate' : 'certificate';
        return view('Volunteer/' . $view, $data);
    }

    public function news()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $items = $this->db->table('news')
            ->select('news.id, news.name, news.post_date, news.post_content, news.activity_id, activities.name as activity_name')
            ->join('activities', 'activities.id = news.activity_id', 'left')
            ->orderBy('news.post_date', 'DESC')
            ->orderBy('news.id', 'DESC')
            ->limit(30)
            ->get()
            ->getResult();

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name ?? '',
                'post_date' => $item->post_date ?? null,
                'post_content' => $item->post_content ?? '',
                'activity_id' => isset($item->activity_id) ? (int) $item->activity_id : null,
                'activity_name' => $item->activity_name ?? null,
                'image_url' => $item->activity_id ? $this->activityImageUrl((int) $item->activity_id) : null,
            ], $items),
        ]);
    }

    public function createNews()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }
        if ($auth['type'] !== 'admin') {
            return $this->json(['status' => 'error', 'message' => 'Admin account required.'], 403);
        }

        $json = $this->request->getJSON(true) ?: [];
        $name = trim((string) ($json['name'] ?? ''));
        $content = trim((string) ($json['post_content'] ?? ''));
        $postDate = trim((string) ($json['post_date'] ?? date('Y-m-d')));
        $activityId = (int) ($json['activity_id'] ?? 0);
        if ($name === '' || $content === '' || $postDate === '') {
            return $this->json(['status' => 'error', 'message' => 'Title, date and content are required.'], 422);
        }
        if ($activityId > 0 && !$this->db->table('activities')->where('id', $activityId)->countAllResults()) {
            return $this->json(['status' => 'error', 'message' => 'Selected activity was not found.'], 422);
        }

        $this->db->table('news')->insert([
            'name' => $name,
            'post_date' => $postDate,
            'post_content' => $content,
            'activity_id' => $activityId > 0 ? $activityId : 0,
        ]);
        $id = (int) $this->db->insertID();
        if ($id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'News could not be created.'], 500);
        }

        return $this->json(['status' => 'success', 'message' => 'News created successfully.', 'data' => ['id' => $id]]);
    }

    private function ensureNewsInteractionsTable(): void
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS mobile_news_interactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            news_id INT UNSIGNED NOT NULL,
            user_type VARCHAR(20) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            comment TEXT NULL,
            created_at DATETIME NOT NULL,
            KEY news_interaction_lookup (news_id, user_type, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function likeNews(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) return $auth['error'];
        $this->ensureNewsInteractionsTable();
        $type = $auth['type'];
        $userId = (int) $auth['user']->id;
        $existing = $this->db->table('mobile_news_interactions')->where(['news_id' => $id, 'user_type' => $type, 'user_id' => $userId])->where('comment IS NULL', null, false)->get()->getRow();
        if ($existing) $this->db->table('mobile_news_interactions')->where('id', $existing->id)->delete();
        else $this->db->table('mobile_news_interactions')->insert(['news_id' => $id, 'user_type' => $type, 'user_id' => $userId, 'comment' => null, 'created_at' => date('Y-m-d H:i:s')]);
        return $this->json(['status' => 'success']);
    }

    public function commentNews(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) return $auth['error'];
        $comment = trim((string) (($this->request->getJSON(true) ?: [])['comment'] ?? ''));
        if ($comment === '' || strlen($comment) > 2000) return $this->json(['status' => 'error', 'message' => 'أدخل تعليقاً صالحاً.'], 422);
        $this->ensureNewsInteractionsTable();
        $this->db->table('mobile_news_interactions')->insert(['news_id' => $id, 'user_type' => $auth['type'], 'user_id' => (int) $auth['user']->id, 'comment' => $comment, 'created_at' => date('Y-m-d H:i:s')]);
        return $this->json(['status' => 'success']);
    }

    public function newsItem(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) return $auth['error'];
        $this->ensureNewsInteractionsTable();
        $item = $this->db->table('news')->select('news.id, news.name, news.post_date, news.post_content, news.activity_id, activities.name as activity_name')->join('activities', 'activities.id = news.activity_id', 'left')->where('news.id', $id)->get()->getRow();
        if (!$item) return $this->json(['status' => 'error', 'message' => 'الخبر غير موجود.'], 404);
        $comments = $this->db->table('mobile_news_interactions')->where('news_id', $id)->where('comment IS NOT NULL', null, false)->orderBy('id', 'DESC')->get()->getResultArray();
        return $this->json(['status' => 'success', 'data' => ['id' => (int) $item->id, 'name' => $item->name, 'post_date' => $item->post_date, 'post_content' => $item->post_content, 'activity_name' => $item->activity_name, 'image_url' => $item->activity_id ? $this->activityImageUrl((int) $item->activity_id) : null, 'comments' => $comments]]);
    }

    public function activity(int $id)
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        $activity = $this->db->table('activities')->where('id', $id)->get()->getRow();
        if (!$activity) {
            return $this->json(['status' => 'error', 'message' => 'Activity not found.'], 404);
        }

        return $this->json([
            'status' => 'success',
            'data' => $this->activityPayload($activity, $auth['type'] === 'volunteer' ? (int) $auth['user']->id : null),
        ]);
    }

    public function myActivities()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'volunteer') {
            return $this->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $activities = $this->db->table('volunteer_activities')
            ->select('volunteer_activities.id as enrollment_id, volunteer_activities.status as enrollment_status, activities.*, cities.name as city_name')
            ->join('activities', 'activities.id = volunteer_activities.activity_id')
            ->join('cities', 'cities.id = activities.city_id', 'left')
            ->where('volunteer_activities.volunteer_id', (int) $auth['user']->id)
            ->orderBy('volunteer_activities.id', 'DESC')
            ->get()
            ->getResult();

        $data = array_map(function ($activity) use ($auth) {
            $payload = $this->activityPayload($activity, (int) $auth['user']->id);
            $payload['enrollment_id'] = isset($activity->enrollment_id) ? (int) $activity->enrollment_id : null;
            $payload['enrollment_status'] = isset($activity->enrollment_status) ? (int) $activity->enrollment_status : null;
            return $payload;
        }, $activities);

        return $this->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function enroll()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'volunteer') {
            return $this->json([
                'status' => 'error',
                'message' => 'Only volunteer accounts can enroll in activities.',
            ], 403);
        }

        $json = $this->request->getJSON();
        $activityId = isset($json->activity_id) ? (int) $json->activity_id : 0;
        if ($activityId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'Activity id is required.'], 422);
        }

        $activity = $this->db->table('activities')->where('id', $activityId)->get()->getRow();
        if (!$activity) {
            return $this->json(['status' => 'error', 'message' => 'Activity not found.'], 404);
        }

        if (strtotime((string) $activity->date_from) < strtotime($this->currentDate())) {
            return $this->json(['status' => 'error', 'message' => 'This activity is no longer open for enrollment.'], 422);
        }

        $existing = $this->db->table('volunteer_activities')
            ->where('volunteer_id', (int) $auth['user']->id)
            ->where('activity_id', $activityId)
            ->get()
            ->getRow();

        if ($existing) {
            return $this->json([
                'status' => 'success',
                'message' => 'You are already enrolled in this activity.',
                'data' => [
                    'enrollment_id' => (int) $existing->id,
                    'status' => isset($existing->status) ? (int) $existing->status : 0,
                ],
            ]);
        }

        $insert = [
            'volunteer_id' => (int) $auth['user']->id,
            'activity_id' => $activityId,
            'status' => 0,
        ];

        $this->db->table('volunteer_activities')->insert($insert);
        $enrollmentId = $this->db->insertID();

        $this->sendEnrollmentNotifications($auth['user'], $activity);

        return $this->json([
            'status' => 'success',
            'message' => 'Enrollment request submitted successfully.',
            'data' => [
                'enrollment_id' => $enrollmentId,
                'status' => 0,
            ],
        ]);
    }

    public function unenroll()
    {
        $auth = $this->authenticate();
        if ($auth['error']) {
            return $auth['error'];
        }

        if ($auth['type'] !== 'volunteer') {
            return $this->json([
                'status' => 'error',
                'message' => 'Only volunteer accounts can unenroll from activities.',
            ], 403);
        }

        $json = $this->request->getJSON();
        $activityId = isset($json->activity_id) ? (int) $json->activity_id : 0;
        if ($activityId <= 0) {
            return $this->json(['status' => 'error', 'message' => 'Activity id is required.'], 422);
        }

        $activity = $this->db->table('activities')->where('id', $activityId)->get()->getRow();
        if (!$activity) {
            return $this->json(['status' => 'error', 'message' => 'Activity not found.'], 404);
        }

        $builder = $this->db->table('volunteer_activities')
            ->where('volunteer_id', (int) $auth['user']->id)
            ->where('activity_id', $activityId);

        $enrollment = $builder->get()->getRow();
        if (!$enrollment) {
            return $this->json(['status' => 'error', 'message' => 'Enrollment record not found.'], 404);
        }

        $builder->delete();
        $this->sendUnenrollmentNotifications($auth['user'], $activity);

        return $this->json([
            'status' => 'success',
            'message' => 'Enrollment removed successfully.',
        ]);
    }

    private function sendEnrollmentNotifications(object $volunteer, object $activity): void
    {
        $city = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        $template = $this->getSetting('msg_enrollment');
        if (!$template) {
            $template = 'مرحباً بك في *منصة أنا متطوع*! 🌟

يسرنا إبلاغك أنه تم تسجيلك بنجاح في النشاط التطوعي الذي اخترته.
طلبك الآن قيد المراجعة من قِبل الإدارة، وسيتم إعلامك فور الموافقة عليه.

📌 تفاصيل النشاط:
- اسم النشاط: {activity_name}
- المدة الزمنية : {activity_date}
- المنظمة : {activity_organisation}
- المدينة : {city_name}
';
        }

        $message = str_replace(
            ['{activity_name}', '{activity_date}', '{activity_organisation}', '{city_name}'],
            [$activity->name ?? '', $activity->date_from ?? '', $activity->organisation ?? '', $city->name ?? ''],
            $template
        );

        if ($this->notificationsender->shouldSend('enroll', 'user')) {
            $this->notificationsender->sendText([$volunteer->phone], $message);
        }

        if ($this->notificationsender->shouldSend('enroll', 'admin')) {
            $adminMsg = "📝 *طلب انضمام جديد لنشاط!*\n\n";
            $adminMsg .= "👤 المتطوع: " . ($volunteer->name ?? '') . "\n";
            $adminMsg .= "🎯 النشاط: " . ($activity->name ?? '') . "\n";
            $adminMsg .= "🏢 المنظمة: " . ($activity->organisation ?? '') . "\n";
            $this->notificationsender->sendToAdmin($adminMsg);
        }
    }

    private function sendUnenrollmentNotifications(object $volunteer, object $activity): void
    {
        $template = $this->getSetting('msg_unenrollment');
        if (!$template) {
            $template = 'مرحباً بك في *منصة أنا متطوع*! 🌟

نبلغك أنه تم بنجاح إلغاء تسجيلك في النشاط التطوعي الذي اخترته.
يمكنك إعادة الطلب في أي وقت ترغب فيه بالانضمام لهذا النشاط ! .
';
        }

        if ($this->notificationsender->shouldSend('enroll', 'user')) {
            $this->notificationsender->sendText([$volunteer->phone], $template);
        }

        if ($this->notificationsender->shouldSend('enroll', 'admin')) {
            $adminMsg = "🔔 *إلغاء تسجيل من التطبيق*\n\n";
            $adminMsg .= "👤 المتطوع: " . ($volunteer->name ?? '') . "\n";
            $adminMsg .= "🎯 النشاط: " . ($activity->name ?? '') . "\n";
            $this->notificationsender->sendToAdmin($adminMsg);
        }
    }
}
