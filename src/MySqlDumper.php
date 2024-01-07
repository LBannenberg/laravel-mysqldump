<?php

namespace Corrivate\LaravelMysqldump;

class MySqlDumper extends \Spatie\DbDumper\Databases\MySql
{
    protected bool $append = false;
    public function appendToDump(bool $value=true): self
    {
        $this->append = $value;
        return $this;
    }

    protected function echoToFile(string $command, string $dumpFile): string
    {
        $dumpFile = '"' . addcslashes($dumpFile, '\\"') . '"';

        if ($this->compressor) {
            return $this->getCompressCommand($command, $dumpFile);
        }

        if($this->append) {
            return $command . ' >> ' . $dumpFile;
        }

        return $command . ' > ' . $dumpFile;
    }
}
