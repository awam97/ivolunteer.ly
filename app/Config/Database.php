<?php
namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';
    public array $default;
    
    public function __construct()
    {
        parent::__construct();

        $this->default = [
            'DSN'          => '',
            'hostname'     => getenv('database.default.hostname'),
            'username'     => getenv('database.default.username'),
            'password'     => getenv('database.default.password'),
            'database'     => getenv('database.default.database'),
            'DBDriver'     => getenv('database.default.DBDriver') ?: 'MySQLi',
            'DBPrefix'     => getenv('database.default.DBPrefix') ?: '',
            'pConnect'     => false,
            'DBDebug'      => true,
            'charset'      => 'utf8mb4',
            'DBCollat'     => 'utf8mb4_general_ci',
            'swapPre'      => '',
            'encrypt'      => false,
            'compress'     => false,
            'strictOn'     => false,
            'failover'     => [],
            'port'         => 3306,
            'numberNative' => false,
            'dateFormat'   => [
                'date'     => 'Y-m-d',
                'datetime' => 'Y-m-d H:i:s',
                'time'     => 'H:i:s',
            ],
        ];


        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
