<?php

namespace Myddleware\RegleBundle\Solutions;

class microsoftsqlbase extends microsoftsqlcore
{
    protected $readDeletion = true;
    protected $sendDeletion = true;

    public function read($param)
    {
        // Redefine reference date format (add milliseconds)
        try {
            $date = new \DateTime($param['date_ref']);
            $param['date_ref'] = $date->format('Y-m-d H:i:s.v');
        } catch (\Exception $error) {
            // Ignore problem on date_ref
        }

        // Call standard read function
        return parent::read($param);
    }

    // Query to get all the tables of the database
    protected function get_query_show_tables() {
        return 'SELECT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_catalog = \''.$this->paramConnexion['database_name'].'\'';
    }

    // Query to get all the flieds of the table
    protected function get_query_describe_table($table) {
        return 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = \''.$table.'\'';
    }
}

$file = __DIR__.'/microsoftsql.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class microsoftsql extends microsoftsqlbase {}
}
