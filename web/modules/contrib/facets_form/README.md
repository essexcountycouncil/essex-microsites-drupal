# Facets Form

Exposes facets from a facets source as Drupal Form API elements.

## Why?

Even displayed as a dropdown select or checkboxes, the facet widgets are
rendered as link lists. Then a JavaScript snippet is responsible to convert them
into form elements, such as `<select>` or `<input type="checkbox">`.

This has some drawbacks:
* Even the widgets are displayed as form elements, they are not piped through
  Drupal Form API. As a consequence, third-party code cannot perform form
  alterations, and they are not subject to standard Form API elements theming.
* The current Facets implementation is applying the filter as soon as the
  element is selected or checked. This is a UX issue, mostly on mobile devices.
  Also, it doesn't allow scenarios where the user wants to do multiple filter
  selection before the filters are submitted.

## Dependency

This module depends on [Facets 2.x](https://www.drupal.org/project/facets)

## Upgrading from "1.0.0-alpha2"

In case you applied [patch](https://www.drupal.org/project/facets/issues/3223956)
manually, you will need to remove it. In case you used composer this will happen
when upgrading the version.

## Usage

* _Facets Form_ provides a block containing a form for each facets source.
* By default, each block is exposing all _eligible facets_. But the site builder
  is able to limit the list of facets to be exposed from the block
  configuration.
* The "submit" and "reset" buttons labels can be also be configured on block
  level.
* _Eligible facets_ are all facets using widgets plugins that are extending the
  `\Drupal\facets_form\FacetsFormWidgetInterface`. _Facets Form_ module ships
  with two widgets, aiming to provide a Form API alternative to _Facets_ module
  native widgets:
  * Dropdown (inside form) `facets_form_dropdown`
  * Checkboxes (inside form) `facets_form_checkbox`

### Submodules

* `facets_form_date_range`: Provides a third, bonus, date range widget.
* `facets_form_live_total`: A module that allows to dynamically update the
  Facets Summary total as the user interacts with the Facets Form widgets.

## Extending

Third-party modules can provide additional widgets to be usable inside forms, by
implementing the `\Drupal\facets_form\FacetsFormWidgetInterface` interface. A
base template file `facets-form-item--{WIDGET_PLUGIN_ID}.html.twig` should be
also provided, if the widget decides to render the facet item via
`facets_form_item` theme.

### Javascript

Client side interaction with facets form widgets will trigger a custom
Javascript event, names `facets_form` if, at least, one subscriber of the
`\Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent` has enabled this
feature. An example can be found in the `facets_form_live_total` sub-module.

Third-party code can add listeners to this event to fulfill their business
logic. For instance, the `facets_form_live_total` sub-module listens to browser
widgets updates and is dynamically refreshes the facets summary total.

Widgets should ship with specific Javascript snippets, with the name matching
the plugin ID and having the `.js` extension, placed under the plugin module
`js/plugin` directory, in order to event dispatching support. There's no need
for a library explicit definition, Facets Form will discover them automatically.

## Theming

The facet item label is themeable by overriding the `facets_form_item` template.
The following theme suggestions are available:

* `facets-form-item--{WIDGET_PLUGIN_ID}.html.twig`
* `facets-form-item--{WIDGET_PLUGIN_ID}--{FACET_SOURCE_PLUGIN_ID}.html.twig`

When building the filename, the underline (`_`) character should be replaced
with dash (`-`) and the colon (`:`) with two dashes (`--`). For instance, if you
plan to customize the _Dropdown_ facet item on the form corresponding to the
`search-api:views-page__search__page_1` facet source, you'll have to copy the
`templates/facets-form-item--facets-form-dropdown.html.twig` file under your
theme `templates/` directory as

```
facets-form-item--facets-form-dropdown--search-api--views-page--search--page-1.html.twig
```

Or if you want to change all _Dropdown_ facet items from all face sources, just
override `facets-form-item--facets-form-dropdown.html.twig`.

## Authors

* Claudiu Cristea, https://www.drupal.org/u/claudiucristea
* Andras Szilagyi, https://www.drupal.org/u/andras_szilagyi
