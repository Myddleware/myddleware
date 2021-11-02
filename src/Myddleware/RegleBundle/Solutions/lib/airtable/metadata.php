<?php

$moduleFields = array (
    'Contacts' => array(
        'id' => array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'firstname' => array( 'label' => 'Last name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'lastname' => array( 'label' => 'First name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'email' => array( 'label' => 'Email', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'date_created' => array( 'label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'id (from Accounts)' => array( 'label' => 'Accounts ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0), relationship
        'date_modified' => array( 'label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
    ),
    'Accounts' => array(
        'id' => array( 'label' => 'Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'name' => array( 'label' => 'Name', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        'date_created' => array( 'label' => 'Date created', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
        // 'Contacts' => array( 'label' => 'Contacts ID', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),  relationship
        'date_modified' => array( 'label' => 'Date modified', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0)
    ),
);


$fieldsRelate = array (
    'Contacts' => array(
        'id (from Accounts)' => array( 'label' => 'Accounts Id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
    ),
    // 'Accounts' => array(
    //      'Contacts'	=> array( 'label' => 'Customer id', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 0),
    // )
);
