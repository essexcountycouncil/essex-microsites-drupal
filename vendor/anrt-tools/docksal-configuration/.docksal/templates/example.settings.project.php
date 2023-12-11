<?php

/**
 * @file
 * Docksal project specific override configuration feature.
 *
 * Rename this file to "settings.project.php".
 * Add any project specific settings,
 * such as API/services/hosting config overrides.
 *
 * Edit/Delete/Comment-out examples below as required.
 */

// Example if project using Redis:
$conf['redis_client_host'] = 'redis';
$conf['port'] = '6379';
$conf['redis_client_interface'] = 'PhpRedis';
$conf['cache_backends'][]       = 'sites/all/modules/contrib/redis/redis.autoload.inc';
$conf['cache_default_class']    = 'Redis_Cache';
// The 'cache_form' bin must be assigned to non-volatile storage.
$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
// The 'cache_field' bin must be transactional.
$conf['cache_class_cache_field'] = 'DrupalDatabaseCache';

// Varnish Configuration.
$conf['cache_backends'][] = 'sites/all/modules/varnish/varnish.cache.inc';
$conf['cache_class_external_varnish_page'] = 'VarnishCache';
$conf['varnish_control_key'] = 'secret';
$conf['varnish_control_terminal'] = 'varnish:6082';
$conf['varnish_version'] = '4';
