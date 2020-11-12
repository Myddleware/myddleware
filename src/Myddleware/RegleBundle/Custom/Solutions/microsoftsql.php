<?php

namespace Myddleware\RegleBundle\Solutions;

class microsoftsqlbase extends microsoftsqlcore
{
}

$file = __DIR__.'/../Custom/Solutions/microsoftsql.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class microsoftsql extends microsoftsqlbase {}
}
