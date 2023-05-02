# Essex microsites

This project was originally installed using the [Composer](https://getcomposer.org/) project template at [LocalGov Drupal Microsites](https://github.com/localgovdrupal/localgov_microsites_project). More [documentation about LGD Microsites can be found here](https://docs.localgovdrupal.org/microsites/).

## Quick start for local development

Use [DDEV](https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/).

1. Clone this repo.
2. Run `ddev start` in the project root.
3. Run `ddev composer install` to install the project dependencies.
4. Run `ddev drush si localgov_microsites`.

The controller site is then available at https://ecc-microsites.ddev.site

Three other domains have been set up in  DDEV, to be used as microsites:

- https://braintree.ddev.site
- https://brentwood.ddev.site
- https://chelmsform.ddev.site

Further hostnames and FQDNs can be added to `.ddev/config.yaml` to create other further microsites.
