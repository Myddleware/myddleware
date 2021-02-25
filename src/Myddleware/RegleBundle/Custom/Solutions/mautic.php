<?php

namespace Myddleware\RegleBundle\Solutions;

class mauticbase extends mauticcore
{
    protected $mauticVersion = 2;
}

$file = __DIR__.'/mautic.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class mautic extends mauticbase {}
}
