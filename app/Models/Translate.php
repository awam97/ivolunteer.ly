<?php

namespace App\Models;

use CodeIgniter\Model;

class Translate extends Model
{

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    function translate_phrase($key, $language = null) 
    {
        $result = $this->db->table('languages')->where('language', $language)->where('key', $key)->get()->getRow();
        return $result->text ?? $key;
    }

}


