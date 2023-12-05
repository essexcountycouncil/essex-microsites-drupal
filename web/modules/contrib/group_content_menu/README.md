# Group Content Menu

---------------

## About this Module

[Group](https://www.drupal.org/project/group) module
> allows you to create arbitrary collections of your content and users on your
> site and grant access control permissions on those collections.

This module lets you setup per group menus. The difference between this module
and [Group Menu](https://www.drupal.org/project/groupmenu) is mainly in its
data model. That module creates everything as menu config items. This creates
everything as content entities.

The upside to this is that config ignore is not needed. No more config
replicating like rabbits all over the place when more and more groups are
created. Nor are any patches needed for Drupal core or Group module.

### Installing the Group Content Menu Module

Note: Use of the module on a Composer managed site is also supported.

1. Copy/upload the module to the modules directory of your Drupal installation.

1. Enable the module in 'Extend' (/admin/modules).

1. Configure the module to define a new menu type
  (/admin/structure/group_content_menu_types).

1. Enable these the type on the group content enablement tab i.e.
  /admin/group/types/manage/{group_type}/content.
