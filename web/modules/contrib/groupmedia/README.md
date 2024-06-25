# Group Media

This module is designed to associate group specific media  with a group when
using the [Group](https://www.drupal.org/project/group) module.

After installation and configuration of the module, there will be a new tab
on group page with the overview of all media items related to the group.
Depending on the configuration (read more in "Configuration" section) it is
possible to add relation to any media item in the system, or create the
media with relation from Group Operations.

Media item edit form will also contain the list of the groups it belongs to.

- For a full description of the module, visit the
[project page](https://www.drupal.org/project/groupmedia)
  or
[project page](https://www.drupal.org/docs/contributed-modules/group-media).

- Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/groupmedia?categories=All).


## Table of contents

- Requirements
- Installation
- Configuration
- Automatic association to a group
- Adding media to group in bulk
- Maintainers


## Requirements

- [Group module](https://drupal.org/project/group)
   version greater than3.0.
- Media module.


## Installation

Install the Groupmedia module as you would normally install a contributed
Drupal module. For further information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. First you need to create a group type. Read more about this in Group
   module documentation.
2. Go to `/admin/group/types`, find group type you want to extend with
   support of media relations.
3. Select "Set available content" in the dropdown button corresponding
   to your group type. You will end up on the plugin overview page with
   the list of all relations to the group possible.
4. Enable the plugins you need. Each Media Type has its own plugin.
5. At page `/admin/group/types/manage/<your_group_type>/permissions`
   you can set the permissions for each media relation plugin.


## Automatic association to a group

Since alpha8 release it is possible to automatically add media items from
group content to the group itself.


#### Which media items will be attached?

1. Items referenced with entity reference fields where target entity type is
   media.
1. Items embedded into editor with Media Library or Entity embed modules.
1. Items referenced in the paragraphs (You need to enabled submodule
   `groupmedia_paragraphs`)

The feature is enabled by default. You can configure tracking settings here
`/admin/group/settings/media`. It is also possible to disable automatic
association. Moreover there are 3 hooks that allows to be flexible when
deciding which items should be attached and which not:
1. `hook_groupmedia_entity_group_alter`: if groups array is empty, the item
will not be attached automatically.
1. `hook_groupmedia_finder_add_alter`: if at least 1 item of `$result` array is
false, the media will not be attached automatically.
1. `hook_groupmedia_attach_group_alter`: if at least 1 item of `$result` array
   is false, the media will not be attached automatically.

Besides the configuration and hooks, there are also group content restrictions.
Each groupmedia plugin (that correspond to media bundle) has its own group
settings like group cardinality and entity cardinality that are set during the
process of configuration of new plugin. This limitations are also respected, so
even if all conditions are met, group settings are the last ones to decide.
For further details check `groupmedia.api.php` php comments.

**IMPORTANT!** Disabled/enabled feature of automatic associating doesn't
influence the manual group media relation CRUD.

## Adding media to group in bulk

Media items can be added to group in bulk operations. For this one needs to add
action "Assign Media to Group" and/or "Remove media from Group" to view that is
based on media entity type. By default the actions are not configured and they
require group id to be set. You can do it with help of "Action" module (shipped
by Drupal core). Each action can have only 1 group set. This solution is good
if the number of groups is small. If the number of groups is rather big it is
recommended to use submodule "Group Media Views Bulk Operations (VBO)" that
uses "Views bulk operations" module as a dependency. This allows to select the
group just before the action is applied.

### Documentation on-line

Read more about groupmedia module usage 
[here](https://www.drupal.org/docs/contributed-modules/group-media)
and on [official module page](https://drupal.org/project/groupmedia).


## Maintainers

- Artem Dmitriiev - [a.dmitriiev](https://www.drupal.org/u/admitriiev)
- Nikolay Lobachev - [LOBsTerr](https://www.drupal.org/u/LOBsTerr)

Supporting organization:

- 1xINTERNET GmbH - [1xINTERNET](https://www.drupal.org/1xinternet)
- European Commission - [European Commission](https://www.drupal.org/european-commission)
