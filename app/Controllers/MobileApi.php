<?php

namespace App\Controllers;

use App\Libraries\MobileApiService;

class MobileApi extends BaseController
{
    protected MobileApiService $api;

    public function __construct()
    {
        $this->api = new MobileApiService();
    }

    public function login()
    {
        return $this->api->login();
    }

    public function me()
    {
        return $this->api->me();
    }

    public function refresh()
    {
        return $this->api->refresh();
    }

    public function logout()
    {
        return $this->api->logout();
    }

    public function cities()
    {
        return $this->api->cities();
    }

    public function activities()
    {
        return $this->api->activities();
    }

    public function activity(int $id)
    {
        return $this->api->activity($id);
    }

    public function myActivities()
    {
        return $this->api->myActivities();
    }

    public function enroll()
    {
        return $this->api->enroll();
    }

    public function unenroll()
    {
        return $this->api->unenroll();
    }
}
