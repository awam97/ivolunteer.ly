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

    public function register()
    {
        return $this->api->register();
    }

    public function ping()
    {
        return $this->api->ping();
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

    public function deviceToken()
    {
        return $this->api->deviceToken();
    }

    public function updateProfile()
    {
        return $this->api->updateProfile();
    }

    public function cities()
    {
        return $this->api->cities();
    }

    public function activities()
    {
        return $this->api->activities();
    }

    public function adminActivities()
    {
        return $this->api->adminActivities();
    }

    public function deleteAdminActivity(int $id)
    {
        return $this->api->deleteAdminActivity($id);
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

    public function adminVolunteers()
    {
        return $this->api->adminVolunteers();
    }

    public function deleteAdminVolunteer(int $id)
    {
        return $this->api->deleteAdminVolunteer($id);
    }

    public function adminRequests()
    {
        return $this->api->adminRequests();
    }

    public function notifications()
    {
        return $this->api->notifications();
    }

    public function updateRequestStatus()
    {
        return $this->api->updateRequestStatus();
    }

    public function updateCertificate()
    {
        return $this->api->updateCertificate();
    }

    public function certificate(int $id, string $type)
    {
        return $this->api->certificate($id, $type);
    }

    public function news()
    {
        return $this->api->news();
    }

    public function createNews()
    {
        return $this->api->createNews();
    }
}
