<?php

namespace Myddleware\RegleBundle\Solutions;

class microsoftsql extends microsoftsqlcore
{
    /**
     * Custom read function.
     *
     * @param $param
     * @return array|void
     */
    public function read($param)
    {
        try {
            // Redefine reference date format (add milliseconds)
            $date = new \DateTime($param['date_ref']);
            $param['date_ref'] = $date->format('Y-m-d H:i:s.v');
        } catch (\Exception $e) {
            // Ignore errors in formatting date
        }

        return  parent::read($param);
    }
}
