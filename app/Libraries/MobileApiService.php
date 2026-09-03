<?php

namespace App\Libraries;

use App\Models\NotificationSender;

class MobileApiService
{
    protected $db;
    protected $request;
    protected $response;
    protected $notificationsender;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        $this->notificationsender = new NotificationSender();
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
        if ($volunteer && $activity && $this->notificationsender->shouldSend('status', 'user')) {
            $templates = [
                1 => 'تمت الموافقة على طلب انضمامك إلى النشاط: {activity_name}.',
                2 => 'تم تسجيل مشاركتك كمكتملة في النشاط: {activity_name}.',
                3 => 'نأسف، تم رفض طلب انضمامك إلى النشاط: {activity_name}.',
            ];
            $message = str_replace('{activity_name}', (string) $activity->name, $templates[$status] ?? 'تم تحديث حالة طلبك للنشاط: {activity_name}.');
            $this->notificationsender->sendText([$volunteer->phone], $message);
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Request updated successfully.',
        ]);
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
