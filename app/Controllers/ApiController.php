<?php

namespace App\Controllers;
use CodeIgniter\Controller;

class ApiController extends BaseController
{

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
        $this->response = \Config\Services::response();
        $this->session = session();
    }

    public function connect()
    {
        $json = $this->request->getJSON();
        if (!$json || !isset($json->username) || !isset($json->password)){return $this->response->setJSON(['status' => 'error','message' => 'Username/Email/Phone and password are required.']);}
        if (empty($json->username) || empty($json->password)) {return $this->response->setJSON(['status' => 'error', 'message' => 'Username/Email/Phone and password are required.']);}
        $usernameOrContact = $json->username;
        $password = $json->password;
        $builder = $this->db->table('admin');
        $admin = $builder->where('username', $usernameOrContact)->orWhere('email', $usernameOrContact)->orWhere('phone', $usernameOrContact)->get()->getRow();
        if ($admin) 
        {
            if (password_verify($password, $admin->password))
            {
                return $this->response->setJSON(['status' => 'success','message' => 'Login successful.','data' => ['owner' => $admin->owner,'password' => $admin->password,'id' => $admin->id,'language' => $admin->language,'username' => $admin->username,'name' => $admin->name]]);
            } 
            else 
            {
                return $this->response->setJSON(['status' => 'error','message' => 'Invalid password.']);
            }
        } 
        else 
        {
            return $this->response->setJSON(['status' => 'error','message' => 'Admin user not found.']);
        }
    }

    
    public function cities()
    {
        header('Content-Type: application/json');
        $query = $this->db->table('cities')->get();
        $cities = $query->getResult();
        if (!empty($cities)) 
        {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Cities retrieved successfully.','data'=> $cities]);
        } 
        else
        {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No cities found.','data'=> []]);
        }
    }
    
    
    public function activities()
    {
        header('Content-Type: application/json');
        $query = $this->db->table('activities')->select('activities.*, cities.name as city_name')->join('cities', 'cities.id = activities.city_id', 'left')->get();
        $activities = $query->getResult();
        if (!empty($activities)) 
        {
            return $this->response->setJSON(['status' => 'success','message' => 'Activities retrieved successfully.','data' => $activities]);
        } 
        else 
        {
            return $this->response->setJSON(['status' => 'error','message' => 'No activities found.','data' => []]);
        }
    }
   
    public function admins()
    {
        header('Content-Type: application/json');
        $query = $this->db->table('admin')->get();
        $admins = $query->getResult();
        if (!empty($admins)) 
        {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Cities retrieved successfully.','data'=> $admins]);
        } 
        else
        {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No cities found.','data'=> []]);
        }
    }    
    
    public function statistics()
    {
        header('Content-Type: application/json');
        $adminsCount = $this->db->table('admin')->countAllResults();
        $volunteersCount = $this->db->table('volunteers')->countAllResults();
        $citiesCount = $this->db->table('cities')->countAllResults();
        $activitiesCount = $this->db->table('activities')->countAllResults();
        $data = ['admins' => $adminsCount,'volunteers' => $volunteersCount,'cities' => $citiesCount,'activities' => $activitiesCount];
        return $this->response->setJSON(['status' => 'success','message' => 'Statistics retrieved successfully.','data' => $data]);
    }

}
