<?php


$moduleFields = [
    $module => [
        'id' => array('label' => 'Id', 'type' => 'int(255)', 'type_bdd' => 'int(11)', 'required' => 1, 'relate' => false),
        'name' => array('label' => 'Name', 'type' => 'varchar(50)', 'type_bdd' => 'varchar(50)', 'required' => 1, 'relate' => false),
        'type' => array('label' => 'Status', 'type' => 'varchar(50)', 'type_bdd' => 'varchar(50)', 'required' => 1, 'relate' => false),
        'date_created' => array('label' => 'Date created', 'type' => 'datetime', 'type_bdd' => 'datetime', 'required' => 1, 'relate' => false),
        'date_modified' => array('label' => 'Date modified', 'type' => 'datetime', 'type_bdd' => 'datetime', 'required' => 1, 'relate' => false),
        'deleted' => array('label' => 'Deleted', 'type' => 'tinyint(1)', 'type_bdd' => 'tinyint(1)', 'required' => 1, 'relate' => false),
        'created_by' => array('label' => 'Created by', 'type' => 'int(11)', 'type_bdd' => 'int(11)', 'required' => 1, 'relate' => true),
        'modified_by' => array('label' => 'Modified by', 'type' => 'int(11)', 'type_bdd' => 'int(11)', 'required' => 1, 'relate' => true)
    ]
];



// Metadata override if needed
$file = __DIR__ . '/../../../Custom/Solutions/lib/internallist/metadata.php';
if (file_exists($file)) {
    require $file;
}
