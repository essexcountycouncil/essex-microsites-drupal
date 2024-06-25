# Group Sites
## Introduction
This module allows you to use any context provider that returns a Group context to set said Group as a global context value. You can then choose one of several access policies to activate when a Group entity was either found or not found.

An example would be where you use a context provider that derives a Group entity from the currently active domain. Then you would deny all Group access aside from the one that was tied to the current domain, effectively splitting one Drupal installation into multiple websites.

To make setting up your site easier, the module also comes with an admin mode that you can toggle from the admin toolbar. While this mode is on, you can use the site as if Group Sites wasn't even installed.

## Configuration

First and foremost decide who can configure the module and grant them the `configure group_sites` permission. Then, choose who can use the admin mode and assign them the `use group_sites admin mode` permission.

Install any context provider module you wish to use alongside Group Sites, such as [Group Context: Domain](https://www.drupal.org/project/group_context_domain) so that you are not stuck using the "Group from URL" context, which is discouraged.

Now visit `/admin/group/sites/settings` and choose the context provider you want to use. Here, you should also choose what to do when the selected context provider could not find a Group entity. The recommended (and default) setting is to deny all access. Finally, choose what to do when there _is_ a Group context. Currently, only one option ships with the module, which disables all but the active Group.

## Writing your own access policies

If you wish to add an option to what happens when there is or isn't a Group context, you need to write a tagged service using the proper interface and tag.

For access policies that run when no Group is detected, create a service that implements `Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface` and tag it with `group_sites_no_site_access_policy` as shown below:
```
  group_sites.no_site_access_policy.deny_all:
    class: 'Drupal\group_sites\Access\DenyAllNoSiteAccessPolicy'
    tags:
      - { name: group_sites_no_site_access_policy, priority: 10 }
```
The priority merely influences the order in which your policy shows up on the settings form.

For access policies that run when there is a Group, do the same thing but instead implement the `Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface` and tag your service with `group_sites_site_access_policy`.

Look at the existing access policies in this module to get an idea of what to do within your access policy. It helps to study the Flexible Permissions module and how Group implements this.
