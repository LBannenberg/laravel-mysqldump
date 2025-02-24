<?php

declare(strict_types=1);

namespace Corrivate\LaravelMysqldump\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class MysqlImport extends Command
{
    protected $signature = 'mysql:import {filename}';

    protected $description = 'Import a MySQLdump file';

    private string $tempFilename = '';

    public function handle(): int
    {
        $filename = (string) $this->argument('filename');

        if ($problem = $this->checkFile($filename)) {
            $this->output->error($problem);

            return 1;
        }

        // The actual import
        DB::unprepared(file_get_contents($this->tempFilename ?: $filename));
        $this->output->success('Import complete!');

        if ($this->tempFilename && file_exists($this->tempFilename)) {
            unlink($this->tempFilename);
        }

        return 0;
    }

    private function checkFile(string $filename): string
    {
        if (! Str::endsWith($filename, ['.sql', '.sql.gz'])) {
            return "Filename $filename does not end with .sql or .sql.gz";
        }

        if (! file_exists($filename)) {
            return "File to import does not exist: $filename";
        }

        if (Str::endsWith($filename, '.sql.gz')) {
            $this->tempFilename = Str::replaceLast('.gz', '', $filename);

            if (file_exists($this->tempFilename)) {
                return "Cannot extract $filename to $this->tempFilename ; $this->tempFilename already exists.";
            }

            $this->output->info("Unzipping $filename to $this->tempFilename");
            Process::forever()->run(['gunzip', '-k', "$filename"]);
        }

        // No problems encountered
        return '';
    }
}
