<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use App\Models\DataModel;
use App\Models\NotificationSender;
use App\Models\Translate;
use Picqer\src\BarcodeGeneratorPNG;

class Volunteer extends BaseController
{
    protected $db;
    protected $request;
    protected $response;
    protected $notificationsender;
    protected $session;
    protected $volunteer_id;
    protected $login_type;
    protected $data;
    protected $translate;
    protected $language;
    protected $admin_id;

    private function getSetting($key, $default = '')
    {
        $setting = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
        return $setting ? $setting->setting_value : $default;
    }

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        $this->notificationsender = new NotificationSender();
        $this->session = session();
        $this->volunteer_id = $this->session->get('user_id');
        $this->login_type = $this->session->get('login_type');
        $this->translate = new Translate();
        $this->language = 'ar'; // Default for volunteers
        if ($this->volunteer_id) {
            $DataModel = new DataModel();
            $this->data = $DataModel;
        }
    }

    public function page(string $page_name, string $page_title, array $data = [], $landing_page = 'index')
    {
        if ($this->login_type !== 'Volunteer') {
            return redirect()->to(base_url());
        }
        $common_data = [
            'db' => $this->db,
            'volunteer_id' => $this->volunteer_id,
            'language' => $this->language,
            'translate' => $this->translate,
            'page_title' => $page_title,
            'page_name' => $page_name,
        ];
        $page_data = array_merge($common_data, $data);
        return view('Volunteer/' . $landing_page, $page_data);
    }

    public function dashboard()
    {
        $citiesData = $this->db->table('cities')->get()->getResult();
        $activitiesPerCity = [];
        foreach ($citiesData as $city) {
            $activityCount = $this->db->table('activities')->where('city_id', $city->id)->countAllResults();
            $activitiesPerCity[] = $activityCount;
        }
        $data = [
            'cities_data' => $citiesData,
            'volunteers' => $this->db->table('volunteers')->countAllResults(),
            'admins' => $this->db->table('admin')->countAllResults(),
            'cities' => $this->db->table('cities')->countAllResults(),
            'activities' => $this->db->table('activities')->countAllResults(),
            'activitiesPerCity' => $activitiesPerCity,
        ];
        return $this->page('dashboard', 'لوحة التحكم', $data);
    }

    public function profile()
    {
        $entityData = $this->getEntityData('volunteers');
        $data = [
            'entityName' => 'volunteers',
            'entityData' => $entityData,
            'entities' => $this->db->table('volunteers')->get()->getResult(),
        ];
        return $this->page('profile', 'ملفي الشخصي', $data);
    }

    public function certificates()
    {
        $entityData = $this->getEntityData('volunteers');
        $data = [
            'entityName' => 'volunteers',
            'entityData' => $entityData,
            'entities' => $this->db->table('volunteers')->get()->getResult(),
        ];
        return $this->page('certificates', 'الشهادات', $data);
    }

    public function terms()
    {
        return $this->page('terms', 'الشروط و الأحكام');
    }

    public function certificate()
    {
        $id = $this->request->getGet('id');
        $generator = new \Picqer\src\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
        $base64 = base64_encode($barcode);
        $image = '<img style="padding-left:30px" src="data:image/png;base64,' . $base64 . '" />';
        if (!$id) {
            return $this->response->setStatusCode(400)->setBody('ID is required.');
        }
        $data = ['barcode' => $image, 'entityName' => 'volunteer_activities', 'entities' => $this->db->table('volunteer_activities')->where('id', $id)->get()->getResult(), 'id' => $id,];
        return $this->page('certificate', 'شهادة إتمام نشاط', $data, 'blank');
    }

    public function public_certificate()
    {
        $id = $this->request->getGet('id');
        $generator = new \Picqer\src\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
        $base64 = base64_encode($barcode);
        $image = '<img style="padding-left:30px" src="data:image/png;base64,' . $base64 . '" />';
        if (!$id) {
            return $this->response->setStatusCode(400)->setBody('ID is required.');
        }
        $data = ['barcode' => $image, 'entityName' => 'volunteer_activities', 'entities' => $this->db->table('volunteer_activities')->where('id', $id)->where('volunteer_id', $this->volunteer_id)->get()->getResult(), 'id' => $id,];
        return $this->page('public_certificate', 'شهادة إتمام نشاط', $data, 'blank');
    }

    public function activities()
    {
        $entityData = $this->getEntityData('activities');
        $data = [
            'entityName' => 'activities',
            'entityData' => $entityData,
            'entities' => $this->db->table('activities')->get()->getResult(),
        ];
        return $this->page('activities', 'النشاطات المتاحة', $data);
    }

    public function news_page()
    {
        $id = $this->request->getGet('id');
        if (!$id) {
            return redirect()->to(base_url('Volunteer/dashboard'));
        }
        $news = $this->db->table('news')->where('id', $id)->get()->getRow();
        if (!$news) {
            return redirect()->to(base_url('Volunteer/dashboard'));
        }
        $data = [
            'news' => $news,
            'news_id' => $id
        ];
        return $this->page('news_page', $news->name, $data);
    }

    public function news()
    {
        $news = $this->db->table('news')
            ->select('id as news_id, name, post_date, activity_id')
            ->orderBy('post_date', 'DESC')
            ->get()->getResult();
        $data = [
            'news' => $news
        ];
        return $this->page('news', 'مكتبة الأخبار والإعلانات', $data);
    }

    public function activity()
    {
        $id = $this->request->getGet('id');
        if (!$id) {
            return $this->response->setStatusCode(400)->setBody('ID is required.');
        }
        $activity = $this->db->table('activities')->where('id', $id)->get()->getRow();
        if (!$activity) {
            return $this->response->setStatusCode(404)->setBody('Activity not found.');
        }
        $cityRow = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        $cityName = $cityRow->name;
        $data = ['entityName' => 'volunteer_activities', 'id' => $id,];
        return $this->page('activity', $activity->name, $data);
    }

    public function my_activities()
    {
        $entityData = $this->getEntityData('volunteer_activities');
        $data = [
            'entityName' => 'volunteer_activities',
            'entityData' => $entityData,
            'entities' => $this->db->table('volunteer_activities')->get()->getResult(),
        ];
        return $this->page('my_activities', 'نشاطاتي الخاصة', $data);
    }

    public function activity_enroll_notification()
    {
        $jsonData = (array) $this->request->getJSON();
        $volunteer_id = $jsonData['volunteer_id'] ?? null;
        $activity_id = $jsonData['activity_id'] ?? null;
        
        $volunteer = $this->db->table('volunteers')->where('id', $volunteer_id)->get()->getRow();
        $activity = $this->db->table('activities')->where('id', $activity_id)->get()->getRow();
        $city = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        $recipients = [$volunteer->phone];

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

شكراً لاهتمامك بالعطاء وخدمة المجتمع.  

مع تحيات فريق *منصة أنا متطوع* 💚';
        }

        $message = str_replace(
            ['{activity_name}', '{activity_date}', '{activity_organisation}', '{city_name}'],
            [$activity->name, $activity->date_from, $activity->organisation, $city->name],
            $template
        );

        if ($this->notificationsender->shouldSend('enroll', 'user')) {
            $this->notificationsender->sendText($recipients, $message);
        }

        if ($this->notificationsender->shouldSend('enroll', 'admin')) {
            $adminMsg = "📝 *طلب انضمام جديد لنشاط!*\n\n";
            $adminMsg .= "👤 المتطوع: " . $volunteer->name . "\n";
            $adminMsg .= "🎯 النشاط: " . $activity->name . "\n";
            $adminMsg .= "🏢 المنظمة: " . $activity->organisation . "\n";
            $this->notificationsender->sendToAdmin($adminMsg);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    public function activity_unenroll_notification()
    {
        $jsonData = (array) $this->request->getJSON();
        $volunteer_id = $jsonData['volunteer_id'] ?? null;
        $recipients = [$this->db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->phone];

        $template = $this->getSetting('msg_unenrollment');
        if (!$template) {
            $template = 'مرحباً بك في *منصة أنا متطوع*! 🌟  

نبلغك أنه تم بنجاح إلغاء تسجيلك في النشاط التطوعي الذي اخترته.  
يمكنك إعادة الطلب في أي وقت ترغب فيه بالانضمام لهذا النشاط ! .  

شكراً لاهتمامك بالعطاء وخدمة المجتمع.  

مع تحيات فريق *منصة أنا متطوع* 💚';
        }
        $message = $template;
        return $results = $this->notificationsender->sendText($recipients, $message);
    }

    public function add_entity()
    {
        $jsonData = (array) $this->request->getJSON(); // Convert to array
        $tableName = $jsonData['table_name'] ?? null;
        $entityFields = $jsonData['fields_entity'] ?? null;

        if (!$tableName) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid table name.']);
        }

        try {
            $filePath = null;
            $entityFields = (array) ($entityFields ?? []);

            if (isset($entityFields['file'])) {
                $base64File = $entityFields['file'];
                $originalFilename = $entityFields['original_filename'] ?? 'unknown_file';
                unset($entityFields['file'], $entityFields['original_filename']);

                $this->addDynamicFieldsFromJson($tableName, $entityFields, $originalFilename);

                if (empty($entityFields)) {
                    $entityFields = ['id' => null];
                }
                $this->data->setFieldsAndPrimaryKey(array_keys($entityFields), 'id')->table($tableName);
                $insertId = $this->data->insertData($entityFields);

                if (preg_match('/^data:([a-zA-Z0-9\/\+]+);base64,/', $base64File, $type)) {
                    $base64File = substr($base64File, strpos($base64File, ',') + 1);
                    $mimeType = strtolower($type[1]);
                    $decodedFile = base64_decode($base64File);

                    if ($decodedFile === false) {
                        throw new \RuntimeException('Base64 decoding failed.');
                    }

                    $fileExtension = explode('/', $mimeType)[1] ?? '';
                    if (!$fileExtension) {
                        throw new \RuntimeException('Failed to determine file extension.');
                    }

                    $filePath = FCPATH . 'uploads/' . $tableName . '_files/' . $insertId . '.' . $fileExtension;
                    if (!is_dir(FCPATH . 'uploads/' . $tableName . '_files/')) {
                        mkdir(FCPATH . 'uploads/' . $tableName . '_files/', 0777, true);
                    }

                    if (!file_put_contents($filePath, $decodedFile)) {
                        throw new \RuntimeException('Failed to save the file.');
                    }
                } else {
                    throw new \RuntimeException('Invalid file format.');
                }
            } else if ($entityFields) {
                $this->addDynamicFieldsFromJson($tableName, $entityFields);
                $this->data->setFieldsAndPrimaryKey(array_keys($entityFields), 'id')->table($tableName);
                $insertId = $this->data->insertData($entityFields);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'No fields or file provided.']);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'id' => $insertId ?? null,
                'message' => 'Entity added successfully.',
                'file_path' => $filePath
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function addDynamicFieldsFromJson(string $tableName, array &$entityFields, string $filename = ''): void
    {
        $configFile = APPPATH . 'Config/additional_fields.json';
        if (!file_exists($configFile)) {
            throw new \RuntimeException('Configuration file not found.');
        }
        $configData = json_decode(file_get_contents($configFile), true);
        if (!isset($configData[$tableName])) {
            return;
        }
        foreach ($configData[$tableName] as $field => $value) {
            if ($value === '{{date_now}}') {
                $value = date('Y-m-d H:i:s');
            }
            if ($value === '{{login_user_id}}') {
                $value = $this->admin_id;
            }
            if ($value === '{{filename}}') {
                $value = $filename;
            }
            $entityFields[$field] = $value;
        }
    }

    public function update_post_entity()
    {
        $postData = $this->request->getPost();
        return $this->update_entity($postData);
    }

    public function update_json_entity()
    {
        $postData = $this->request->getJSON(true);
        return $this->update_entity($postData);
    }

    public function update_entity($postData)
    {
        $tableName = $postData['table'] ?? null;
        $entityId = $postData['id_entity'] ?? null;
        $entityFields = $postData;
        unset($entityFields['table'], $entityFields['id_entity'], $entityFields['file']);
        if (!$tableName || !$entityId || empty($entityFields)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Table name, entity ID, and fields are required.']);
        }
        try {
            $uploadedFile = null;
            foreach ($this->request->getFiles() as $inputName => $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $uploadedFile = $file;
                    break;
                }
            }
            if ($uploadedFile) {
                $uploadDir = FCPATH . "uploads/" . $tableName . "_files/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filePattern = $uploadDir . $entityId . ".*";
                foreach (glob($filePattern) as $existingFile) {
                    if (file_exists($existingFile)) {
                        unlink($existingFile);
                    }
                }
                $fileExtension = $uploadedFile->getClientExtension();
                $fileName = $entityId . "." . $fileExtension;
                $uploadedFile->move($uploadDir, $fileName);
            }
            foreach ($entityFields as $key => $value) {
                if (is_array($value)) {
                    $entityFields[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                if ($key === 'password') {
                    if (!empty($value)) {
                        $entityFields[$key] = password_hash($value, PASSWORD_BCRYPT);
                    } else {
                        unset($entityFields[$key]);
                    }
                }
            }
            $this->data->table($tableName);
            $isUpdated = $this->data->updateData($entityId, $entityFields);
            if ($isUpdated) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'تم تحديث البيانات بنجاح']);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'حدثت مشكلة أثناء تحديث البيانات']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete_entity()
    {
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $entityId = $jsonData->id_entity ?? null;
        $conditions = $jsonData->conditions ?? [];

        if (!$tableName || (!$entityId && empty($conditions))) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Table name and either entity ID or conditions are required.']);
        }

        try {
            $this->data->table($tableName);

            // Add conditions or fallback to ID
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $this->data->where($field, $value);
                }
            } elseif ($entityId) {
                $this->data->where('id', $entityId);
            }

            $isDeleted = $this->data->delete();

            // Handle file deletions only if entity ID is provided
            if ($entityId) {
                $directoryPath = FCPATH . "uploads/{$tableName}_files/";
                $files = glob($directoryPath . "{$entityId}.*");
                foreach ($files as $filePath) {
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            if ($isDeleted) {
                return $this->response->setJSON(['status' => 'success', 'message' => $tableName]);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'حدثت مشكلة أثناء حذف البيانات']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'An error occurred during deletion: ' . $e->getMessage()]);
        }
    }

    public function getEntityData($tablename)
    {
        $cities = $this->db->table('cities')->select('id, name')->get()->getResultArray();
        $citiesOptions = [];
        foreach ($cities as $city) {
            $citiesOptions[$city['id']] = $city['name'];
        }
        $genders = $this->db->table('genders')->select('id, name')->get()->getResultArray();
        $GendersOptions = [];
        foreach ($genders as $gender) {
            $GendersOptions[$gender['id']] = $gender['name'];
        }
        $EntityData =
            [
                'cities' => [
                    'name' => [
                        'id' => 'name',
                        'placeholder' => 'أدخل اسم المدينة',
                        'type' => 'text',
                        'required' => true
                    ],
                ],
                'volunteers' => [
                    'name' => [
                        'id' => 'name',
                        'placeholder' => 'أدخل الإسم',
                        'type' => 'text',
                        'required' => true
                    ],
                    'birthdate' => [
                        'id' => 'birthdate',
                        'placeholder' => 'تاريخ الميلاد',
                        'type' => 'text',
                        'required' => true
                    ],
                    'gender' => [
                        'id' => 'gender',
                        'placeholder' => 'الجنس',
                        'type' => 'select',
                        'options' => $GendersOptions,
                        'required' => true
                    ],
                    'phone' => [
                        'id' => 'phone',
                        'placeholder' => 'أدخل رقم الهاتف',
                        'type' => 'text',
                        'required' => true
                    ],
                    'username' => [
                        'id' => 'username',
                        'placeholder' => 'أدخل اسم المستخدم',
                        'type' => 'text',
                        'required' => true
                    ],
                    'email' => [
                        'id' => 'email',
                        'placeholder' => 'أدخل البريد الالكتروني',
                        'type' => 'email',
                        'required' => true
                    ],
                    'password' => [
                        'id' => 'password',
                        'placeholder' => 'كلمة المرور الجديدة',
                        'type' => 'password',
                        'required' => false
                    ],
                    'identity' => [
                        'id' => 'identity',
                        'placeholder' => 'أدخل التعريف الشخصي',
                        'type' => 'text',
                        'required' => true
                    ],
                    'academic_value' => [
                        'id' => 'academic_value',
                        'placeholder' => 'المؤهل العلمي / التخصص',
                        'type' => 'text',
                        'required' => true
                    ],
                    'hobbies' => [
                        'id' => 'hobbies',
                        'placeholder' => 'الهوايات',
                        'type' => 'text',
                        'required' => true
                    ],
                    'language' => [
                        'id' => 'language',
                        'placeholder' => 'اختر اللغة',
                        'type' => 'select',
                        'options' => ['ar' => 'اللغة العربية', 'en' => 'English Language',],
                        'required' => true
                    ],
                    'image' => [
                        'id' => 'image',
                        'placeholder' => 'اختر صورة شخصية',
                        'type' => 'file',
                        'required' => false
                    ],

                ],
                'activities' => [
                    'name' => [
                        'id' => 'name',
                        'placeholder' => 'أدخل عنوان النشاط',
                        'type' => 'text',
                        'required' => true
                    ],
                    'organisation' => [
                        'id' => 'organisation',
                        'placeholder' => 'أدخل اسم المنظمة',
                        'type' => 'text',
                        'required' => true
                    ],
                    'city_id' => [
                        'id' => 'city_id',
                        'type' => 'select',
                        'placeholder' => 'المدينة',
                        'options' => $citiesOptions,
                        'required' => true
                    ],
                    'date_from' => [
                        'id' => 'date_from',
                        'placeholder' => 'تاريخ بدء النشاط',
                        'type' => 'date',
                        'required' => true
                    ],
                    'date_to' => [
                        'id' => 'date_to',
                        'placeholder' => 'تاريخ نهاية النشاط',
                        'type' => 'date',
                        'required' => true
                    ],
                    'description' => [
                        'id' => 'description',
                        'placeholder' => 'وصف النشاط',
                        'type' => 'textarea',
                        'required' => true
                    ],
                    'required_files' => [
                        'id' => 'required_files',
                        'placeholder' => 'الملفات المطلوبة',
                        'type' => 'textarea',
                        'required' => true
                    ],
                    'image' => [
                        'id' => 'image',
                        'placeholder' => 'صورة غلاف للنشاط',
                        'type' => 'file',
                        'required' => false
                    ],
                    'transportation' => [
                        'id' => 'transportation',
                        'placeholder' => 'التكفل بالمواصلات',
                        'options' => ['1', '0'],
                        'type' => 'radio',
                        'required' => false
                    ],
                    'residency' => [
                        'id' => 'residency',
                        'placeholder' => 'التكفل بالإقامة',
                        'options' => ['1', '0'],
                        'type' => 'radio',
                        'required' => false
                    ],
                    'expenses' => [
                        'id' => 'expenses',
                        'placeholder' => 'التكفل بالإعاشة',
                        'options' => ['1', '0'],
                        'type' => 'radio',
                        'required' => false
                    ],
                    'training' => [
                        'id' => 'training',
                        'placeholder' => 'التكفل بالتدريب',
                        'options' => ['1', '0'],
                        'type' => 'radio',
                        'required' => false
                    ],
                ],
            ];
        return $EntityData[$tablename] ?? [];
    }

    public function data_grap()
    {
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $row_id = $jsonData->id_entity ?? null;
        $data = $this->db->table($tableName)->where('id', $row_id)->get()->getRow();
        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        $session->setFlashdata('logout_notification', 'logged_out');
        return redirect()->to(base_url());
    }
}
