<?php

namespace PhpBrew\Tasks;

use PhpBrew\Config;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function build($nice = null)
    {
        $this->info("===> Building...");

        $builder = ProcessBuilder::create(array('make'));

        if ($this->logPath) {
            // $builder->add('2>&1 >> ' .  $this->logPath);
        }

        if ($nice) {
            $builder->add("nice -n $nice");
        }

        $process = $builder->getProcess();
        $this->debug($process->getCommandLine());

        $startTime = microtime(true);

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

        $buildTime = (int)((microtime(true) - $startTime) / 60);
        $this->info("Build finished: $buildTime minutes.");
    }
}


