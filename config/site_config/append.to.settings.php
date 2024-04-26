
// Load shared settings
if (file_exists($app_root . '/' . $site_path . '/settings.shared.php')) {
  include $app_root . '/' . $site_path . '/settings.shared.php';
}

if (isset($_ENV['DRUPAL_ENV']) && in_array($_ENV['DRUPAL_ENV'], array('dev', 'stage', 'prod', 'local-k8s'))) {
  // Prepare 3 settings, settings.dev.php, settings.stage.php and settings.prod.php
  // All three files are in git, to track changes.
  // But, sensitive data such as mysql passwords should not be in those files.
  if (file_exists($app_root . '/' . $site_path . '/settings.' . $_ENV['DRUPAL_ENV'] . '.php')) {
    include $app_root . '/' . $site_path . '/settings.' . $_ENV['DRUPAL_ENV'] . '.php';
  }
}

// Enable the reverse_proxy configuration. This enables the webserver to serve redirects directly to https and not to http first
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = array($_SERVER['REMOTE_ADDR']);
