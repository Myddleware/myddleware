// execute.php
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $command = escapeshellcmd($input['command']); // Sanitize input

    // Execute the command and capture the output
    $output = shell_exec($command);

    error_log("output:");
    error_log($output);

    // Return the output
    echo json_encode(['output' => $output]);
}
?>