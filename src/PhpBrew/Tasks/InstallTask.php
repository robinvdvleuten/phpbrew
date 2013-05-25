<?php

namespace PhpBrew\Tasks;

use PhpBrew\Config;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Task to run `make install`
 */
class InstallTask extends BaseTask
{
    public $logPath;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function install()
    {
        $this->info("Installing...");

        $builder = ProcessBuilder::create(array('make install'));

        if ($this->logPath) {
            // $builder->add('2>&1 >> ' .  $this->logPath);
        }

        $process = $builder->getProcess();
        $this->debug($process->getCommandLine());

        $process->run(function ($type, $buffer) {
            if ('err' === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}


