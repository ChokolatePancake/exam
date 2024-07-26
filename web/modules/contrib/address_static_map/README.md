# Address Static Map

Provides a formatter for the address field that renders it as a [Google static
map](https://developers.google.com/maps/documentation/staticmaps/).

This module is a Drupal 8-9 implementation of the [Address Field Static Map](
https://www.drupal.org/project/addressfield_staticmap) module. Unlike its
predecessor, it uses only the new (in D8) [address](
https://www.drupal.org/project/address) field.

This first version only provides a Google static map, it doesn't provide yet:

- a regular JS Google map that defaults to the static map if JavaScript is
  disabled
- a Mapquest static map


## Requirements

This module requires the following modules:

- [Address](https://www.drupal.org/project/address)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](
https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Get an API key from your map provider (note that as of June 2018, Google no
longer allows the use of maps without an API key).

Go to `/admin/config/system/address_static_map` and set the key to access the
Google API.


## Maintainers

- Juan Olalla - [juanolalla](https://www.drupal.org/u/juanolalla)
