<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use App\Models\DataModel;
use App\Models\NotificationSender;
use App\Models\Translate;
use App\Models\FileModel;
use App\Models\AdditionalFieldsModel;
use Picqer\src\BarcodeGeneratorPNG;

class Admin extends BaseController
{
    protected $db;
    protected $session;
    protected $data;
    protected $notificationsender;
    protected $additionalFieldsModel;
    protected $translate;
    protected $filemodel;
    protected $admin_id;
    protected $login_type;
    protected $language;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        $this->session = session();
        $this->data = new DataModel();
        $this->notificationsender = new NotificationSender();
        $this->additionalFieldsModel = new AdditionalFieldsModel();
        $this->translate = new Translate();
        $this->filemodel = new FileModel();
        $this->admin_id = $this->session->get("user_id");
        $this->login_type = $this->session->get("login_type");
        $admin_row = $this->db->table("admin")->where("id", $this->admin_id)->get()->getRow();
        $this->language = $admin_row->language ?? null;
    }
    
    private function checkAdmin()
    {
        if ($this->login_type !== "Admin") {
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    private function page($name,$title,$page_data = [],$landing_page = "index")
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $page_data["db"] = $this->db;
        $page_data["language"] = $this->language;
        $page_data["translate"] = $this->translate;
        $page_data["filemodel"] = $this->filemodel;
        $page_data["admin_id"] = $this->admin_id;
        $page_data["adminData"] = $this->db->table("admin")->where("id", $this->admin_id)->get()->getRow();
        $page_data["page_title"] = $this->translate->translate_phrase($title,$this->language);
        $page_data["page_name"] = $name;
        return view("Admin/" . $landing_page, $page_data);
    }

    public function dashboard()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $citiesData = $this->db->table("cities")->get()->getResult();
        $activitiesQuery = $this->db->table("activities")->select("city_id, COUNT(*) as total_activities")->groupBy("city_id")->get()->getResult();
        $volunteersQuery = $this->db->table("volunteers")->select("city_id, COUNT(*) as total_volunteers")->groupBy("city_id")->get()->getResult();
        $volunteersPerCityMap = [];
        foreach ($volunteersQuery as $row) {$volunteersPerCityMap[$row->city_id] = $row->total_volunteers;}
        $volunteersPerCity = [];
        foreach ($citiesData as $city) {$volunteersPerCity[] = $volunteersPerCityMap[$city->id] ?? 0;}
        $adminsCount = $this->db->table("admin")->countAllResults();
        $citiesCount = count($citiesData);
        $activitiesCount = $this->db->table("activities")->countAllResults();
        $volunteersCount = $this->db->table("volunteers")->countAllResults();
        
        // Volunteer Registration Trends (Current Year)
        $registrationQuery = $this->db->table("volunteers")
            ->select("DATE(created_at) as date, COUNT(*) as count")
            ->where("YEAR(created_at)", date('Y'))
            ->groupBy("DATE(created_at)")
            ->orderBy("date", "ASC")
            ->get()->getResult();

        $registrationLabels = [];
        $registrationData = [];
        foreach ($registrationQuery as $row) {
            $registrationLabels[] = $row->date;
            $registrationData[] = $row->count;
        }

        $topVolunteers = $this->db->table("volunteer_activities")->select("volunteers.id, volunteers.name, SUM(activities.hours) as total_hours")->join("volunteers", "volunteer_activities.volunteer_id = volunteers.id")->join("activities", "volunteer_activities.activity_id = activities.id")->where("volunteer_activities.status", 2)->groupBy("volunteers.id, volunteers.name")->orderBy("total_hours", "DESC")->limit(15)->get()->getResultArray();
        $data = [
            "cities_data" => $citiesData,
            "admins" => $adminsCount,
            "cities" => $citiesCount,
            "activities" => $activitiesCount,
            "volunteers" => $volunteersCount,
            "top_volunteers" => $topVolunteers,
            "volunteersPerCity" => $volunteersPerCity,
            "registrationLabels" => $registrationLabels,
            "registrationData" => $registrationData,
        ];
        return $this->page("dashboard", 'dashboard', $data);
    }

    public function admins()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "admin";
        $entityData = $this->getEntityData($entityName);
        $currentAdmin = $this->db->table($entityName)->where("id", $this->admin_id)->get()->getRow();
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        if ($search) $query->like('name', $search);
        
        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "hidden" => $currentAdmin->owner ?? null,
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("admins", "admins", $data);
    }
    
    public function calendar()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "activities";
        $entityData = $this->getEntityData($entityName);
        $currentAdmin = $this->db->table($entityName)->where("id", $this->admin_id)->get()->getRow();
        $data = ["entityName" => $entityName,"entityData" => $entityData,"hidden"=> $currentAdmin->owner ?? null, "entities"=> $this->db->table($entityName)->get()->getResult(),];
        return $this->page("calendar", "calendar", $data);
    }    

    
    public function volunteers()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "volunteers";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) $query->like('name', $search);
        if ($sort === 'az') $query->orderBy('name', 'ASC');
        elseif ($sort === 'za') $query->orderBy('name', 'DESC');
        elseif ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');

        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("volunteers", "volunteers", $data);
    }

    public function cities()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "cities";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) $query->like('name', $search);
        if ($sort === 'az') $query->orderBy('name', 'ASC');
        elseif ($sort === 'za') $query->orderBy('name', 'DESC');
        elseif ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');

        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("cities", "cities", $data);
    }

    public function news()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "news";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) $query->like('name', $search);
        if ($sort === 'az') $query->orderBy('name', 'ASC');
        elseif ($sort === 'za') $query->orderBy('name', 'DESC');
        elseif ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');

        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "link" => "news_page",
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("news", "news", $data);
    }

    public function profile()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "admin";
        $entityData = $this->getEntityData($entityName);
        $data = ["entityName" => $entityName,"entityData" => $entityData,"entities" => $this->db->table($entityName)->get()->getResult(),];
        return $this->page("profile", "profile", $data);
    }

    public function library()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "library";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) {
            // For library, search in some field? Library might not have 'name' but has 'file'
            // Usually we'd search in file names or something.
            $query->like('id', $search); // Assuming we only have id for now or we search by id?
        }
        if ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');

        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("library", "مكتبة الوسائط", $data);
    }

    public function activities()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "activities";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) $query->like('name', $search);
        if ($sort === 'az') $query->orderBy('name', 'ASC');
        elseif ($sort === 'za') $query->orderBy('name', 'DESC');
        elseif ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');

        $entities_raw = $query->asObject()->paginate(12);
        $cities = $this->db->table("cities")->get()->getResult();
        
        $cityMap = [];
        foreach ($cities as $city) $cityMap[$city->id] = $city->name;

        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "entities" => array_map(function($activity) use ($cityMap) {
                $activity->city_name = $cityMap[$activity->city_id] ?? 'Unknown';
                return $activity;
            }, $entities_raw),
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("activities", "نشاطات", $data);
    }

    public function volunteer_activities()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "volunteer_activities";
        $entityData = $this->getEntityData($entityName);
        $sort = $this->request->getGet('sort');
        $search = $this->request->getGet('search');
        $query = $this->data->table($entityName);
        
        if ($search) $query->like('id', $search); 
        
        if ($sort === 'newest') $query->orderBy('id', 'DESC');
        elseif ($sort === 'oldest') $query->orderBy('id', 'ASC');
        
        $entities = $query->asObject()->paginate(12);
        $data = [
            "entityName" => $entityName,
            "entityData" => $entityData,
            "entities" => $entities,
            "pager" => $this->data->pager,
            "search" => $search
        ];
        return $this->page("table", "نشاطات المتطوعين", $data);
    }
    
    public function activity()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $id = $this->request->getGet("id");
        if (!$id) {
            return $this->response->setStatusCode(400)->setBody("ID is required.");
        }
        else
        {
            $activity = $this->db->table("activities")->where("id", $id)->get()->getRow();
            if (!$activity) {
                return $this->response->setStatusCode(404)->setBody("لم يتم العثور على النشاط");
            }
            $data = ["entities" => $this->db->table("activities")->where("id", $id)->get()->getResult(),"id" => $id,];
            return $this->page("activity", $activity->name, $data);
        }
    }

    public function news_page()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "news";
        $id = $this->request->getGet("id");
        if (!$id) 
        {
            return $this->response->setStatusCode(400)->setBody("ID is required.");
        }
        else
        {
            $news = $this->db->table($entityName)->where("id", $id)->get()->getRow();
            if (!$news) 
            {
                return $this->response->setStatusCode(404)->setBody("Activity not found.");
            }
            $data = ["entityName" => $entityName,"entities" => $this->db->table($entityName)->where("id", $id)->get()->getResult(),"id" => $id,];
            return $this->page("news_page", $news->name, $data);
        }
    }

    public function certificate()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "volunteer_activities";
        $id = $this->request->getGet("id");
        if (!$id) 
        {
            return $this->response->setStatusCode(400)->setBody("ID is required.");
        }
        else
        {
            $generator = new \Picqer\src\BarcodeGeneratorPNG();
            $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
            $image ='<img style="padding-left:30px" src="data:image/png;base64,' .base64_encode($barcode) .'" />';
            $data = ["barcode" => $image,"entityName" => $entityName,"entities" => $this->db->table($entityName)->where("id", $id)->get()->getResult(),"id" => $id,];
            return $this->page("certificate", "شهادة إتمام نشاط", $data, "blank");
        }
    }

    public function public_certificate()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "volunteer_activities";
        $id = $this->request->getGet("id");
        if (!$id) 
        {
            return $this->response->setStatusCode(400)->setBody("ID is required.");
        }
        else
        {
            $generator = new \Picqer\src\BarcodeGeneratorPNG();
            $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
            $image = '<img style="padding-left:30px" src="data:image/png;base64,' .base64_encode($barcode) .'" />';
            $data = ["barcode" => $image,"entityName" => $entityName,"entities" => $this->db->table($entityName)->where("id", $id)->get()->getResult(),"id" => $id,];
            return $this->page("public_certificate","شهادة إتمام نشاط",$data,"blank");
        }
    }

    public function report()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $entityName = "volunteer_activities";
        $id = $this->request->getGet("id");
        if (!$id) 
        {
            return $this->response->setStatusCode(400)->setBody("ID is required.");
        }
        else
        {
            $data = ["entityName" => $entityName,"entities" => $this->db->table($entityName)->where("activity_id", $id)->get()->getResult(),"id" => $id,];
            return $this->page("report", "كشف بيانات المتطوعين", $data, "blank");
        }
    }

    private function loadMessages()
    {
        $path = APPPATH . 'Config/messages.json';
        if (!file_exists($path)) 
        {
            throw new \Exception("Messages file not found.");
        }
        $json = file_get_contents($path);
        return json_decode($json, true);
    }
    
    private function prepareMessage($template, $replacements)
    {
        foreach ($replacements as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    public function updateStatus()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $request = $this->request->getJSON();
        if (!isset($request->id) || !isset($request->status)) 
        {
            return $this->response->setJSON(["success" => false, "message" => "Invalid input"]);
        }
        $id = $request->id;
        $status = $request->status;
        $update = $this->db->table("volunteer_activities")->where("id", $id)->update(["status" => $status]);
        if (!$update) 
        {
            return $this->response->setJSON(["success" => false, "message" => "Failed to update status"]);
        }
        $volunteerActivity = $this->db->table("volunteer_activities")->where("id", $id)->get()->getRow();
        $volunteer = $this->db->table("volunteers")->where("id", $volunteerActivity->volunteer_id)->get()->getRow();
        $activity = $this->db->table("activities")->where("id", $volunteerActivity->activity_id)->get()->getRow();
        $city = $this->db->table("cities")->where("id", $activity->city_id)->get()->getRow();
        $recipients = [$volunteer->phone];
        $messagesData = $this->loadMessages();
        $statusMessages = $messagesData['status_messages'];
        $template = $this->getSetting('msg_status_' . $status);
        if (!$template) 
        {
            return $this->response->setJSON(["success" => false, "message" => "No template defined for this status"]);
        }
        $message = $this->prepareMessage($template, ['activity_name' => $activity->name,'activity_date' => $activity->date_from,'activity_organisation' => $activity->organisation,'city_name' => $city->name,'activity_required_files' => $activity->required_files ?? 'لا يوجد']);
        
        if ($this->notificationsender->shouldSend('status', 'user')) {
            $this->notificationsender->sendText($recipients, $message);
        }
        
        session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تم تحديث حالة طلب التطوع بنجاح']);
        return $this->response->setJSON(["success" => true]);
    }

    public function add_entity()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $insert_notifications = $jsonData->insert_notifications ?? null;
        $notification_type = $jsonData->notification_type ?? null;
        $entityFields = (array) ($jsonData->fields_entity ?? []);
        
        if (!$tableName) {return $this->response->setJSON(["status" => "error","message" => "Invalid table name.",]);}
        try {
            if (isset($entityFields['password'])) {$entityFields['password'] = password_hash($entityFields['password'], PASSWORD_BCRYPT);}
            
            // Extract notification flags if passed inside fields_entity
            if ($insert_notifications === null && isset($entityFields['insert_notifications'])) {
                $insert_notifications = $entityFields['insert_notifications'];
            }
            // If toggle was removed but it is an activity, default to ON (1)
            if ($insert_notifications === null && $tableName === 'activities') {
                $insert_notifications = 1;
            }

            if ($notification_type === null) {
                $notification_type = ($tableName === 'news') ? 'news' : (($tableName === 'activities') ? 'activities' : null);
            }

            // Cleanup fields that are not in the database table
            $dbFields = $entityFields;
            unset($dbFields['insert_notifications'], $dbFields['notification_type'], $dbFields['table']);

            $this->additionalFieldsModel->addDynamicFieldsFromJson($tableName, $dbFields);
            $this->data->setFieldsAndPrimaryKey(array_keys($dbFields), "id")->table($tableName);
            
            $filePath = null;
            if (isset($entityFields["file"])) 
            {
                unset($dbFields["file"], $dbFields["original_filename"]);
                $insertId = $this->data->insertData($dbFields);
                $filePath = $this->filemodel->saveBase64File($jsonData->fields_entity["file"], $tableName, $insertId);
            } else
            {
                $insertId = $this->data->insertData($dbFields);
            }

            if ($insert_notifications == 1 && $notification_type == "activities") {
                $template = $this->getSetting('msg_new_activity', "تم نشر نشاط جديد بعنوان : {activity_name} , سجل دخولك الى المنصة و اكتشف التفاصيل !");
                $message = str_replace('{activity_name}', $entityFields["name"], $template);
                
                $phoneNumbers = $this->db->table("volunteers")->select("phone")->where("city_id", $entityFields["city_id"])->get()->getResultArray();
                $recipients = array_column($phoneNumbers, "phone");
                
                // CRITICAL FIX: If in TEST MODE and no recipients in city, add a dummy to trigger the test redirection
                $mode = $this->db->table('settings')->where('setting_key', 'whatsapp_enabled')->get()->getRow();
                if (empty($recipients) && $mode && $mode->setting_value == 2) {
                    $recipients = ["TEST_TRIGGER"];
                }

                $this->notificationsender->sendTextHandler($recipients, $message);
            }
            if ($insert_notifications == 1 && $notification_type == "news") {
                $message = "تم نشر خبر جديد : " . $entityFields["name"] . " , سجل دخولك الى المنصة و اكتشف التفاصيل !";
                //$phoneNumbers = $this->db->table("volunteers")->select("phone")->get()->getResultArray();
                $phoneNumbers = $this->db->table("volunteers")->select("phone")->where("phone","+393312106740")->get()->getResultArray();
                $recipients = array_column($phoneNumbers, "phone");
                $this->notificationsender->sendTextHandler($recipients, $message);
            }
            
            // Render the new card for SPA update
            $newEntity = $this->db->table($tableName)->where('id', $insertId)->get()->getRow();
            $renderedHtml = view('Admin/partials/entity_card', [
                'entity' => $newEntity,
                'entityName' => $tableName,
                'translate' => $this->translate,
                'language' => $this->language,
                'db' => $this->db
            ]);

            session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تمت إضافة البيانات بنجاح']);
            return $this->response->setJSON([
                "status" => "success",
                "id" => $insertId ?? null,
                "message" => "Entity added successfully.",
                "file_path" => $filePath,
                "rendered_html" => $renderedHtml
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => "error","message" => $e->getMessage(),]);
        }
    }

    public function delete_entity()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $entityId = $jsonData->id_entity ?? null;
        if (!$tableName || !$entityId) 
        {
            return $this->response->setJSON(["status" => "error","message" => "Table name and entity ID are required.",]);
        }
        try {
            $this->data->table($tableName);
            $isDeleted = $this->data->deleteData($entityId);
            $directoryPath = FCPATH . "uploads/{$tableName}_files/";
            $files = glob($directoryPath . "{$entityId}.*");
            if ($isDeleted) {
                foreach ($files as $filePath) 
                {
                    if (is_file($filePath)) 
                    {
                        unlink($filePath);
                    }
                }
                session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تم حذف البيانات بنجاح']);
                return $this->response->setJSON(["status" => "success","message" => $tableName,]);
            } else 
            {
                return $this->response->setJSON(["status" => "error","message" => "حدثت مشكلة أثناء حذف البيانات",]);
            }
        } catch (\Exception $e) 
        {
            return $this->response->setJSON(["status" => "error","message" =>"An error occurred during deletion: " . $e->getMessage(),]);
        }
    }

    public function updateCertificateStatus()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $json = $this->request->getJSON();
        $id = $json->id;
        $field = $json->field;
        $value = $json->value;
        $db = \Config\Database::connect();
        $builder = $db->table("volunteer_activities");
        $builder->where("id", $id)->update([$field => $value]);

        if ($field === 'certificate' && $value == 1) {
            $volunteerActivity = $this->db->table("volunteer_activities")->where("id", $id)->get()->getRow();
            $volunteer = $this->db->table("volunteers")->where("id", $volunteerActivity->volunteer_id)->get()->getRow();
            $activity = $this->db->table("activities")->where("id", $volunteerActivity->activity_id)->get()->getRow();
            
            $template = $this->getSetting('msg_certificate', "مرحباً بك في *منصة أنا متطوع*! 🌟\n\nيسرنا إبلاغك أن شهادة إتمام النشاط التطوعي الخاص بك أصبحت جاهزة الآن! \nيمكنك تحميلها من خلال الدخول على حسابك الشخصي وولوج قسم الشهادات.\n\n📌 تفاصيل النشاط:\n- اسم النشاط: {activity_name}\n\nشكراً لجهودك وعطائك المستمر. ✨\n\nمع تحيات فريق *منصة أنا متطوع* 💚");
            $message = str_replace('{activity_name}', $activity->name, $template);
            
            if ($this->notificationsender->shouldSend('cert', 'user')) {
                $this->notificationsender->sendTexts($volunteer->phone, $message);
            }
        }

        return $this->response->setJSON(["success" => true]);
    }

    public function bulk_delete()
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $ids = $jsonData->ids ?? null;
        $selectAll = $jsonData->selectAll ?? false;

        if (!$tableName || (!$selectAll && empty($ids))) {
            return $this->response->setJSON(["status" => "error", "message" => "Table name and selection are required."]);
        }

        try {
            $this->data->table($tableName);
            if ($selectAll) {
                $search = $jsonData->search ?? null;
                $builder = $this->db->table($tableName);
                if ($search) {
                    // Apply search filter to the delete operation
                    if (in_array($tableName, ['volunteer_activities', 'library'])) {
                        $builder->like('id', $search);
                    } else {
                        $builder->like('name', $search);
                    }
                    $builder->delete();
                } else {
                    $builder->truncate(); 
                }
            } else {
                $this->data->deleteBatch($ids, "id");
                
                // Also handle file deletion for the batch
                $directoryPath = FCPATH . "uploads/{$tableName}_files/";
                foreach ($ids as $id) {
                    $files = glob($directoryPath . "{$id}.*");
                    foreach ($files as $filePath) {
                        if (is_file($filePath)) unlink($filePath);
                    }
                }
            }
            session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تم حذف العناصر المختارة بنجاح']);
            return $this->response->setJSON(["status" => "success"]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function data_grap()
    {
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $row_id = $jsonData->id_entity ?? null;
        if (!$tableName || !$row_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        $data = $this->db->table($tableName)->where('id', $row_id)->get()->getRow();
        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    public function update_post_entity()
    {
        $postData = array_merge($this->request->getPost(), $this->request->getJSON(true) ?? []);
        return $this->update_entity($postData);
    }

    public function update_entity($postData)
    {
        if ($redirect = $this->checkAdmin()) return $redirect;
        $tableName = $postData["table"] ?? null;
        $entityId = $postData["id_entity"] ?? null;
        
        $Edit_notifications = $postData["edit_notifications"] ?? null;
        $Notification_type = $postData["notification_type"] ?? null;
        
        $entityFields = $postData;
        unset($entityFields["table"], $entityFields["id_entity"], $entityFields["edit_notifications"], $entityFields["notification_type"], $entityFields["file"]);
        if (!$tableName || !$entityId || empty($entityFields)) 
        {
            return $this->response->setJSON(["status" => "error","message" => "Table name, entity ID, and fields are required.",]);
        }
        try 
        {
            $uploadedFile = null;
            foreach ($this->request->getFiles() as $inputName => $file) 
            {
                if ($file->isValid() && !$file->hasMoved()) 
                {
                    $uploadedFile = $file;
                    break;
                }
            }
            if ($uploadedFile) 
            {
                $uploadDir = FCPATH . "uploads/" . $tableName . "_files/";
                if (!is_dir($uploadDir)){mkdir($uploadDir, 0777, true);}
                $filePattern = $uploadDir . $entityId . ".*";
                foreach (glob($filePattern) as $existingFile) {if (file_exists($existingFile)) {unlink($existingFile);}}
                $fileExtension = $uploadedFile->getClientExtension();
                $fileName = $entityId . "." . $fileExtension;
                $uploadedFile->move($uploadDir, $fileName);
            }
            foreach ($entityFields as $key => $value) 
            {
                if (is_array($value)){$entityFields[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);}
                if ($key === 'password') {if (!empty($value)) {$entityFields[$key] = password_hash($value, PASSWORD_BCRYPT);} else {unset($entityFields[$key]);}}
            }
            $this->data->table($tableName);
            $isUpdated = $this->data->updateData($entityId, $entityFields);
            
            if ($isUpdated) 
            {
                // Render updated card for SPA update
                $updatedEntity = $this->db->table($tableName)->where('id', $entityId)->get()->getRow();
                $renderedHtml = view('Admin/partials/entity_card', [
                    'entity' => $updatedEntity,
                    'entityName' => $tableName,
                    'translate' => $this->translate,
                    'language' => $this->language,
                    'db' => $this->db
                ]);

                session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تم تحديث البيانات بنجاح']);
                return $this->response->setJSON([
                    "status" => "success",
                    "message" => "تم تحديث البيانات بنجاح",
                    "rendered_html" => $renderedHtml
                ]);
            } else {
                return $this->response->setJSON(["status" => "error","message" => "حدثت مشكلة أثناء تحديث البيانات",]);
            }
    
        } catch (\Exception $e) 
        {
            return $this->response->setJSON(["status" => "error","message" => $e->getMessage(),]);
        }
    }

    public function getEntityData($tablename)
    {
        $cities = $this->db->table("cities")->select("id, name")->get()->getResultArray();
        $citiesOptions = [];
        foreach ($cities as $city) 
        {
            $citiesOptions[$city["id"]] = $city["name"];
        }
        $activities = $this->db->table("activities")->select("id, name")->get()->getResultArray();
        $ActivitiesOptions = ["0" => "بلا تصنيف"];
        foreach ($activities as $activity) 
        {
            $ActivitiesOptions[$activity["id"]] = $activity["name"];
        }
        $genders = $this->db->table("genders")->select("id, name")->get()->getResultArray();
        $GendersOptions = [];
        foreach ($genders as $gender) 
        {
            $GendersOptions[$gender["id"]] = $gender["name"];
        }
        $EntityData = [
            "admin" => [
                "name" => [
                    "id" => "name",
                    "placeholder" => "الاسم",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "email" => [
                    "id" => "email",
                    "placeholder" => "البريد الإلكتروني",
                    "type" => "email",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "username" => [
                    "id" => "username",
                    "placeholder" => "اسم المستخدم",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "password" => [
                    "id" => "password",
                    "placeholder" => "كلمة المرور الجديدة",
                    "type" => "password",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "phone" => [
                    "id" => "phone",
                    "placeholder" => "رقم الهاتف",
                    "type" => "phone",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "language" => [
                    "id" => "language",
                    "placeholder" => "اللغة",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => [
                        "ar" => "اللغة العربية",
                        "en" => "English Language",
                    ],
                    "required" => true,
                ],
                "image" => [
                    "id" => "image",
                    "placeholder" => "صورة شخصية",
                    "type" => "file",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "owner" => [
                    "id" => "owner",
                    "placeholder" => "الصلاحية",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => [
                        "0" => "إداري",
                        "1" => "مدير المنظومة",
                    ],
                    "required" => true,
                ],
            ],
            "cities" => [
                "name" => [
                    "id" => "name",
                    "placeholder" => "اسم المدينة",
                    "type" => "text",
                    "class_id" => "col-md-12",
                    "required" => true,
                ],
            ],
            "library" => [
                "file" => [
                    "id" => "file",
                    "placeholder" => "اختر ملف الوسائط",
                    "type" => "file",
                    "required" => true,
                ],
            ],
            "news" => [
                "name" => [
                    "id" => "name",
                    "placeholder" => "عنوان المنشور",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "activity_id" => [
                    "id" => "activity_id",
                    "placeholder" => "التصنيف",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => $ActivitiesOptions,
                    "required" => true,
                ],
                "post_date" => [
                    "id" => "post_date",
                    "placeholder" => "تاريخ النشر",
                    "type" => "date",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "post_thumbnail" => [
                    "id" => "post_thumbnail",
                    "placeholder" => "صورة المنشور",
                    "type" => "file",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "post_content" => [
                    "id" => "post_content",
                    "placeholder" => "تفاصيل المنشور",
                    "type" => "textarea",
                    "class_id" => "col-md-12",
                    "required" => true,
                ],
            ],


            "volunteers" => [
                "name" => [
                    "id" => "name",
                    "placeholder" => "الاسم",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "image" => [
                    "id" => "image",
                    "placeholder" => "صورة شخصية",
                    "type" => "file",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "email" => [
                    "id" => "email",
                    "placeholder" => "البريد الإلكتروني",
                    "type" => "email",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "birthdate" => [
                    "id" => "birthdate",
                    "placeholder" => "تاريخ الميلاد",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "gender" => [
                    "id" => "gender",
                    "placeholder" => "الجنس",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => $GendersOptions,
                    "required" => true,
                ],
                "username" => [
                    "id" => "username",
                    "placeholder" => "اسم المستخدم",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "address" => [
                    "id" => "address",
                    "placeholder" => "العنوان",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "password" => [
                    "id" => "password",
                    "placeholder" => "كلمة المرور الجديدة",
                    "type" => "password",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "phone" => [
                    "id" => "phone",
                    "placeholder" => "رقم الهاتف",
                    "type" => "phone",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "identity" => [
                    "id" => "identity",
                    "placeholder" => "التعريف الشخصي",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "academic_value" => [
                    "id" => "academic_value",
                    "placeholder" => "المؤهل العلمي / التخصص",
                    "type" => "text",
                    "class_id" => "col-md-12",
                    "required" => false,
                ],
                "hobbies" => [
                    "id" => "hobbies",
                    "placeholder" => "الهوايات",
                    "type" => "text",
                    "class_id" => "col-md-12",
                    "required" => false,
                ],
                "language" => [
                    "id" => "language",
                    "placeholder" => "اللغة",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => [
                        "ar" => "اللغة العربية",
                        "en" => "English Language",
                    ],
                    "required" => true,
                ],
                "city_id" => [
                    "id" => "city_id",
                    "placeholder" => "المدينة",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "options" => $citiesOptions,
                    "required" => true,
                ],
            ],
            "activities" => [
                "name" => [
                    "id" => "name",
                    "placeholder" => "عنوان النشاط",
                    "type" => "text",
                    "class_id" => "col-md-12",
                    "required" => true,
                ],
                "organisation" => [
                    "id" => "organisation",
                    "placeholder" => "اسم المنظمة",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "city_id" => [
                    "id" => "city_id",
                    "type" => "select",
                    "class_id" => "col-md-6",
                    "placeholder" => "المدينة",
                    "options" => $citiesOptions,
                    "required" => true,
                ],
                "date_from" => [
                    "id" => "date_from",
                    "placeholder" => "تاريخ بدء النشاط",
                    "type" => "date",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "date_to" => [
                    "id" => "date_to",
                    "placeholder" => "تاريخ نهاية النشاط",
                    "type" => "date",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "image" => [
                    "id" => "image",
                    "placeholder" => "صورة غلاف للنشاط",
                    "type" => "file",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "description" => [
                    "id" => "description",
                    "placeholder" => "وصف النشاط",
                    "type" => "textarea",
                    "class_id" => "col-md-12",
                    "required" => true,
                ],
                "required_files" => [
                    "id" => "required_files",
                    "placeholder" => "الملفات المطلوبة",
                    "type" => "textarea",
                    "class_id" => "col-md-12",
                    "required" => true,
                ],
                "hours" => [
                    "id" => "hours",
                    "placeholder" => "ساعات التطوع",
                    "type" => "text",
                    "class_id" => "col-md-6",
                    "required" => true,
                ],
                "transportation" => [
                    "id" => "transportation",
                    "placeholder" => "التكفل بالمواصلات",
                    "options" => ["1", "0"],
                    "type" => "radio",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "residency" => [
                    "id" => "residency",
                    "placeholder" => "التكفل بالإقامة",
                    "options" => ["1", "0"],
                    "type" => "radio",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "expenses" => [
                    "id" => "expenses",
                    "placeholder" => "التكفل بالإعاشة",
                    "options" => ["1", "0"],
                    "type" => "radio",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
                "training" => [
                    "id" => "training",
                    "placeholder" => "التكفل بالتدريب",
                    "options" => ["1", "0"],
                    "type" => "radio",
                    "class_id" => "col-md-6",
                    "required" => false,
                ],
            ],


        ];
        return $EntityData[$tablename] ?? [];
    }
    
    public function calendar_activities()
    {
        $activities = $this->db->table("activities")->get()->getResultArray();
        $events = [];
    
        foreach ($activities as $activity) 
        {
            $events[] = [
                'title' => $activity['name'],
                'start' => $activity['date_from'],
                'end'   => date('Y-m-d', strtotime($activity['date_to'] . ' +1 day')),
                'color' => '#304300',
                'extendedProps' => [
                    'hours'   => $activity['hours'],
                    'city_id' => $activity['city_id']
                ]
            ];
        }
    
        header('Content-Type: application/json');
        echo json_encode($events);
    }

    public function settings()
    {
        $this->checkAdmin();
        $is_owner = $this->db->table('admin')->where('id', $this->admin_id)->get()->getRow()->owner;
        if ($is_owner != 1) {
            return redirect()->to(base_url('Admin/dashboard'));
        }

        $settings = $this->db->table('settings')->get()->getResultArray();
        $settingsMap = [];
        foreach ($settings as $setting) {
            $settingsMap[$setting['setting_key']] = $setting['setting_value'];
        }

        // WhatsApp API Key Fallback
        $whatsapp_api_key = $settingsMap['whatsapp_api_key'] ?? env('app.notificationApiKey', '');
        $whatsapp_webhook_secret = $settingsMap['whatsapp_webhook_secret'] ?? env('app.whatsappWebhookSecret', '');

        // WhatsApp Test Number
        $whatsapp_test_number = $settingsMap['whatsapp_test_number'] ?? '';

        $data = [
            'settings' => $settingsMap,
            'whatsapp_api_key' => $whatsapp_api_key,
            'whatsapp_webhook_secret' => $whatsapp_webhook_secret,
            'whatsapp_test_number' => $whatsapp_test_number
        ];

        return $this->page('settings', 'system-settings', $data);
    }

    public function update_settings()
    {
        $this->checkAdmin();
        $is_owner = $this->db->table('admin')->where('id', $this->admin_id)->get()->getRow()->owner;
        if ($is_owner != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $jsonData = $this->request->getJSON();
        $key = $jsonData->key ?? null;
        $value = $jsonData->value ?? null;

        if ($key) {
            $exists = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
            if ($exists) {
                $this->db->table('settings')->where('setting_key', $key)->update(['setting_value' => $value]);
            } else {
                $this->db->table('settings')->insert(['setting_key' => $key, 'setting_value' => $value]);
            }
            return $this->response->setJSON(['status' => 'success', 'message' => 'Setting updated successfully']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid data']);
    }

    public function test_whatsapp()
    {
        $this->checkAdmin();
        $is_owner = $this->db->table('admin')->where('id', $this->admin_id)->get()->getRow()->owner;
        if ($is_owner != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $jsonData = $this->request->getJSON();
        $phone = $jsonData->phone ?? null;
        
        if ($phone) {
            $message = "I-Volunteer System Test Message - 🔥 - " . date('Y-m-d H:i:s');
            // Bypass toggle for manual test
            $results = $this->notificationsender->sendTexts($phone, $message, true);
            
            if (!empty($results) && $results[0]['success']) {
                $api_info = !empty($results[0]['response'])
                    ? json_encode($results[0]['response'], JSON_UNESCAPED_UNICODE)
                    : ($results[0]['error'] ?: 'Message sent to WasenderAPI');
                session()->setFlashdata('notification', ['type' => 'success', 'message' => 'تم إرسال رسالة التجربة بنجاح']);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Test message sent successfully.', 'info' => $api_info]);
            } else {
                $error_msg = $results[0]['error'] ?? 'Unknown Error';
                return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to send test message.', 'info' => $error_msg]);
            }
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid phone number']);
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        $session->setFlashdata("logout_notification", "logged_out");
        return redirect()->to(base_url());
    }

    private function getSetting($key, $default = '')
    {
        $setting = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
        return $setting ? $setting->setting_value : $default;
    }
}
