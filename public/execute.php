<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json'); // Ensure the response is JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $command = escapeshellcmd($input['command']); // Sanitize input

    error_log("command:");
    error_log($command);

    error_log("SHELL:");
    error_log($SHELL);

    // test for the command
    // $command = "php /c/laragon/www/myddleware_NORMAL/bin/console cache:clear";

    // Execute the command and capture the output
   // Specify the full path to PHP and the console script
   $phpPath = 'C:\\laragon\\bin\\php\\php-8.2.0\\php.exe'; // Update this to the actual path of your PHP executable
   $consolePath = 'C:\\laragon\\www\\myddleware_NORMAL\\bin\\console';

   // Construct the command
   $fullCommand = "$phpPath $consolePath cache:clear 2>&1";

    $output = shell_exec($fullCommand);
    error_log("output:");
    error_log($output);

    // Return the output as JSON
    echo json_encode(['output' => $output]);
}
?>