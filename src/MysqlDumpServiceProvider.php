<?php

namespace Corrivate\LaravelMysqldump;

use Illuminate\Support\ServiceProvider;

class MysqlDumpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->commands([
            \Corrivate\LaravelMysqldump\Console\Command\MysqlExport::class,
            \Corrivate\LaravelMysqldump\Console\Command\MysqlImport::class
        ]);
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            \Corrivate\LaravelMysqldump\Console\Command\MysqlExport::class,
            \Corrivate\LaravelMysqldump\Console\Command\MysqlImport::class
        ];
    }
}
