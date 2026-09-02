<?php

namespace Config;
use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public $baseURL;
    public $notificationApiUrl;
    public $notificationApiKey;
    public $defaultCountryCode;
    public $allowedHostnames = [];
    public $indexPage = 'index.php';
    public $uriProtocol = 'REQUEST_URI';
    public $permittedURIChars = 'a-z 0-9~%.:_\-';
    public $defaultLocale = 'en';
    public $negotiateLocale = false;
    public $csrf_protection = true;
    public $supportedLocales = ['en'];
    public $appTimezone = 'UTC';
    public $charset = 'UTF-8';
    public $forceGlobalSecureRequests = false;
    public $proxyIPs = [];
    public $CSPEnabled = false;
    public $CI_ENVIRONMENT;
    public $session = [
        'sessionDriver'         => 'CodeIgniter\Session\Handlers\FileHandler',
        'sessionCookieName'     => 'ci_session',
        'sessionExpiration'     => 7200,
        'sessionSavePath'       => WRITEPATH . 'session',
        'sessionMatchIP'        => false,
        'sessionTimeToUpdate'   => 300,
        'sessionRegenerateDestroy' => false,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->baseURL = getenv('app.baseURL');
        $this->notificationApiUrl = getenv('app.notificationApiUrl');
        $this->notificationApiKey = getenv('app.notificationApiKey');
        $this->defaultCountryCode = getenv('app.defaultCountryCode');
        $this->CI_ENVIRONMENT = getenv('CI_ENVIRONMENT');
    }
}
