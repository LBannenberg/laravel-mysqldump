<?php

declare(strict_types=1);

namespace Corrivate\LaravelMysqldump\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
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

        if (! App::isDownForMaintenance()) {
            $this->warn('The application is not in maintenance mode. Please enable maintenance mode first, using `artisan down`.');

            return Command::FAILURE;
        }

        if ($this->isPulseInstalled()) {
            $this->confirm('Have you paused the pulse:check supervisor task?');
        }

        if ($problem = $this->checkFile($filename)) {
            $this->output->error($problem);

            return Command::FAILURE;
        }

        $command = $this->prepareCommand($filename);

        // The actual import
        $this->output->info("Importing $this->tempFilename...");

        $result = Process::run($command);

        if ($result->successful()) {
            $this->output->success('Import complete!');
        } else {
            $this->error('Failed to import SQL file: ' . $result->errorOutput());
        }

        if ($this->tempFilename && file_exists($this->tempFilename)) {
            $this->info('Cleaning up..');
            unlink($this->tempFilename);
        }

        return (int) $result->exitCode();
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

    private function prepareCommand(string $fileName): string
    {
        return sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($this->tempFilename ?: $fileName)
        );
    }

    private function isPulseInstalled(): bool
    {
        $installedJsonPath = base_path('vendor/composer/installed.json');

        if (! file_exists($installedJsonPath)) {
            return false;
        }

        $installed = json_decode(file_get_contents($installedJsonPath), true);

        foreach ($installed['packages'] as $package) {
            if ($package['name'] === 'laravel/pulse') {
                return true;
            }
        }

        return false;
    }
}
