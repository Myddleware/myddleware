<?php

namespace Myddleware\RegleBundle\Solutions;

class microsoftsqlbase extends microsoftsqlcore
{
    public function read($param)
    {
        // Redefine reference date format (add milliseconds)
        $date = new \DateTime($param['date_ref']);
        $param['date_ref'] = $date->format('Y-m-d H:i:s.v');

        // Call standard read function
        return parent::read($param);
    }
}

$file = __DIR__.'/microsoftsql.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class microsoftsql extends microsoftsqlbase {}
}
