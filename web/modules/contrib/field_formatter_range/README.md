# Field Formatter Range

This module provides the option to display only selected range of values for
multivalued entity fields.

For example if you have an image field attached to an entity and the entity has
15 images attached to it, but you want to display only the first 5 of them,
this module is exactly what you are looking for.

Beside setting the range (offset and number of items to show), you can also
reverse the order, so you can display just the last 5 images and by setting
proper values you can display them in order or in reverse order too.


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module adds options on each field formatter of multivalued fields. You can
access these options like you normally would when configuring a field display.

Those options are:
- offset: where to start.
- limit: how many items to show.
- order: will display the items in the selected order, default, reverse or
  random.


## Maintainers

Current maintainers:
- Florent Torregrosa - [Grimreaper](https://www.drupal.org/user/2388214)
- Pierre Dureau - [pdureau](https://www.drupal.org/user/1903334)

Previous maintainers:
- Ivan Jaros - [ivanjaros](https://www.drupal.org/user/135190)
