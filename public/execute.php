// execute.php
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $command = escapeshellcmd($input['command']); // Sanitize input

        // Execute the command and capture the output
        $output = shell_exec($command);

        // Return the output
        echo json_encode(['output' => $output]);
    }
    ?>