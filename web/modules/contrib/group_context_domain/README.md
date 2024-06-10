# Group Context: Domain
## Installation
Install the module like you normally would and assign the "Set domain group" permission. Accounts with this permission will be able to assign a domain to group entities they have edit access to (via the Group module).

## Features
### Context provider
The GroupFromDomainContext class acts as a context provider that will look at the current domain and return a Group context if there was a Group entity with this Domain assigned.

You can use this context to, for example, show the Group operations block on all pages of your site, rather than just the ones with a group ID in the URL.

When used with the [Group Sites](https://www.drupal.org/project/group_sites) module, it will limit access to just the Group that is represented by the active Domain.

### Cache context
To allow you to cache whatever code that relies on the context provider, a `url.site.group` cache context is also provided. This will return the ID of the detected Group entity or `group.none` if none was found.

## Limitations
For practical reasons a Group entity may not be tied to multiple domains or vice versa.
