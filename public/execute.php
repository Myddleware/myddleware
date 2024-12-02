<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json'); // Ensure the response is JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $command = escapeshellcmd($input['command']); // Sanitize input

    // Check if shell_exec function is disabled
    if (!function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
        // throw new \Exception('The PHP shell_exec() function is disabled. Please enable it in php.ini to run the terminal.');
        echo json_encode(['output' => 'The PHP shell_exec() function is disabled. Please enable it in php.ini to run the terminal.']);
        exit;
    }



   $phpPath = trim(shell_exec('where php'));

   $executepath = $_SERVER['SCRIPT_FILENAME'];
   
   $cleanPath = str_replace('/public/execute.php', '', $executepath);

   $consolePath = $cleanPath . '/bin/console';

//    if the command contains php bin/console
if (strpos($command, 'php bin/console') !== false) {
    $isolatedCommand = str_replace('php bin/console', '', $command);
    $output = shell_exec("$phpPath $consolePath $isolatedCommand 2>&1");
} elseif (strpos($command, 'composer') !== false) {
    $isolatedCommand = str_replace('composer ', '', $command);

    $composerPath = trim(shell_exec('which composer'));

    $isWindowsOrUnix = strpos(PHP_OS, 'WIN') === 0 ? 'win' : 'unix';

    if ($isWindowsOrUnix === 'win') {
        // convert the composer path to windows format
        $composerPath = str_replace('/', '\\', $composerPath);
        // in composer path, replace "\c\ by "C:\"
        $composerPath = str_replace('\\c\\', 'C:\\', $composerPath);
    }

    // $output = shell_exec("$composerPath $isolatedCommand");
    $output = shell_exec("cd $cleanPath && $composerPath $isolatedCommand 2>&1");
} else {
    $output = shell_exec($command);
}

    // Return the output as JSON
    echo json_encode(['output' => $output]);
}

function isWindows() {
    return strpos(PHP_OS, 'WIN') === 0;
}

function adjustPath($path) {
    if (isWindows()) {
        // Convert Unix-style path to Windows-style
        $path = str_replace('/', '\\', $path);
        $path = str_replace('\\c\\', 'C:\\', $path);
    }
    return $path;
}
?>