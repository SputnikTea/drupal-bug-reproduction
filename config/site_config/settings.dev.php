<?php

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * See: https://www.drupal.org/docs/8/install/trusted-host-settings
 */
$settings['trusted_host_patterns'] = array(
  '^drupal-all$', // allow Drupal all cluster service endpoint in K8S dev env
  '^localhost$',
  '^127\.0\.0\.1$'
);
