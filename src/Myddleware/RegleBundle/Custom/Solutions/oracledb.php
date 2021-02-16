<?php

namespace Myddleware\RegleBundle\Solutions;

class oracledbbase extends oracledbcore
{
    protected $mauticVersion = 3;
}

$file = __DIR__.'/oracledb.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class oracledb extends oracledbbase {}
}
