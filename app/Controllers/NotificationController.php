<?php

    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    use App\Models\DataModel;
    use App\Models\NotificationSender;
    
    class NotificationController extends BaseController
    {
        protected $group = 'Notifications';
        protected $name = 'notification:send';
        protected $description = 'Send WhatsApp notifications.';
    
        public function __construct()
        {
            $this->db = \Config\Database::connect();
            $this->request = \Config\Services::request();
            $this->response = \Config\Services::response();
            $this->notificationsender = new NotificationSender();
            $this->session = session();
            $this->admin_id = $this->session->get('user_id');
            $this->login_type = $this->session->get('login_type');
            if ($this->admin_id) 
            {
                $DataModel = new DataModel();
                $this->data = $DataModel;
            }
        }
    
        public function sendnotification()
        {
            $builder = $this->db->table('admin');
            $admins = $builder->select('phone')->get()->getResultArray();
            $recipients = array_column($admins, 'phone');
            $volunteerActivities = $this->db->table('volunteer_activities');
            $pendingCount = $volunteerActivities->where('status', 0)->countAllResults();
            if ($pendingCount >= 15) 
            {
                $message = 'تنبيه: يوجد هناك عدد  ' . $pendingCount . ' طلبات انضمام لأنشطة تطوعية معلقة , الرجاء الدخول للمنصة للبت في هذه الطلبات';
                $results = $this->notificationsender->sendText($recipients, $message);
                if ($results) 
                {
                    return $this->response->setJSON(['status' => 'success', 'message' => 'Notifications sent successfully']);
                }
                else
                {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to send notifications']);
                }
            }
            return $this->response->setJSON(['status' => 'info', 'message' => 'No pending requests or less than 15']);
        }
        
        public function sendtogroup()
        {
            $db = \Config\Database::connect();
            $builder = $db->table('pending_messages');
            $query = $builder->where('status', 'pending')->get();
            if ($query->getNumRows() === 0) {return;}
            foreach ($query->getResult() as $row) 
            {
                // Mark as sending immediately to prevent other processes from picking it up
                $db->table('pending_messages')->where('id', $row->id)->update(['status' => 'sending']);
                
                $response = $this->notificationsender->sendTexts($row->phone, $row->message);
                if ($response && isset($response[0]) && $response[0]['success']) 
                {
                    $db->table('pending_messages')->where('id', $row->id)->update(['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')]);
                }
                else
                {
                    $db->table('pending_messages')->where('id', $row->id)->update(['status' => 'failed']);
                }
                sleep(8);
            }
        }

    }
