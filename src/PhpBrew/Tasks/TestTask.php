<?php

namespace PhpBrew\Tasks;

use PhpBrew\Config;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Task to run `make test`
 */
class TestTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function test($nice = null)
    {
        $this->info("Testing...");

        $builder = ProcessBuilder::create('make test');

        if ($this->logPath) {
            // $builder->add('2>&1 >> ' .  $this->logPath);
        }

        if ($nice) {
            $builder->add("nice -n $nice");
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


