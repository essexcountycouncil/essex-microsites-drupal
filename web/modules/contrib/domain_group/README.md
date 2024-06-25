## CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


## INTRODUCTION

This module provides integration between Domain and Group modules.

You will find a new Domain Settings tab in the group to set a
domain and other useful configurations.

## REQUIREMENTS

  * Domain - https://www.drupal.org/project/domain.
  * Group - https://www.drupal.org/project/group.


## INSTALLATION

  * Install normally as other modules are installed. For support:
    https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules


## CONFIGURATION

  * The configuration options for a group are available via:
    _/group/[GROUP ID]/domain-settings_
  * General settings are available via:
    _/admin/config/domain/domain-group_

As group entities are content and domains configuration, by default domain
configuration for groups is ignored for syncronization (import export). If you
would like to export domain configuration include the following in your site
settings.php:

`$settings['domain_group_export_group_config'] = TRUE;`

## MAINTAINERS

Current maintainers:
 * Ivan Duarte (jidrone) - https://www.drupal.org/u/jidrone
 * Fabian Sierra (fabiansierra5191) - https://www.drupal.org/u/fabiansierra5191
