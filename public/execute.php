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
   $phpPath = trim(shell_exec('where php'));

   // given the script filename "C:/laragon/www/myddleware_NORMAL/public/execute.php" global variable
   $executepath = $_SERVER['SCRIPT_FILENAME'];
   error_log("executePath:");
   error_log($executePath);
   
//    to get the console path, remove /public/execute.php
   $cleanPath = str_replace('/public/execute.php', '', $executepath);

   error_log("cleanPath:");
   error_log($cleanPath);

   $consolePath = $cleanPath . '/bin/console';

   error_log("consolePath:");
   error_log($consolePath);

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
    $output = shell_exec("cd C:\\laragon\\www\\myddleware_NORMAL && $composerPath install 2>&1");
} else {
    $output = shell_exec($command);
}

   // Construct the command
//    $fullCommand = "$phpPath $consolePath cache:clear 2>&1";

    error_log("output:");
    error_log($output);

    // Return the output as JSON
    echo json_encode(['output' => $output]);
}
?>