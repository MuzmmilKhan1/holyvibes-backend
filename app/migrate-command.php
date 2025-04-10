<?php
// cron-job.php

// Check if the script is being run from the command line (CLI)
if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

// Define the absolute path to your Laravel project's root directory.
// *Important:* You MUST replace '/path/to/your/laravel/project' with the actual
// absolute path to your Laravel project on the Hostinger server.

// Construct the Artisan command to run migrations.
$artisanCommand = 'php artisan migrate --force';

// Construct the full command to execute within the Laravel project directory.
$fullCommand = "{$artisanCommand}";

// Execute the Artisan command.
// The shell_exec function executes a command via the shell and returns the
// complete output as a string.
$output = shell_exec($fullCommand);

// You can log the output for debugging purposes.
// Make sure the web server has write permissions to the log file.
$logFile = 'migrate.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Migration Output:\n" . $output . "\n\n", FILE_APPEND);

// Optionally, you can print the output to the console.
echo "Migration process completed. Check the log file for details: " . $logFile . "\n";

?>