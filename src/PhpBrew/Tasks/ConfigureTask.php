<?php

namespace PhpBrew\Tasks;

use PhpBrew\Config;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Task to run `make`
 */
class BuildTask extends BaseTask
{

    public $o;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }


    public function setOptimizationLevel($o)
    {
        $this->o = $o;
    }

    public function build($version)
    {
        $root        = Config::getPhpbrewRoot();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if (!file_exists('configure')) {
            $this->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== false or die('buildconf error');
        }

        $builder = ProcessBuilder::create(array('./configure'));

        // append cflags
        if ($this->o) {
            $o = $this->o;

            $cflags = getenv('CFLAGS');
            $builder->setEnv('CFLAGS', "$cflags -O$o");
            $_ENV['CFLAGS'] = "$cflags -O$o";
        }

        $args = array();
        $builder
            ->add("--prefix=$buildPrefix")
            ->add("--with-config-file-path={$buildPrefix}/etc")
            ->add("--with-config-file-scan-dir={$buildPrefix}/var/db")
            ->add("--with-pear={$buildPrefix}/lib/php");

    }
}


