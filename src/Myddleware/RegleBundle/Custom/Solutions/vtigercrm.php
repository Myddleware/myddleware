<?php

namespace Myddleware\RegleBundle\Solutions;

class vtigercrmbase extends vtigercrmcore
{
}

$file = __DIR__.'/vtigercrm.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class vtigercrm extends vtigercrmbase {}
}
