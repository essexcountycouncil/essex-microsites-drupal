<?php

/**
 * @file
 * Template for settings.local.php file for Drupal 7 Docksal without caching.
 */

// Docksal DB connection settings.
$databases['default']['default'] = [
  'database' => 'default',
  'username' => 'user',
  'password' => 'user',
  'host' => 'db',
  'driver' => 'mysql',
];

$conf['file_temporary_path'] = '/tmp';
$conf['file_private_path'] = '/var/www/private';

// Error All The Things!
// (And show them on screen as well...)
$conf['error_level'] = 2;
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

// Disable CSS and JS aggregation.
$conf['preprocess_css'] = FALSE;
$conf['preprocess_js'] = FALSE;

// Stage File Proxy settings.
$conf['stage_file_proxy_origin'] = '${STAGE_FILE_PROXY_URL}';

// Show template hints.
$conf['theme_debug'] = TRUE;

// No caching.
$conf['block_cache'] = 0;
$conf['cache'] = 0;

if (!class_exists('DrupalFakeCache')) {
  $conf['cache_backends'][] = 'includes/cache-install.inc';
}
// Default to throwing away cache data.
$conf['cache_default_class'] = 'DrupalFakeCache';

// Rely on the DB cache for form caching - otherwise forms fail.
$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
