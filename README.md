# laravel-mysqldump

## Purpose

* Easily dump whole databases or specific tables, with `artisan mysql:export`
* Easily import, with `artisan mysql:import`
* Strip content from tables, for example, strip the customer data but keep the product catalog.
 

## Credits

This package makes heavy use of [spatie/db-dumper](https://github.com/spatie/db-dumper). The original inspiration is based on [magerun2](https://github.com/netz98/n98-magerun2) and [mage-db-sync](https://github.com/jellesiderius/mage-db-sync). 

## Setup

Install with: 
```bash
composer require corrivate/laravel-mysqldump
```

## Configuration
In your `config/database.php` you can add configuration for stripping tables:

![](docs/config.png)


