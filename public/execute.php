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
        echo json_encode(['output' => 'The PHP shell_exec() function is disabled. Please enable it in php.ini to run the terminal.']);
        exit;
    }

    $phpPath = trim(shell_exec(isWindows() ? 'where php' : 'which php'));
    $executepath = $_SERVER['SCRIPT_FILENAME'];
    $cleanPath = str_replace(isWindows() ? '\\public\\execute.php' : '/public/execute.php', '', $executepath);
    $consolePath = $cleanPath . (isWindows() ? '\\bin\\console' : '/bin/console');

    // Adjust paths for Windows
    if (isWindows()) {
        $phpPath = adjustPath($phpPath);
        $executepath = adjustPath($executepath);
        $cleanPath = adjustPath($cleanPath);
        $consolePath = adjustPath($consolePath);
    }

    // Execute command
    if (strpos($command, 'php bin/console') !== false) {
        $isolatedCommand = str_replace('php bin/console', '', $command);
        $output = shell_exec("$phpPath $consolePath $isolatedCommand 2>&1");
    } elseif (strpos($command, 'composer') !== false) {
        $isolatedCommand = str_replace('composer ', '', $command);
        $composerPath = trim(shell_exec(isWindows() ? 'where composer' : 'which composer'));

        if (isWindows()) {
            $composerPath = adjustPath($composerPath);
        }

        $output = shell_exec("cd $cleanPath && $composerPath $isolatedCommand 2>&1");
    } else {
        $output = shell_exec($command);
    }


   $phpPath = trim(shell_exec('where php'));

   if (isWindows()) {
    $phpPath = adjustPath($phpPath);
   }

   $executepath = $_SERVER['SCRIPT_FILENAME'];

   if (isWindows()) {
    $executepath = adjustPath($executepath);
   }
   
   if (isWindows()) {
    $cleanPath = str_replace('\\public\\execute.php', '', $executepath);
   } else {
    $cleanPath = str_replace('/public/execute.php', '', $executepath);
   }

   if (isWindows()) {
    $cleanPath = adjustPath($cleanPath);
   }

   if (isWindows()) {
    $consolePath = $cleanPath . '\bin\console';
   } else {
    $consolePath = $cleanPath . '/bin/console';
   }

   if (isWindows()) {
    $consolePath = adjustPath($consolePath);
   }

//    if the command contains php bin/console
if (strpos($command, 'php bin/console') !== false) {
    $isolatedCommand = str_replace('php bin/console', '', $command);
    $output = shell_exec("$phpPath $consolePath $isolatedCommand 2>&1");
} elseif (strpos($command, 'composer') !== false) {
    $isolatedCommand = str_replace('composer ', '', $command);

    $composerPath = trim(shell_exec('which composer'));


    if (isWindows()) {
        $composerPath = adjustPath($composerPath);
    }

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
        
        // Handle drive letters (e.g., /c/ to C:\)
        $path = preg_replace('/^\\\\([a-z])\\\\/', '$1:\\\\', $path);

        // Convert the drive letter to uppercase
        $path = preg_replace_callback('/^([a-z]):/', function($matches) {
            return strtoupper($matches[1]) . ':';
        }, $path);
    }
    return $path;
}
?>