<?php

/**
 * @file
 * Template for settings.local.php file for Drupal 7 Docksal with caching.
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
