# Test WordPress with Docker

Easy WordPress development with Docker and Docker Compose.

With this project you can quickly run the following:

-   [WordPress and WP CLI](https://hub.docker.com/_/wordpress/)
-   [phpMyAdmin](https://hub.docker.com/r/phpmyadmin/phpmyadmin/)
-   [MySQL](https://hub.docker.com/_/mysql/)

Contents:

-   [Requirements](#requirements)
-   [Configuration](#configuration)
-   [Installation](#installation)
-   [Usage](#usage)

## Requirements

Make sure you have the latest versions of **Docker** and **Docker Compose** installed on your machine.

Clone this repository or copy the files from this repository into a new folder. In the **docker-compose.yml** file you may change the IP address (in case you run multiple containers) or the database from MySQL to MariaDB.

Make sure to [add your user to the `docker` group](https://docs.docker.com/install/linux/linux-postinstall/#manage-docker-as-a-non-root-user) when using Linux.

## Configuration

Copy the example environment into `.env`

```
cp env.example .env
```

Edit the `.env` file to change the default IP address, MySQL root password and WordPress database name.

## Installation

Open a terminal and `cd` to the folder in which `docker-compose.yml` is saved and run:

```
docker-compose up
```

This creates two new folders next to your `docker-compose.yml` file.

-   `wp-data` – used to store and restore database dumps
-   `wp-app` – the location of your WordPress application

The containers are now built and running. You should be able to access the WordPress installation with the configured IP in the browser address. By default it is `http://127.0.0.1`.

For convenience you may add a new entry into your hosts file.

Changes the ports, if needed, by editing the `docker-compose.yml` file.

## Usage

### Starting containers

You can start the containers with the `up` command in daemon mode (by adding `-d` as an argument) or by using the `start` command:

```
docker-compose start
```

### Stopping containers

```
docker-compose stop
```

### Removing containers

To stop and remove all the containers use the`down` command:

```
docker-compose down
```

Use `-v` if you need to remove the database volume which is used to persist the database:

```
docker-compose down -v
```

### Project from existing source

Copy the `docker-compose.yml` file into a new directory. In the directory you create two folders:

-   `wp-data` – here you add the database dump
-   `wp-app` – here you copy your existing WordPress code

You can now use the `up` command:

```
docker-compose up
```

This will create the containers and populate the database with the given dump. You may set your host entry and change it in the database, or you simply overwrite it in `wp-config.php` by adding:

```
define('WP_HOME','http://wp-app.local');
define('WP_SITEURL','http://wp-app.local');
```

### Creating database dumps

```
./export.sh
```

### Developing a Theme

Configure the volume to load the theme in the container in the `docker-compose.yml`:

```
volumes:
  - ./theme-name/trunk/:/var/www/html/wp-content/themes/theme-name
```

### Developing a Plugin

Configure the volume to load the plugin in the container in the `docker-compose.yml`:

```
volumes:
  - ./plugin-name/trunk/:/var/www/html/wp-content/plugins/plugin-name
```

### WP CLI

The docker compose configuration also provides a service for using the [WordPress CLI](https://developer.wordpress.org/cli/commands/).

Sample command to install WordPress:

```
docker-compose run --rm wpcli core install --url=http://localhost --title=test --admin_user=admin --admin_email=test@example.com
```

Or to list installed plugins:

```
docker-compose run --rm wpcli plugin list
```

For an easier usage you may consider adding an alias for the CLI:

```
alias wp="docker-compose run --rm wpcli"
```

This way you can use the CLI command above as follows:

```
wp plugin lists
```

### phpMyAdmin

You can also visit `http://127.0.0.1:8080` to access phpMyAdmin after starting the containers.

The default username is `root`, and the password is the same as supplied in the `.env` file.

## Custom Domains

To use a custom fake domain locally, such as mywordpress.test instead of http://127.0.0.1:8590/, you need to perform a few steps involving your system's hosts file and your web server configuration. Here's a step-by-step guide:

1. Open a terminal.
2. Edit the `/etc/hosts` file with sudo privileges
3. Add a new line: 127.0.0.1 subdomain.loginasuser.xyz.
4. Save and close the file
5. Then Modify WordPress Configuration `wp-config.php`

```php
define('WP_HOME','http://subdomain.loginasuser.xyz/subfolder');
define('WP_SITEURL','http://subdomain.loginasuser.xyz/subfolder');
```

After completing these steps, you should be able to access your local WordPress site by navigating to `http://subdomain.loginasuser.xyz/subfolder` in your browser.

## .htaccess

```bash
# BEGIN WordPress

RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /subfolder/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /subfolder/index.php [L]

# END WordPress
```
