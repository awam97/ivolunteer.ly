<?php

namespace App\Models;
use CodeIgniter\Model;
class FileModel extends Model
{

    public function saveBase64File($base64File, $tableName, $insertId)
    {
        if (!preg_match("/^data:([a-zA-Z0-9\/\+]+);base64,/", $base64File, $type)){throw new \RuntimeException("Invalid file format.");}
        $base64File = substr($base64File, strpos($base64File, ",") + 1);
        $mimeType = strtolower($type[1]);
        $decodedFile = base64_decode($base64File);
        if ($decodedFile === false){throw new \RuntimeException("Base64 decoding failed.");}
        $fileExtension = explode("/", $mimeType)[1] ?? "";
        if (!$fileExtension){throw new \RuntimeException("Failed to determine file extension.");}
        $uploadDir = FCPATH . "uploads/" . $tableName . "_files/";
        if (!is_dir($uploadDir)){mkdir($uploadDir, 0777, true);}
        $filePath = $uploadDir . $insertId . "." . $fileExtension;
        if (!file_put_contents($filePath, $decodedFile)){throw new \RuntimeException("Failed to save the file.");}
        return $filePath;
    }
    
    public function get_image_url($user_type, $user_id)
    {
        $folderPath = "uploads/{$user_type}_files/";
        $filePath = glob($folderPath . $user_id . ".*");
        if (!empty($filePath)) 
        {
            $fileExtension = pathinfo($filePath[0], PATHINFO_EXTENSION);
            $image_path = $folderPath . $user_id . '.' . $fileExtension;
        } else 
        {
            $image_path = 'uploads/user.jpg';
        }
        return base_url($image_path);
    }

}
