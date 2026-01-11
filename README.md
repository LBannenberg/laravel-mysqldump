# laravel-mysqldump

![logo](logo.png)

<p>
<a href="https://packagist.org/packages/corrivate/laravel-mysqldump"><img src="https://img.shields.io/packagist/dt/corrivate/laravel-mysqldump" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/corrivate/laravel-mysqldump"><img src="https://img.shields.io/packagist/v/corrivate/laravel-mysqldump" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/corrivate/laravel-mysqldump"><img src="https://img.shields.io/packagist/l/corrivate/laravel-mysqldump" alt="License"></a>

</p>


## Purpose

* Easily dump whole databases or specific tables, with `artisan mysql:export`
* Easily import, with `artisan mysql:import`
* Strip content from tables, for example, strip the customer data but keep the product catalog.

<span style="color:red">**IMPORTANT**</span> Stripping content from tables does not respect foreign key constraints. Meaning, if you strip a parent table but NOT a child table that depends on the parent, and then import that file somewhere else, you're going to foreign key failures. Always make sure to also strip dependent tables.

## Setup

Install with: 
```bash
composer require corrivate/laravel-mysqldump
```

## Configuration
Configuration is not required. You can ad-hoc decide to strip tables by using `artisan mysql:export --strip=` with a comma-separated list of tables.

If your project has a standard set of tables you usually want to strip during export, you can add those to your `config/database.php` to be stripped by default.

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
    
    // These tables will only have their schema exported, no data
    'strip_tables_on_export' => [
        'cache', 'cache_locks',
        'job_batches', 'jobs',
        'users', 'password_reset_tokens', 'sessions',
        'pulse_aggregates', 'pulse_entries', 'pulse_values',
    ],
],
```

This configuration will be applied by default when using a `stripped` mode export. If you want to run a stripped export without this configuration, pass a `--config-stripped=0` option to the command.

Note that you can combine or replace this with the `--strip-manual=your,specific,tables` option.


## Usage

### Export
```bash
artisan mysql:export [options]
```

The default behavior is to create a gzipped file `dump.sql.gz` with an export based on your stripping configuration.

Options
* `--filename=dump.sql`: Name of the file to export to. Note that '.gz' will be appended if using the gzip option as well.
* `--gzip=1` can be set to 0 to turn off compression.
* `--mode=stripped`: perform a `stripped`, `schema` or `full` export. Default is stripped.
* `--config-stripped=1` adds the tables configured in `database.mysql.strip_tables_on_export` to the list of tables to strip. Default is 1; set to 0 if you only want to manually strip tables.
* `--strip-manual=` (OPTIONAL) accepts a comma-separated list of tables to strip. These will me merged with those from your configuration (if any).

### Import

```bash
artisan mysql:import <filename>
```

## Credits

This package is built on top of [spatie/db-dumper](https://github.com/spatie/db-dumper). The original inspiration was the Magento packages [magerun2](https://github.com/netz98/n98-magerun2) and [mage-db-sync](https://github.com/jellesiderius/mage-db-sync).

## Corrivate
(en.wiktionary.org)

Etymology 

From Latin *corrivatus*, past participle of *corrivare* ("to corrivate").

### Verb

**corrivate** (*third-person singular simple present* **corrivates**, *present participle* **corrivating**, *simple past and past participle* **corrivated**)

(*obsolete*) To cause to flow together, as water drawn from several streams. 

