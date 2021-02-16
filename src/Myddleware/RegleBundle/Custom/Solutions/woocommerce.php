<?php

namespace Myddleware\RegleBundle\Solutions;

class woocommercebase extends woocommercecore
{

}

$file = __DIR__.'/woocommerce.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class woocommerce extends woocommercebase {}
}
