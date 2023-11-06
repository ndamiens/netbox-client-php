# Netbox API Client

This project is an API client for [Netbox](https://netbox.dev/)

## To iterate over your netbox entities

```php
<?php

use ND\Netbox\Client;

$netbox = new Client("https://your-netbox/", "your-api-key");

foreach ($netbox->getSites() as $site) {
    echo $site["name"]."\n";
}
```

Read entities attribues as an associative array

## Retrieve an entity

```php
<?php

$site = $netbox->getSite(42);
```

## To update an entity

after retrieve

```php
$site['comments'] = "This is a test";
$site->save();
```