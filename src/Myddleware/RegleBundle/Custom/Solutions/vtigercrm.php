<?php

namespace Myddleware\RegleBundle\Solutions;

class vtigercrmbase extends vtigercrmcore
{
    /**
     * Inventory modules
     *
     * @var string[]
     */
    protected $inventoryModules = [
        'Invoice',
        'SalesOrder',
        'Quotes',
        'PurchaseOrder',
        'GreenTimeControl',
        'DDT'
    ];

    /**
     * Module list that allows to make parent relationships
     *
     * @var string[]
     */
    protected $allowParentRelationship = [
        'Invoice',
        'Quotes',         
        'SalesOrder',
        'PurchaseOrder',
        'GreenTimeControl',
        'DDT'
    ];    
}

$file = __DIR__.'/vtigercrm.client.php';
if (file_exists($file)) {
    require_once $file;
} else {
    class vtigercrm extends vtigercrmbase {}
}
