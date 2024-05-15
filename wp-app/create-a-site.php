<?php
// Enable PHP error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure this file is being accessed from within WordPress
define('WP_USE_THEMES', false);
require('./wp-load.php');

// Docker container name where WP-CLI is installed
$docker_container = 'wp-docker-test_wordpress';

// Log directory
$log_dir = '/var/www/html/logs';
$log_file = $log_dir . '/wp_cli.log';

error_log("geia xara\n", 3, $log_file);

// Ensure the log directory exists
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Function to execute WP-CLI commands and handle errors
function execute_wp_cli_command($command, $sub_site_url = '') {
    global $docker_container, $log_dir, $log_file;
    $full_command = "wp $command";
    if (!empty($sub_site_url)) {
        $full_command .= " --url=$sub_site_url";
    }
    $full_command .= " 2>&1";
    $output = shell_exec($full_command); // Capture both output and errors
    
    // Log the command and its output to a file
   
    $log_message = "Command: $full_command\nOutput: $output\n";
    error_log($log_message, 3, $log_file);
    // file_put_contents($log_file, "Command: $full_command\nOutput: $output\n", FILE_APPEND);
    
    if ($output === null) {
        throw new Exception("Command failed: $command");
    }
    return $output;
}

// Function to check if a user exists
function user_exists($username) {
    $command = "user get $username --field=user_login";
    $output = shell_exec("wp $command 2>&1");
    return strpos($output, $username) !== false;
}

// Function to schedule a deletion event
function schedule_site_deletion($site_slug, $delay_minutes) {
    $timestamp = time() + ($delay_minutes * 60);
    $event_name = 'delete_subsite_' . $site_slug;
    
    // Schedule the event
    if (!wp_next_scheduled($event_name, array($site_slug))) {
        wp_schedule_single_event($timestamp, $event_name, array($site_slug));
    }

    // Add the event callback dynamically
    add_action($event_name, 'delete_site_callback', 10, 1);
}

// Check if the button was pressed
if (isset($_POST['generate_site'])) {
    try {
        // Generate a random site slug
        $site_slug = 'site-' . uniqid();

        // 1. Create a new site
        $create_site_command = "site create --slug=$site_slug --title='New Site' --email=admin@newsite.com";
        execute_wp_cli_command($create_site_command);

        // Fetch the site URL
        $site_list_output = execute_wp_cli_command('site list --field=url');
        $site_list = explode(PHP_EOL, trim($site_list_output));
        $new_site_url = array_filter($site_list, function($url) use ($site_slug) {
            return strpos($url, $site_slug) !== false;
        });

        if (empty($new_site_url)) {
            throw new Exception("Failed to retrieve the URL for the new site.");
        }

        $new_site_url = array_values($new_site_url)[0];

        // 2. Add 5 users, creating new ones if they do not exist
        for ($i = 1; $i <= 2; $i++) {
            $username = "user$i";
            $email = "$username@example.com";
            
            if (user_exists($username)) {
                // Add existing user to the new site
                $set_role_command = "user set-role $username subscriber --url=$new_site_url";
                execute_wp_cli_command($set_role_command);
            } else {
                // Create new user
                $create_user_command = "user create $username $email --role=subscriber --user_pass=password";
                execute_wp_cli_command($create_user_command, $new_site_url);
            }
        }

        // 3. Add 5 blog posts
        for ($i = 1; $i <= 2; $i++) {
            $create_post_command = "post create --post_type=post --post_title='Blog Post $i' --post_content='This is blog post $i.' --post_status=publish --post_author=1";
            execute_wp_cli_command($create_post_command, $new_site_url);
        }

        // 4. Add 5 pages
        for ($i = 1; $i <= 2; $i++) {
            $create_page_command = "post create --post_type=page --post_title='Page $i' --post_content='This is page $i.' --post_status=publish --post_author=1";
            execute_wp_cli_command($create_page_command, $new_site_url);
        }

        // 5. Install and activate three plugins
        $plugins = ['akismet', 'login-as-user'];
        foreach ($plugins as $plugin) {
            $install_plugin_command = "plugin install $plugin --activate";
            execute_wp_cli_command($install_plugin_command, $new_site_url);
        }

        // Schedule the deletion of the sub-site after X minutes (e.g., 60 minutes)
        $delay_minutes = 1;
        schedule_site_deletion($site_slug, $delay_minutes);

        echo "Site created successfully and scheduled for deletion after $delay_minutes minutes!";
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate New Site</title>
</head>
<body>
    <form method="post">
        <button type="submit" name="generate_site">Generate New Site</button>
    </form>
</body>
</html>
