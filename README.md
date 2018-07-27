# Regiondo Bundle for Contao Open Source CMS

The bundle provides the Regiondo integration with Contao.

As of now the bundle offers the following:

1. Regiondo product/events synchronization with calendar events.
2. Iframe booking widget content element.
3. Cart widget frontend module.

The custom booking widget, cart and checkout modules are planned for the future.


## Installation

Execute the following command in your Contao 4 project folder:

```
$ composer require derhaeuptling/regiondo-bundle
```

Then run the Contao install tool to update the database.


## Configuration

Once you have installed the bundle, add the below configuration to your `app/config/config.yml` file.

The Regiondo keys can be obtained in Regiondo control panel in the
`Shop Configuration > Website Integration > Api Configuration` section.

```yaml
derhaeuptling_regiondo:
    public_key: 'DE123456789' # The Regiondo public key
    secure_key: '123456789' # The Regiondo secure key
    vendor: 'foobar' # The Regiondo vendor name
    assets_folder: 'files/regiondo_events' # Target folder where event images will be downloaded to
    sandbox: true # Enable/disable the sandbox mode (optional, defaults to false)
    debug: '%kernel.debug%' # Enable/disable the debug mode (optional, defaults to false)
    widget_base_url: 'https://foobar.regiondo.com/' # The custom base URL of the iframe widgets (optional)
```

### Improve performance with cache

You can improve the backend performance by adding the cache provider that will handle some repeating API requests.
In order to do that, require the `doctrine/doctrine-cache-bundle` dependency and adjust the configuration of your app:

```yaml
derhaeuptling_regiondo:
    ...
    cache_provider: 'doctrine_cache.providers.app_regiondo_cache'
    ...

doctrine_cache:
    providers:
        app_regiondo_cache:
            type: file_system
            file_system:
                extension: ".cache"
                directory: "%kernel.cache_dir%/app/regiondo"
```

## Pass values to booking iframe widget with URL hash

The widget inside booking iframe content element can have preselected values using the hash. This is especially useful
if you want to redirect the user to the booking page directly from the event list or calendar view.

You can append the URL hash to the event link as shown in the example of `event_list` template:

```php
…
<a href="<?= $this->href ?><?= ($urlHash = \Derhaeuptling\RegiondoBundle\ContentElement\EventBookingIframeElement::generateUrlHash($this->id)) ? ('#' . $urlHash) : '' ?>"
…
```


## Synchronize using CRON

You can set up the CRON on your server to synchronize the Regiondo data on a regular basis by executing the following command:

```
$ vendor/bin/contao-console regiondo:sync
```

The command also allows to synchronize individual parts of the data:

```
# Synchronize all products
$ vendor/bin/contao-console regiondo:sync products

# Synchronize all calendars
$ vendor/bin/contao-console regiondo:sync calendars

# Synchronize calendars with ID 12, 13, 14
$ vendor/bin/contao-console regiondo:sync calendars --calendars=12,13,14

# Synchronize all reviews
$ vendor/bin/contao-console regiondo:sync reviews

# Synchronize reviews with ID 21,22
$ vendor/bin/contao-console regiondo:sync reviews --reviews=21,22
```
