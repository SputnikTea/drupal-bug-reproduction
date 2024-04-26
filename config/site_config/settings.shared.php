<?php

ini_set('memory_limit', '512M');

/**
 * Database settings:
 *
 * The $databases array specifies the database connection or
 * connections that Drupal may use.  Drupal is able to connect
 * to multiple databases, including multiple types of databases,
 * during the same request.
 *
 * One example of the simplest connection array is shown below. To use the
 * sample settings, copy and uncomment the code below between the @code and
 * @endcode lines and paste it after the $databases declaration. You will need
 * to replace the database username and password and possibly the host and port
 * with the appropriate credentials for your database system.
 *
 * The next section describes how to customize the $databases array for more
 * specific needs.
 *
 * @code
 * $databases['default']['default'] = array (
 *   'database' => 'databasename',
 *   'username' => 'sqlusername',
 *   'password' => 'sqlpassword',
 *   'host' => 'localhost',
 *   'port' => '3306',
 *   'driver' => 'mysql',
 *   'prefix' => '',
 *   'collation' => 'utf8mb4_general_ci',
 * );
 * @endcode
 */
$databases['default']['default'] = array (
  'database' => getenv('MARIADB_DATABASE'),
  'username' => getenv('MARIADB_USER'),
  'password' => getenv('MARIADB_PASSWORD'),
  'prefix' => '',
  'host' => getenv('MARIADB_HOST'),
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * This variable will be set to a random value by the installer. All one-time
 * login links will be invalidated if the value is changed. Note that if your
 * site is deployed on a cluster of web servers, you must ensure that this
 * variable has the same value on each server.
 *
 * For enhanced security, you may set this variable to the contents of a file
 * outside your document root; you should also ensure that this file is not
 * stored with backups of your database.
 *
 * Example:
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = 'yMJnlSJq-AuMu0JyzcrE3VYYMm3I0RAniDdzSDWXNsO783nTnixXDkiKyxYgxbzLEhkyUzxLYg';

/**
 * Location of the site configuration files.
 *
 * The $settings['config_sync_directory'] specifies the location of file system
 * directory used for syncing configuration data. On install, the directory is
 * created. This is used for configuration imports.
 *
 * The default location for this directory is inside a randomly-named
 * directory in the public files path. The setting below allows you to set
 * its location.
 */
$settings['config_sync_directory'] = '/var/www/sync';

/**
 * Temporary file path:
 *
 * A local file system path where temporary files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * If this is not set, the default for the operating system will be used.
 *
 * @see \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory()
 */
$settings['file_temp_path'] = '/tmp';
