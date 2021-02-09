<?php

namespace Myddleware\RegleBundle\Solutions;

class mysqlbase extends mysqlcore
{

}

$file = __DIR__.'/mysql.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class mysql extends mysqlbase {}
}
