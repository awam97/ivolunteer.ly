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

    private function getSetting(string $key, string $default = ''): string
    {
        $row = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
        return $row ? (string) $row->setting_value : $default;
    }

    private function createToken(object $volunteer): string
    {
        $payload = [
            'id' => (int) $volunteer->id,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60),
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $encodedPayload = $this->base64UrlEncode($payloadJson);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $volunteer->password, true));

        return $encodedPayload . '.' . $signature;
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
        if (!is_array($payload) || !isset($payload['id'], $payload['exp'])) {
            return false;
        }

        $volunteer = $this->db->table('volunteers')->where('id', (int) $payload['id'])->get()->getRow();
        if (!$volunteer) {
            return false;
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $volunteer->password, true));
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        return [$payload, $volunteer];
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
            return [null, $this->json(['status' => 'error', 'message' => 'Missing or invalid token.'], 401)];
        }

        [$encodedPayload, $signature] = explode('.', $token, 2);
        $decodedPayload = $this->base64UrlDecode($encodedPayload);
        if ($decodedPayload === false) {
            return [null, $this->json(['status' => 'error', 'message' => 'Invalid token payload.'], 401)];
        }

        $payload = json_decode($decodedPayload, true);
        if (!is_array($payload) || !isset($payload['id'], $payload['exp'])) {
            return [null, $this->json(['status' => 'error', 'message' => 'Invalid token payload.'], 401)];
        }

        if ((int) $payload['exp'] < time()) {
            return [null, $this->json(['status' => 'error', 'message' => 'Session expired.'], 401)];
        }

        $volunteer = $this->db->table('volunteers')->where('id', (int) $payload['id'])->get()->getRow();
        if (!$volunteer) {
            return [null, $this->json(['status' => 'error', 'message' => 'Volunteer not found.'], 401)];
        }

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, (string) $volunteer->password, true));
        if (!hash_equals($expectedSignature, $signature)) {
            return [null, $this->json(['status' => 'error', 'message' => 'Invalid token signature.'], 401)];
        }

        return [$volunteer, null];
    }

    private function getAuthenticatedVolunteer(): array
    {
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return [null, $error];
        }

        return [$volunteer, null];
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

    public function login()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->identifier, $json->password)) {
            return $this->json(['status' => 'error', 'message' => 'Identifier and password are required.'], 422);
        }

        $identifier = trim((string) $json->identifier);
        $password = (string) $json->password;
        if ($identifier === '' || $password === '') {
            return $this->json(['status' => 'error', 'message' => 'Identifier and password are required.'], 422);
        }

        $volunteer = $this->db->table('volunteers')
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('phone', $identifier)
            ->get()
            ->getRow();

        if (!$volunteer || !password_verify($password, (string) $volunteer->password)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid volunteer credentials.'], 401);
        }

        $token = $this->createToken($volunteer);

        return $this->json([
            'status' => 'success',
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'volunteer' => $this->volunteerPayload($volunteer),
            ],
        ]);
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

        [, $volunteer] = $decoded;
        $newToken = $this->createToken($volunteer);

        return $this->json([
            'status' => 'success',
            'message' => 'Session refreshed successfully.',
            'data' => [
                'token' => $newToken,
                'volunteer' => $this->volunteerPayload($volunteer),
            ],
        ]);
    }

    public function logout()
    {
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
        }

        return $this->json([
            'status' => 'success',
            'message' => 'Logout successful.',
            'data' => [
                'volunteer_id' => (int) $volunteer->id,
            ],
        ]);
    }

    public function me()
    {
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
        }

        $volunteerId = (int) $volunteer->id;
        $totalActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->countAllResults();
        $approvedActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->where('status', 1)->countAllResults();
        $completedActivities = $this->db->table('volunteer_activities')->where('volunteer_id', $volunteerId)->where('status', 2)->countAllResults();

        return $this->json([
            'status' => 'success',
            'data' => [
                'volunteer' => $this->volunteerPayload($volunteer),
                'stats' => [
                    'total_activities' => $totalActivities,
                    'approved_activities' => $approvedActivities,
                    'completed_activities' => $completedActivities,
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
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
        }

        $volunteerId = (int) $volunteer->id;
        $defaultCityId = isset($volunteer->city_id) ? (int) $volunteer->city_id : null;
        $cityId = $this->request->getGet('city_id');
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $builder = $this->db->table('activities')
            ->select('activities.*, cities.name as city_name')
            ->join('cities', 'cities.id = activities.city_id', 'left')
            ->join('volunteer_activities', 'volunteer_activities.activity_id = activities.id AND volunteer_activities.volunteer_id = ' . $volunteerId, 'left')
            ->where('activities.date_from >=', $this->currentDate())
            ->orderBy('activities.date_from', 'ASC');

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

    public function activity(int $id)
    {
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
        }

        $activity = $this->db->table('activities')->where('id', $id)->get()->getRow();
        if (!$activity) {
            return $this->json(['status' => 'error', 'message' => 'Activity not found.'], 404);
        }

        return $this->json([
            'status' => 'success',
            'data' => $this->activityPayload($activity, (int) $volunteer->id),
        ]);
    }

    public function myActivities()
    {
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
        }

        $activities = $this->db->table('volunteer_activities')
            ->select('volunteer_activities.id as enrollment_id, volunteer_activities.status as enrollment_status, activities.*, cities.name as city_name')
            ->join('activities', 'activities.id = volunteer_activities.activity_id')
            ->join('cities', 'cities.id = activities.city_id', 'left')
            ->where('volunteer_activities.volunteer_id', (int) $volunteer->id)
            ->orderBy('volunteer_activities.id', 'DESC')
            ->get()
            ->getResult();

        $data = array_map(function ($activity) use ($volunteer) {
            $payload = $this->activityPayload($activity, (int) $volunteer->id);
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
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
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
            ->where('volunteer_id', (int) $volunteer->id)
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
            'volunteer_id' => (int) $volunteer->id,
            'activity_id' => $activityId,
            'status' => 0,
        ];

        $this->db->table('volunteer_activities')->insert($insert);
        $enrollmentId = $this->db->insertID();

        $this->sendEnrollmentNotifications($volunteer, $activity);

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
        [$volunteer, $error] = $this->authenticate();
        if ($error) {
            return $error;
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
            ->where('volunteer_id', (int) $volunteer->id)
            ->where('activity_id', $activityId);

        $enrollment = $builder->get()->getRow();
        if (!$enrollment) {
            return $this->json(['status' => 'error', 'message' => 'Enrollment record not found.'], 404);
        }

        $builder->delete();
        $this->sendUnenrollmentNotifications($volunteer, $activity);

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
