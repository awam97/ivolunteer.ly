<?php

namespace App\Models;

use CodeIgniter\Model;

class DataModel extends Model
{
    protected $db;
    protected $table = '';
    protected $primaryKey = 'id';
    protected $allowedFields = [];

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect('default');
    }

    public function table(string $tableName)
    {
        if (empty($tableName)) {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }

        $this->table = $tableName;
        return $this;
    }

    public function where($field, $value = null, $escape = null)
    {
        parent::where($field, $value, $escape);
        return $this;
    }

    public function firstRow()
    {
        return parent::asObject()->first();
    }

    public function setFieldsAndPrimaryKey(array $fields, string $primaryKey = 'id')
    {
        if (empty($fields)) {
            throw new \InvalidArgumentException('Allowed fields cannot be empty.');
        }

        $this->allowedFields = $fields;
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function insertData(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Data to insert cannot be empty.');
        }                    
        if (!$this->db->table($this->table)->insert($data)) {
            $errors = $this->db->error() ?? 'Unknown error occurred during insertion.';
            throw new \Exception("Failed to insert data: $errors");
        }    
        return $this->db->insertID();
    }    

    public function deleteData($id)
    {
        if (empty($this->table)) {
            throw new \Exception('Table name must be set before using deleteData.');
        }
        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty for delete operation.');
        }                
        $deleteResult = $this->db->table($this->table)->delete([$this->primaryKey => $id]);
        if (!$deleteResult) {
            throw new \Exception('Failed to delete data. The entity may not exist.');
        }

        return $deleteResult;
    }

    public function updateData($id, array $data)
    {
        if (empty($this->table)) {
            throw new \Exception('Table name must be set before using updateData.');
        }

        if (empty($id)) {
            throw new \InvalidArgumentException('ID cannot be empty for update operation.');
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('Data array cannot be empty.');
        }

        try {
            // Start a transaction (optional, useful for complex multi-step operations)            
            $this->db->transStart();

            // Attempt to update the record
            $updateResult = $this->db->table($this->table)->where($this->primaryKey, $id)->update($data);

            // Check if the update was successful
            if ($updateResult === false) {
                $error = $this->db->error();
                if ($error['code'] !== 0) {
                    log_message('error', 'Update error for ID ' . $id . ' in table ' . $this->table . ': ' . $error['message']);
                    throw new \Exception('Failed to update data: ' . $error['message']);
                }
            }

            // Complete the transaction (commit if successful)
            $this->db->transComplete();

            // Check if the transaction was successful
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed. Data update was not completed.');
            }

            return true;  // Return true to indicate success

        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            $this->db->transRollback();
            
            // Log the exception message for debugging
            log_message('error', 'Error during update: ' . $e->getMessage());
            
            throw $e;  // Re-throw the exception
        }
    }


    public function deleteBatch(array $ids, string $primaryKey = 'id')
    {
        if (!$this->table) {
            throw new \Exception('Table name must be set before using deleteBatch.');
        }

        // Use the primary key to match IDs
        return $this->db->table($this->table)->whereIn($primaryKey, $ids)->delete();
    }
}
