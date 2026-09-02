<?php

namespace App\Models;
use CodeIgniter\Model;

class AdditionalFieldsModel extends Model
{
    protected $table = '';
    protected $allowedFields = [];

    public function addDynamicFieldsFromJson(string $tableName, array &$entityFields, string $filename = ""): void
    {
        $configFile = APPPATH . "Config/additional_fields.json";
        if (!file_exists($configFile)) {throw new \RuntimeException("Configuration file not found.");}
        $configData = json_decode(file_get_contents($configFile), true);
        if (!isset($configData[$tableName])) {return;}
        foreach ($configData[$tableName] as $field => $value) 
        {
            if ($value === "{{FIELD NAME}}") 
            {
                $value = 'VALUE';
            }
            $entityFields[$field] = $value;
        }
    }
}
