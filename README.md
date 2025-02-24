# laravel-mysqldump

## Purpose

* Easily dump whole databases or specific tables, with `artisan mysql:export`
* Easily import, with `artisan mysql:import`
* Strip content from tables, for example, strip the customer data but keep the product catalog.


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

If you need to do an export where you want to override this configuration, and only strip the tables you specify on the command line, you can do so by passing a `--config-stripped=0` argument along with the `--strip=your,specific,tables` argument. 


## Credits

This package is built on top of [spatie/db-dumper](https://github.com/spatie/db-dumper). The original inspiration was the Magento packages [magerun2](https://github.com/netz98/n98-magerun2) and [mage-db-sync](https://github.com/jellesiderius/mage-db-sync).

## Corrivate
(en.wiktionary.org)

Etymology 

From Latin *corrivatus*, past participle of *corrivare* ("to corrivate").

### Verb

**corrivate** (*third-person singular simple present* **corrivates**, *present participle* **corrivating**, *simple past and past participle* **corrivated**)

(*obsolete*) To cause to flow together, as water drawn from several streams. 

