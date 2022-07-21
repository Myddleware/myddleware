<?php

/**
 * Since Airtable modules & fields are entirely custom, these need to be manually input into the custom metadata file.
 */
$moduleFields['appdKFUpk2X2Ok8Dc'] = array(
    'CONTACTS' => array(
        'ID___COMET' => array('label' => 'ID Comet', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'relate' => false),
        'STATUS' => array('label' => 'Status', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1, 'relate' => false),
    )
);



// Metadata override if needed
/* $file = __DIR__ . '/../../../Custom/Solutions/lib/internallist/metadata.php';
if (file_exists($file)) {
    require $file;
}
 */