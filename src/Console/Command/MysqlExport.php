<?php

declare(strict_types=1);

namespace Corrivate\LaravelMysqldump\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;

class MysqlExport extends Command
{
    protected $signature = 'mysql:export
    {--f|filename= : name of the file to export to}
    {--gzip=1 : compress output (adds .gz to filename)}
    {--full : do not strip any table }
    {--strip= : list extra tables to strip}
    {--config-stripped=1 : strip configured tables in config("database.connections.mysql.strip_tables_on_export") ; set 0 if you only want to --strip specific tables}
    {--schema : dump only the schema & migrations}';

    protected $description = 'Export a MySQLdump';

    public function handle(): int
    {
        if ($exitCode = $this->validateArguments()) {
            return $exitCode;
        }

        if ($filename = $this->option('filename')) {
            if (! preg_match('/\.sql$/', $filename)) {
                $filename = $filename . '.sql';
            }
        } else {
            $filename = 'dump.sql';
        }

        if ($this->option('schema')) {
            $result = $this->schemaDump($filename);
        } elseif ($this->option('full')) {
            $result = $this->fullDump($filename);
        } else {
            $result = $this->strippedDump($filename);
        }

        // Nonzero result = problem
        if ($result) {
            return $result;
        }

        $this->output->success('Export complete!');

        return 0;
    }

    private function validateArguments(): int
    {
        if ($this->option('strip') && $this->option('full')) {
            $this->output->error('Bad arguments: you cannot specify both --strip and --full.');

            return 1;
        }

        if ($this->option('schema') && ($this->option('strip') || $this->option('full'))) {
            $this->output->error('Bad arguments: you cannot combine --schema with --strip or --full.');

            return 1;
        }

        return 0;
    }

    private function schemaDump(string $filename): int
    {
        // Export schema
        $this->baseCommand()
            ->doNotDumpData()
            ->dumpToFile($filename);

        // Append migrations
        $this->baseCommand()
            ->doNotCreateTables()
            ->useAppendMode()
            ->includeTables('migrations')
            ->dumpToFile($filename);

        if ($this->option('gzip')) {
            Process::forever()->run(['gzip', $filename]);
            $this->output->info("Writing schema dump to $filename.gz");
        } else {
            $this->output->info("Writing schema dump to $filename");
        }

        return 0;
    }

    private function fullDump(string $filename): int
    {
        $command = $this->baseCommand();

        if ($this->option('gzip')) {
            $gzipCompressor = new GzipCompressor;
            $filename .= '.' . $gzipCompressor->useExtension();
            $command->useCompressor($gzipCompressor);
        }

        $this->output->info("Exporting full dump to $filename");
        $command->dumpToFile($filename);

        return 0;
    }

    private function strippedDump(string $filename): int
    {
        $strip = $this->parseStripOptions();

        if (! $strip) {
            return $this->fullDump($filename);
        }

        $this->output->info('Stripping these tables: ' . implode(', ', $strip));

        // Export schema
        $this->baseCommand()
            ->doNotDumpData()
            ->dumpToFile($filename);

        // Export data to not-stripped tables
        $this->baseCommand()
            ->doNotCreateTables()
            ->useAppendMode()
            ->excludeTables($strip)
            ->dumpToFile($filename);

        if ($this->option('gzip')) {
            Process::forever()->run(['gzip', $filename]);
            $this->output->info("Writing schema dump to $filename.gz");
        } else {
            $this->output->info("Writing schema dump to $filename");
        }

        return 0;
    }

    private function baseCommand(): MySql
    {
        return MySql::create() // @phpstan-ignore return.type
            ->setHost((string) config('database.connections.mysql.host')) // @phpstan-ignore cast.string
            ->setPort((int) config('database.connections.mysql.port')) // @phpstan-ignore cast.int
            ->setDbName((string) config('database.connections.mysql.database')) // @phpstan-ignore cast.string
            ->setUserName((string) config('database.connections.mysql.username')) // @phpstan-ignore cast.string
            ->setPassword((string) config('database.connections.mysql.password')); // @phpstan-ignore cast.string
    }

    /** @return string[] */
    private function parseStripOptions(): array
    {
        if ($this->option('config-stripped')
            && ! config('database.connections.mysql.strip_tables_on_export')
            && ! $this->option('strip')) {
            $this->output->warning("No full export requested, but also no stripping configuration found in config('database.connections.mysql.strip_tables_on_export')");
        }

        $strip = [];

        if ($this->option('strip')) {
            $strip = explode(',', $this->option('strip'));
        }

        /** @var string[] $config */
        $config = config('database.connections.mysql.strip_tables_on_export');
        if ($this->option('config-stripped') && $config) {
            $strip = array_merge($config, $strip);
        }

        $strip = array_map(fn ($item) => trim($item), $strip);
        $strip = array_filter($strip);

        return array_unique($strip);
    }
}
