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
    {--f|filename=dump.sql : name of the file to export to}
    {--m|mode=stripped : Perform either `full`, `schema` or `stripped` export}
    {--g|gzip=1 : compress output (adds .gz to filename)}
    {--s|strip-manual= : list extra tables to strip}
    {--c|config-stripped=1 : strip configured tables in config("database.connections.mysql.strip_tables_on_export") ; set 0 if you only want to strip manually}';

    protected $description = 'Export a MySQLdump';

    public function handle(): int
    {
        $filename = $this->cleanFilename();

        if(file_exists($filename)) {
            $this->output->error("File $filename already exists, please clean up first to prevent data loss.");
            return Command::FAILURE;
        }

        if(file_exists("$filename.gz")) {
            $this->output->error("File $filename.gz already exists, please clean up first to prevent data loss.");
            return Command::FAILURE;
        }

        return match($this->option('mode')) {
            'stripped' => $this->strippedDump($filename),
            'schema' => $this->schemaDump($filename),
            'full' => $this->fullDump($filename),
            default => function(){
                $this->output->error('Bad option: `mode` must be one of `stripped`, `schema` or `full`');
                return Command::INVALID;
            }
        };
    }


    private function cleanFilename(): string
    {
        $filename = trim($this->option('filename'));

        if (str_ends_with($filename, '.gz')) {
            $filename = substr($filename, 0, strlen($filename) - 3);
            if(!$this->option('gzip')) {
                throw new \InvalidArgumentException("You cannot specify a filename ending in .gz and set --gzip=0");
            }
        }

        if (!str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }

        return $filename;
    }


    private function schemaDump(string $filename): int
    {
        $this->output->info("Writing schema dump to $filename");

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
            $this->output->info("Zipping dump to $filename.gz");
            Process::forever()->run(['gzip', $filename]);
            if(file_exists($filename)) {
                unlink($filename);
            }
        }

        $this->output->success('Export complete!');
        return Command::SUCCESS;
    }

    private function fullDump(string $filename): int
    {
        $command = $this->baseCommand();

        if ($this->option('gzip')) {
            $filename .= '.gz';
            $command->useCompressor(new GzipCompressor);
        }

        $this->output->info("Exporting full dump to $filename");
        $command->dumpToFile($filename);

        $this->output->success('Export complete!');
        return Command::SUCCESS;
    }

    private function strippedDump(string $filename): int
    {
        $this->output->info("Writing schema dump to $filename");

        $strip = $this->parseStripOptions();

        if (! $strip) {
            $this->output->error('Running in `stripped` mode, but no tables configured for stripping and no tables manually selected for stripping.');
            return Command::FAILURE;
        }

        $this->output->info('Stripping these tables:');
        foreach($strip as $name) {
            $this->output->writeln("  $name");
        }

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
            $this->output->info("Zipping dump to $filename.gz");
            Process::forever()->run(['gzip', $filename]);
            if(file_exists($filename)) {
                unlink($filename);
            }
        }

        $this->output->success('Export complete!');
        return Command::SUCCESS;
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

        if ($this->option('strip-manual')) {
            $strip = explode(',', $this->option('strip-manual'));
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
