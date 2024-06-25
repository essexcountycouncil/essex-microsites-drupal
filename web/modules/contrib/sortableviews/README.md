# Sortable Views

## CONTENTS OF THIS FILE

 - Introduction
 - Requirements
 - Recommended modules
 - Installation
 - Configuration
 - Maintainers


## INTRODUCTION


This is an alternative for the popular DraggableViews module.
The difference lies in that this module stores weights directly
on entity fields.

- For a full description of this module, visit the project page:
  [sortable views](https://www.drupal.org/project/sortableviews)

- To submit bug reports and feature suggestions, or track changes:
  [issue queue](https://www.drupal.org/project/issues/sortableviews)


## REQUIREMENTS


This module requires Drupal 8.8 or higher.

Themes that do not use the Classy base theme (eg. themes that
use Stable base theme) may be missing the .view-content class
in views-view.html.twig which is required for this module to work.

Make sure your theme's views-view.html.twig template contains code such as:

```
<div class="view-content">
  {{ rows }}
</div>
```

## RECOMMENDED MODULES


This module requires no modules outside of Drupal core.


## INSTALLATION


Install/Enable the sortableviews module as you would normally
install a contributed Drupal module.

- Visit [Install/Enable](https://www.drupal.org/node/1897420)
for further information.

- Optionally you can also install using Composer:
```
composer require 'drupal/sortableviews:1.x-dev@dev'
```


## CONFIGURATION


1. Create a view of any entity and have its format be any of
"Sortable HTML list", "Sortable Unformatted list" or
"Sortable table". Make sure the entity type has a spare integer
field or base field to store the weight.

2. In the view format settings, specify such field for storing
weight.

3. Add the field "Sortableviews: Drag and drop handle." to the
view. This is the actual "handle" users will use to perform the
drag and drop.

4. Add your weight field as a sort criteria as well. It can be
either in asc or desc fashion.

5. Finally, add the "Save Sortableviews changes" handler to
either your view header of footer. This is the button users will
use to save changes and will only appear when there are changes
to be saved.

6. Your view should now be sortable.

Be aware that the sorting process will always overwrite whatever
weight an entity had. Also, weight conflicts may occur if using
multiple sortableviews for the same entity type and bundle.


## MAINTAINERS

Current maintainers:
- Ivan Bustos - [ibustos](https://www.drupal.org/u/ibustos)
- Dieter Holvoet - [dieterholvoet](https://www.drupal.org/u/dieterholvoet)
