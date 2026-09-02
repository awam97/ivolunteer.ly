<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use App\Models\DataModel;
use App\Models\NotificationSender;
use App\Models\Translate;

class Home extends BaseController
{
    protected $db;
    protected $session;
    protected $request;
    protected $response;
    protected $notificationsender;
    protected $data;
    protected $translate;
    protected $language;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->session = \Config\Services::session();
        $this->request = \Config\Services::request();
        $this->response =  \Config\Services::response(); 
        $this->notificationsender = new NotificationSender();
        $DataModel = new DataModel();
        $this->data = $DataModel;            
        $this->translate = new Translate();
        $this->language = 'ar'; // Default public language
    }

    public function page(string $page_name, string $page_title, array $data = [], $landing_page)
    {
        $session = session();                
        $common_data = [
            'db' => $this->db,
            'loginType' => $session->get('login_type'),
            'page_title' => $this->translate->translate_phrase($page_title, $this->language),
            'page_name' => $page_name,
            'translate' => $this->translate,
            'language' => $this->language
        ];            
        $page_data = array_merge($common_data, $data);
        return view($landing_page, $page_data);
    }

    public function login()
    {       
        $session = session();
        if ($session->get('login_type') == 'Admin') {return redirect()->to(base_url("Admin/dashboard"));}
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url("Super/dashboard"));}   
        $data = [];
        return $this->page('login', 'تسجيل الدخول', $data,'index');
    }
    
    public function forgot_password()
    {       
        $session = session();
        if ($session->get('login_type') == 'Admin') {return redirect()->to(base_url("Admin/dashboard"));}
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url("Super/dashboard"));}   
        $data = [];
        return $this->page('forgot_password', 'تغيير كلمة المرور', $data,'index');
    }
    
    public function enter_otp()
    {       
        $session = session();
        if ($session->get('login_type') == 'Admin') {return redirect()->to(base_url("Admin/dashboard"));}
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url("Super/dashboard"));}   
        $data = [];
        return $this->page('verify_otp', 'المصادقة الثنائية', $data,'index');
    }   
    
    public function reset_password_page()
    {       
        $session = session();
        if ($session->get('login_type') == 'Admin') {return redirect()->to(base_url("Admin/dashboard"));}
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url("Super/dashboard"));}   
        $data = [];
        return $this->page('reset_password_page', 'تغيير كلمة المرور', $data,'index');
    }

    public function terms()
    {
        $data = [];
        return $this->page('terms', 'اللائحة التنظيمية', $data, 'index');
    }
    
    public function send_otp()
    {
        $phone = $this->request->getPost('phone');
        $otp = rand(100000, 999999);
        session()->set('otp', $otp);
        session()->set('phone', $phone);
        $this->notificationsender->sendTexts($phone, $otp);
        return redirect()->to(base_url('enter_otp'))->with('success', 'OTP sent to WhatsApp.');
    }

    // Verify OTP
    public function verify_otp()
    {
        $enteredOtp = $this->request->getPost('otp');
        $sessionOtp = session()->get('otp');

        if ($enteredOtp == $sessionOtp) {
            session()->set('otp_verified', true);
            return redirect()->to(base_url('reset_password_page'));
        } else {
            return redirect()->to(base_url('forgot_password'))->with('error', 'Invalid OTP.');
        }
    }
    
    public function reset_password()
    {
        if (!session()->get('otp_verified')) {
            return redirect()->to(base_url('forgot_password'))->with('error', 'Unauthorized access.');
        }

        $phone = session()->get('phone');
        $builder = $this->db->table('volunteers');
        $newPassword = $this->request->getPost('password');
        $builder->where('phone', $phone)->update(['password' => $newPassword]);

        session()->remove(['otp', 'otp_verified', 'phone']);
        return redirect()->to(base_url('login'))->with('success', 'Password reset successful.');
    }

    public function register()
    {      
        $session = session();
        if ($session->get('login_type') == 'Admin') {return redirect()->to(base_url() . '/Admin/dashboard');}
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url() . '/Super/dashboard');}   
        $data = [
            'cities'=>$this->db->table('cities')->get()->getResult(),
            'genders'=>$this->db->table('genders')->get()->getResult(),
        ];
        return $this->page('register', 'انشاء حساب جديد', $data,'index');        
    }        
    
    public function search_bar()
    {      
        $session = session();        
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url() . '/Super/dashboard');}   
        $data = [
            'cities'=>$this->db->table('cities')->get()->getResult(),
        ];
        return $this->page('search_bar', 'بحث عن نشاط', $data,'blank');        
    }    
    
    public function activities()
    {
        $session = session();
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url() . '/Super/dashboard');}                    
        $activity_name = $this->request->getPost('activity');
        $city_id = $this->request->getPost('city');   
        if (empty($city_id)) {$city_name = null;}else{$city_name = $this->db->table('cities')->where('id', $city_id)->get()->getRow()->name;}
        if (empty($activity_name)) {$activity_name = null;}                             
    
        $data = [
            'entityName' => 'activities',
            'searchKey' => $activity_name,
            'cityName' => $city_name,
            'details' => 'activity',
            'cities' => $this->db->table('cities')->get()->getResult(),
            'entities' => $this->getActivities($activity_name, $city_id),
        ];                    
        return $this->page('grid_view3', 'النشاطات المتاحة', $data, 'index3');
    }    

    public function news()
    {
        $session = session();
        if ($session->get('login_type') == 'Super') {return redirect()->to(base_url() . '/Super/dashboard');}                    
        $activity_name = $this->request->getPost('activity');
        $city_id = $this->request->getPost('city');   
        if (empty($city_id)) {$city_name = null;}else{$city_name = $this->db->table('cities')->where('id', $city_id)->get()->getRow()->name;}
        if (empty($activity_name)) {$activity_name = null;}                             
    
        $data = [
            'entityName' => 'news',
            'searchKey' => $activity_name,
            'cityName' => $city_name,
            'details' => 'news_page',
            'cities' => $this->db->table('cities')->get()->getResult(),
            'entities' => $this->getNews($activity_name, $city_id),
        ];                    
        return $this->page('grid_view3', 'الأخبار', $data, 'index3');
    }    

    public function activity()
    {       
        $id = $this->request->getGet('id');
        if (!$id) {return $this->response->setStatusCode(400)->setBody('ID is required.');}        
        $activity = $this->db->table('activities')->where('id', $id)->get()->getRow();
        if (!$activity) {return $this->response->setStatusCode(404)->setBody('Activity not found.');}
        $cityRow = $this->db->table('cities')->where('id', $activity->city_id)->get()->getRow();
        $cityName = $cityRow->name;                            
        $data = [
            'entityName' => 'activities',
            'id' => $id,
            'name' => $activity->name,
            'date_from' => $activity->date_from,
            'date_to' => $activity->date_to,
            'city_id' => $activity->city_id,
            'city_name' => $cityName,
            'organisation' => $activity->organisation,
            'description' => $activity->description,
            'required_files' => $activity->required_files,
            'transportation' => $activity->transportation,
            'residency' => $activity->residency,
            'expenses' => $activity->expenses,
            'training' => $activity->training,
            'entities' => $activity
        ];
        return $this->page('activity', $activity->name, $data, 'index3');
    }

    public function news_page()
    {       
        $id = $this->request->getGet('id');
        if (!$id) {return $this->response->setStatusCode(400)->setBody('ID is required.');}        
        $news = $this->db->table('news')->where('id', $id)->get()->getrow();
        if (!$news) {return $this->response->setStatusCode(404)->setBody('news not found.');}                           

        $data = [
            'entityName' => 'news',
            'id' => $id,
            'entities' => $news,
        ];
        return $this->page('news_page', $news->name, $data, 'index3');
    }    

    public function create_account()
    {        
        $data = [
            'name' => $this->request->getPost('fullname'),
            'city_id' => $this->request->getPost('city'),
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('user'),
            'phone' => $this->request->getPost('phone'),
            'password' => $this->request->getPost('password'),
            'address' => $this->request->getPost('address'),
            'created_at' => date('Y-m-d H:i:s'),
        ];      
        
        $this->data->setFieldsAndPrimaryKey($data, 'id');
        $this->data->table('volunteers');

        try {           
            $insertId = $this->data->insertData($data);            
            if ($insertId) {$this->session->setFlashdata('success', 'Account created successfully! You can now log in.');
            }
        } catch (\Exception $e) {            
            $this->session->setFlashdata('error', 'An error occurred while creating your account. Please try again.');
            log_message('error', 'Account creation failed: ' . $e->getMessage());
        }
    }

    public function last_activities()
    {
        $session = session();                                            
        $allCities = $this->db->table('cities')->get()->getResult();
        $activities = $this->getActivities(null, null, 6, 'ID DESC');               
        $data = [
            'entityName' => 'activities',
            'cities' => $allCities,
            'details' => 'activity',
            'entities' => $activities,
        ];                                
        return $this->page('grid_view2', 'النشاطات المتاحة', $data, 'blank');
    }

    public function last_news()
    {
        $session = session();                                            
        $allCities = $this->db->table('cities')->get()->getResult();
        $news = $this->getNews(null, null, 4, 'ID DESC');               
        $data = [
            'entityName' => 'news',
            'cities' => $allCities,
            'details' => 'news_page',
            'entities' => $news,
        ];                                
        return $this->page('grid_view2', 'الأخبار', $data, 'blank');
    }    

    public function cities()
    {
        $session = session();                                    
        $cities = $this->db->table('cities')->get()->getResult();                
        $data = [
            'entityName' => 'cities',            
            'entities' => $cities,
        ];                                
        return $this->page('cities', 'المدن', $data, 'widgets');
    }    
 
     public function success_register()
    {
        $session = session();                                    
        $data = [];                                
        return $this->page('success_register', 'تم التسجيل بنجاح !', $data, 'index3');
    }

    public function getActivities($key = NULL , $city_id = NULL , $limit = NULL,$order = NULL )
    {
        $builder = $this->db->table('activities');
        if (!empty($order)) {$builder->orderBy($order);}
        if (!empty($city_id)) {$builder->where('city_id', $city_id);}
        if (!empty($key)) {$builder->like('name', $key);}
        if (!empty($limit)) {$builder->limit($limit);}
        return $builder->where('date_from >',date("Y/m/d"))->get()->getResult();
    }

    public function getNews($key = NULL , $city_id = NULL , $limit = NULL,$order = NULL )
    {
        $builder = $this->db->table('news');
        if (!empty($order)) {$builder->orderBy($order);}
        if (!empty($city_id)) {$builder->where('city_id', $city_id);}
        if (!empty($key)) {$builder->like('name', $key);}
        if (!empty($limit)) {$builder->limit($limit);}
        return $builder->get()->getResult();
    }
    
    private function validate_login($input, $password, $type)
    {
        $column = match ($type) {'email' => 'email','phone' => 'phone','username' => 'username',default => null};
        if (!$column) {return ['invalid', null, null];}
        $adminQuery = $this->db->table('admin')->where([$column => $input])->get();
        $admin = $adminQuery->getRow();
        if ($admin && password_verify($password, $admin->password)) {return ['success', 'Admin', $admin->id];}
        $volunteerQuery = $this->db->table('volunteers')->where([$column => $input])->get();
        $volunteer = $volunteerQuery->getRow();
        if ($volunteer && password_verify($password, $volunteer->password)) {return ['success', 'Volunteer', $volunteer->id];}
        return ['invalid', null, null];
    }


    public function verify_login()
    {
        if ($this->request->getMethod() === 'POST') {
            $validation = \Config\Services::validation();
            $rules = ['user' => 'required','password' => 'required|min_length[6]'];
            $messages = ['user' => ['required' => 'The email, phone number, or username field is required.'],'password' => ['required' => 'Password is required.','min_length' => 'Password must be at least 6 characters long.']];
            if (!$this->validate($rules, $messages)) {return $this->page('login', 'تسجيل الدخول',['validation'  => $this->validator],'index');}
            $userInput = $this->request->getPost('user');
            $password = $this->request->getPost('password');
            if (filter_var($userInput, FILTER_VALIDATE_EMAIL)) {
                $type = 'email';
            } elseif (preg_match('/^\+?[0-9]+$/', $userInput)) {
                $type = 'phone';
            } else {
                $type = 'username';
            }
            [$login_status, $login_type, $user_id] = $this->validate_login($userInput, $password, $type);
            if ($login_status === 'success') {
                session()->set(['login_type' => $login_type, 'user_id' => $user_id]);
                if ($login_type === 'Admin') {
                    return redirect()->to(base_url("Admin/dashboard"));
                } elseif ($login_type === 'Volunteer') {
                    return redirect()->to(base_url("Volunteer/dashboard"));
                }
            } else {
                session()->setFlashdata('login_error', 'Invalid email, phone number, or username, or password');
                return redirect()->to(base_url('login'));
            }
        }
        $data = [];
        return $this->page('login', 'تسجيل الدخول', $data,'index');
    }

    public function verify_register()
    {
        $jsonData = $this->request->getJSON();
        $tableName = $jsonData->table ?? null;
        $entityFields = $jsonData->fields_entity ?? null;
        if (!$tableName || !$entityFields || !is_object($entityFields)) 
        {
            return $this->response->setJSON(['status' => 'error','message' => 'Invalid table name or fields data.']);
        }
        try {
            $entityArray = (array)$entityFields;
            if (isset($entityArray['password'])) 
            {
                $entityArray['password'] = password_hash($entityArray['password'], PASSWORD_BCRYPT);
            }
            $entityArray['created_at'] = date('Y-m-d');
            $this->data->setFieldsAndPrimaryKey(array_keys($entityArray), 'id')->table($tableName);
            $insertId = $this->data->insertData($entityArray);
            $recipients = [$entityArray['phone']];
            $template = $this->getSetting('msg_registration');
            if (!$template) {
                $template = 'الأخ/ت : ' . $entityArray['name'] . '
    
    مرحباً بك في منصة أنا متطوع ، نعلمك أن عملية التسجيل المبدئي تمت بنجاح !  
    نحن سعداء بانضمامك إلينا في رحلتك نحو العطاء والعمل التطوعي.
    
    منصة أنا متطوع هي بوابتك للمشاركة في الأنشطة التطوعية بسهولة وفعالية، و بإشراف منظمة شباب العمل التطوعي، نسعى إلى دعم المجتمع وتوفير فرص تطوعية تناسب اهتماماتك ومهاراتك.
    
    🔹 كيف تعمل المنصة؟
    1️⃣ ابحث عن الأنشطة التطوعية في منطقتك.
    2️⃣ سجل في الأنشطة التي تناسبك مباشرةً عبر المنصة.
    3️⃣ بعد اتمام النشاط ستجد شهادة اتمام النشاط  جاهزة في حسابك الشخصي و  متاحة للتحميل .
    
    ✨ معاً نحو مجتمع أفضل!';
            }
            $message = str_replace('{name}', $entityArray['name'], $template);
        
            if ($this->notificationsender->shouldSend('reg', 'user')) {
                $this->notificationsender->sendText($recipients, $message);
            }

            if ($this->notificationsender->shouldSend('reg', 'admin')) {
                $adminMsg = "🔔 *متطوع جديد انضم للمنصة!*\n\n";
                $adminMsg .= "👤 الاسم: " . $entityArray['name'] . "\n";
                $adminMsg .= "📞 الهاتف: " . $entityArray['phone'] . "\n";
                $adminMsg .= "📍 المدينة: " . ($this->db->table('cities')->where('id', $entityArray['city_id'])->get()->getRow()->name ?? 'غير معروف') . "\n";
                $this->notificationsender->sendToAdmin($adminMsg);
            }

            return $this->response->setJSON(['status' => 'success','message' => 'Data inserted successfully.','insertId' => $insertId]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error','message' => $e->getMessage()]);
        }
    }


    public function four_zero_four()
    {
        return view('four_zero_four');
    }

    private function getSetting($key, $default = '')
    {
        $setting = $this->db->table('settings')->where('setting_key', $key)->get()->getRow();
        return $setting ? $setting->setting_value : $default;
    }
}
