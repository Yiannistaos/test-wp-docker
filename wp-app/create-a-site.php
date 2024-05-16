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
    
    if ($output === null) {
        throw new Exception("Command failed: $command");
    }
    return $output;
}

// Function to check if a user exists
function user_exists($username, $sub_site_url) {
    global $log_file;
    $command = "user get $username --field=user_login --url=$sub_site_url";

    $output = shell_exec("wp $command 2>&1");
    error_log("User exists output: $output\n", 3, $log_file);
    if (strpos($output, 'Invalid user ID, email or login') !== false) {
        error_log("User does not exist: $username\n", 3, $log_file);
        return false;
    }
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

    // Store the deletion timestamp in the options table
    update_site_option('deletion_timestamp_' . $site_slug, $timestamp);

    // Add the event callback dynamically
    add_action($event_name, 'delete_site_callback', 10, 1);
}

// Check if the button was pressed
if (isset($_POST['generate_site'])) {

    $site_type = isset($_POST['site_type']) ? $_POST['site_type'] : 'site';
    
    // END: Debugging
    try {
        // Generate a random site slug
        // $unique_id = uniqid();
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $unique_id = substr(str_shuffle($characters), 0, 4);
        $site_slug = $site_type.'-' . $unique_id;

        // 1. Create a new site
        $create_site_command = "site create --slug='$site_slug' --title='Web357 Demo Site (".$site_slug.")' --email=admin@$site_slug.com";
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
        $first_names = ['John', 'Jane', 'Alex', 'Chris', 'Pat'];
        $last_names = ['Doe', 'Smith', 'Johnson', 'Williams', 'Brown'];
        $usernames = [];
        for ($i = 1; $i <= 5; $i++) {
            // get 4 random characters from the site slug
            $username = "user".$i.substr(str_shuffle($unique_id), 0, 4);
            $email = "$username@$site_slug.com";
            $password = 'password';  // Default password for new users

            // Select random first and last names
            $first_name = $first_names[array_rand($first_names)];
            $last_name = $last_names[array_rand($last_names)];
            
            if (user_exists($username, $new_site_url)) {
                // Add existing user to the new site
                $set_role_command = "user set-role $username subscriber --url=$new_site_url";
                execute_wp_cli_command($set_role_command);
            } else {
                // Create new user
                $create_user_command = "user create $username $email --role=subscriber --user_pass='$password' --first_name='$first_name' --last_name='$last_name' --display_name='" . $first_name . " " . $last_name . "'";
                execute_wp_cli_command($create_user_command);
                // Add the new user to the new site
                $add_user_to_site_command = "user set-role $username subscriber --url=$new_site_url";
                execute_wp_cli_command($add_user_to_site_command);
                $usernames[] = ['username' => $username, 'password' => $password];
            }
        }

        // add admins
        $admin_usernames = [];
        $admin_username = "admin".$unique_id;
        $admin_email = "$admin_username@$site_slug.com";
        $password = 'password';  // Default password for new users
        if (user_exists($admin_username, $new_site_url)) {
            // Add existing user to the new site
            $set_role_command = "user set-role $admin_username administrator --url=$new_site_url";
            execute_wp_cli_command($set_role_command);
        } else {
            // Create new user
            $create_user_command = "user create $admin_username $admin_email --role=administrator --user_pass=$password";
            execute_wp_cli_command($create_user_command, $new_site_url);
            
            // Add capabilities to the admin user
            $add_capabilities_to_admin = "cap add 'administrator' 'manage_network_users' --url=$new_site_url";
            execute_wp_cli_command($add_capabilities_to_admin, $new_site_url);

            $admin_usernames[] = ['username' => $admin_username, 'password' => $password];
        }

        // Retrieve the admin user's ID
        $get_admin_id_command = "user get $admin_username --field=ID --url=$new_site_url";
        $admin_id = trim(execute_wp_cli_command($get_admin_id_command));


        // 3. Add 5 blog posts
        for ($i = 1; $i <= 2; $i++) {
            $create_post_command = "post create --post_type=post --post_title='Blog Post $i' --post_content='This is blog post $i.' --post_status=publish  --url=$new_site_url --post_author=$admin_id";
            execute_wp_cli_command($create_post_command, $new_site_url);
        }

        // 4. Add 5 pages
        for ($i = 1; $i <= 2; $i++) {
            $create_page_command = "post create --post_type=page --post_title='Page $i' --post_content='This is page $i.' --post_status=publish  --url=$new_site_url --post_author=$admin_id";
            execute_wp_cli_command($create_page_command, $new_site_url);
        }

        // 5. Install and activate three plugins
        switch ($site_type) {
            case 'site':
                $plugins = ['login-as-user', 'fixed-html-toolbar'];
                break;
            case 'loginasuser':
                $plugins = ['login-as-user'];
                break;
            case 'fixedhtmltoolbar':
                $plugins = ['fixed-html-toolbar'];
                break;
            default:
                $plugins = [];
        }   
        if (!empty($plugins )) {
            foreach ($plugins as $plugin) {
                $install_plugin_command = "plugin install $plugin --activate  --url=$new_site_url";
                execute_wp_cli_command($install_plugin_command, $new_site_url);
            }
        }

        // Schedule the deletion of the sub-site after X minutes (e.g., 60 minutes)
        $delay_minutes = 10; // 10 minutes
        schedule_site_deletion($site_slug, $delay_minutes);

        // Redirect to the new site URL
        header("Location: $new_site_url" . 'wp-admin');
        exit;

        // Display success message with site URL and user details
        $new_site_url = $new_site_url . 'wp-admin';
        echo "Site created successfully and scheduled for deletion after $delay_minutes minutes!<br>";
        echo "New site URL: <a href='$new_site_url' target='_blank'>$new_site_url</a><br>";
        echo "User access details:<br>";
        foreach ($admin_usernames as $user) {
            echo "Username: {$user['username']}, Password: {$user['password']}<br>";
        }
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
    <h1>Generate New Site</h1>
    <?php
    $types = ['site', 'loginasuser', 'fixedhtmltoolbar'];
    ?>
    <form method="post">
        <select name="site_type">
            <?php foreach ($types as $type) : ?>
                <option value="<?php echo $type; ?>"><?php echo ($type === 'site' ? 'All' : $type); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="generate_site">Generate New Site</button>
    </form>
</body>
</html>
