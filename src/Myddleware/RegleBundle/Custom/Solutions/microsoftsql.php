<?php

namespace Myddleware\RegleBundle\Solutions;

//Custom class
class microsoftsql extends microsoftsqlcore {

    // Redifine read function
    public function read($param) {
        // Redefine reference date format (add milliseconds)
        $date = new \DateTime($param['date_ref']);
        $param['date_ref'] = $date->format('Y-m-d H:i:s.v');
        // Call standard read function
        $result = parent::read($param);

        return $result;
    }
}
