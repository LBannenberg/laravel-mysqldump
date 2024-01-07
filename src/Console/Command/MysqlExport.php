<?php

namespace Corrivate\LaravelMysqldump\Console\Command;

use Corrivate\LaravelMysqldump\MySqlDumper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Spatie\DbDumper\Compressors\GzipCompressor;

class MysqlExport extends Command
{
    protected $signature = 'mysql:export
    {--f|filename= : name of the file to export to}
    {--gzip=1 : compress output (adds .gz to filename)}
    {--strip= : list specific tables to strip}
    {--stripped : strip tables by config}
    {--schema : dump only the schema & migrations}';
    protected $description = 'Export a MySQLdump';

    public function handle(): int
    {
        if ($filename = $this->option('filename')) {
            if (!preg_match('/\.sql$/', $filename)) {
                $filename = $filename . '.sql';
            }
        } else {
            $filename = 'dump.sql';
        }

        if ($this->option('schema')) {
            $result = $this->schemaDump($filename);
        } elseif ($this->option('strip') || $this->option('stripped')) {
            $result = $this->strippedDump($filename);
        } else {
            $result = $this->defaultDump($filename);
        }

        if($result) {
            return $result;
        }

        $this->output->success("It is done!");
        return 0;
    }


    private function baseCommand(): MySqlDumper
    {
        return MySqlDumper::create()
            ->setHost(config('database.connections.mysql.host'))
            ->setPort(config('database.connections.mysql.port'))
            ->setDbName(config('database.connections.mysql.database'))
            ->setUserName(config('database.connections.mysql.username'))
            ->setPassword(config('database.connections.mysql.password'));
    }


    private function defaultDump(string $filename): int
    {
        $command = $this->baseCommand();

        if ($this->option('gzip')) {
            $gzipCompressor = new GzipCompressor();
            $filename .= '.' . $gzipCompressor->useExtension();
            $command->useCompressor($gzipCompressor);
        }

        $this->output->info("Dumping to $filename");
        $command->dumpToFile($filename);

        return 0;
    }


    private function schemaDump(string $filename): int
    {
        // Export schema
        $this->baseCommand()
            ->addExtraOption('--no-data')
            ->dumpToFile($filename);

        // Append migrations
        $this->baseCommand()
            ->doNotCreateTables()
            ->appendToDump()
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

    private function strippedDump(string $filename): int
    {
        $strip = $this->parseStripOptions();
        if(!$strip) {
            $this->output->error("Cannot strip tables.");
            return 1;
        }
        $this->output->info("Stripping these tables: " . implode(', ', $strip));

        // Export schema
        $this->baseCommand()
            ->addExtraOption('--no-data')
            ->dumpToFile($filename);

        // Export data to not-stripped tables
        $this->baseCommand()
            ->doNotCreateTables()
            ->appendToDump()
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

    private function parseStripOptions(): array
    {

        $strip = [];
        if ($this->option('strip')) {
            $strip = explode(',', $this->option('strip'));
        }
        if($this->option('stripped')) {
            if(!config('databases.export.stripped')) {
                $this->output->error("No --stripped configuration found. You can make this configuration in config/databases.php => databases.export.stripped");
                return [];
            }
            $strip = array_merge(config('databases.export.stripped'), $strip);
        }
        $strip = array_map(fn($item) => trim($item), $strip);
        $strip = array_filter($strip);

        // TODO: logic for groups (@) and wildcards (*)
        // see https://github.com/netz98/n98-magerun2/blob/develop/src/N98/Util/Console/Helper/DatabaseHelper.php#L460
        foreach($strip as $table) {
            if(Str::contains($table, ['@', '*'])) {
                $this->output->error("Groups (@) and wildcards (*) are not implemented yet; in '$table'");
                return [];
            }
        }

        return array_unique($strip);
    }


}
