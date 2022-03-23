<?php
/**
 * Since Airtable modules & fields are entirely custom, these need to be manually input into the custom metadata file.
 */
$moduleFields = [];

// Metadata override if needed
$file = __DIR__.'/../../../Custom/Solutions/lib/airtable/metadata.php';
if (file_exists($file)) {
    require $file;
}
