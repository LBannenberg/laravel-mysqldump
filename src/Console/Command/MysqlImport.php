<?php

namespace Corrivate\LaravelMysqldump\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class MysqlImport extends Command
{
    protected $signature = 'mysql:import {--f|filename=}';
    protected $description = 'Import a MySQLdump file';

    private string $tempFilename = '';


    public function handle(): int
    {
        if ($filename = (string)$this->option('filename')) {
            if (!preg_match('/\.sql$/', $filename)) {
                $filename = $filename . '.sql';
            }
        } else {
            $filename = 'dump.sql';
        }

        if ($problem = $this->checkFile($filename)) {
            $this->output->error($problem);
            return 1;
        }

        DB::unprepared(file_get_contents($filename));
        $this->output->success("Import complete!");

        if($this->tempFilename && file_exists($this->tempFilename)) {
            unlink($this->tempFilename);
        }

        return 0;
    }

    private function checkFile(string $filename): string
    {
        if (!file_exists($filename) && file_exists("$filename.gz")) {
            $this->tempFilename = $filename;
            $this->output->info("Unzipping $filename.gz to $filename");
            Process::forever()->run(['gunzip', "-k", "$filename.gz"]);
        }

        if (!file_exists($filename)) {
            return "File to import does not exist: $filename";
        }
        return "";
    }
}
