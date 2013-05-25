<?php

namespace PhpBrew;

use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use PhpBrew\VariantBuilder;
use Symfony\Component\Process\ProcessBuilder;

class Builder
{
    /**
     * @var CLIFramework\Logger logger object
     */
    public $logger;

    public $options;

    /**
     * @var string Version string
     */
    public $version;

    /**
     * @var string source code directory, path to extracted source directory
     */
    public $targetDir;

    /**
     * @var source build directory
     */
    public $buildDir;

    /**
     * @var string phpbrew root
     */
    public $root;

    public function __construct($targetDir,$version)
    {
        $this->targetDir   = $targetDir;
        $this->root        = Config::getPhpbrewRoot();
        $this->buildDir    = Config::getBuildDir();
        $this->version = $version;
        chdir($targetDir);
    }

    public function configure(\PhpBrew\Build $build)
    {
        $variantBuilder = new VariantBuilder;

        $extra = $build->getExtraOptions();

        if( ! file_exists('configure') ) {
            $this->logger->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== false or die('buildconf error');
        }

        // build configure args
        // XXX: support variants

        $builder = ProcessBuilder::create(array('./configure'));
        $builder->setEnv('CFLAGS', '-03');

        $prefix = $build->getInstallDirectory();
        $builder
            ->add("--prefix=" . $prefix)
            ->add("--with-config-file-path={$prefix}/etc")
            ->add("--with-config-file-scan-dir={$prefix}/var/db")
            ->add("--with-pear={$prefix}/lib/php");


        foreach ($variantBuilder->build($build) as $variantOption) {
            $builder->add($variantOption);
        }

        $this->logger->debug('Enabled variants: ' . join(', ',array_keys($build->getVariants())  ));
        $this->logger->debug('Disabled variants: ' . join(', ',array_keys($build->getDisabledVariants())  ));

        if ($patchFile = $this->options->patch) {
            // copy patch file to here
            $this->logger->info("===> Applying patch file from $patchFile ...");
            system("patch -p0 < $patchFile");
        }


        // let's apply patch for libphp{php version}.so (apxs)
        if( $build->isEnabledVariant('apxs2') ) {
            $apxs2Checker = new \PhpBrew\Tasks\Apxs2CheckTask($this->logger);
            $apxs2Checker->check($build);
            $apxs2Patch = new \PhpBrew\Tasks\Apxs2PatchTask($this->logger);
            $apxs2Patch->patch($build);
        }

        foreach ($extra as $a) {
            $builder->add($a);
        }

        $this->logger->info("===> Configuring {$build->version}...");

        if ($stdout = Config::getVersionBuildLogPath($build->name)) {
            // $builder->add("2&1 > $stdout");

            echo "\n\n";
            echo "Use tail command to see what's going on:\n";
            echo "   $ tail -f {$stdout}\n\n\n";
        }

        if ($nice = $this->options->nice) {
            $builder->add("nice -n $nice");
        }

        $process = $builder->getProcess();
        $this->logger->debug($process->getCommandLine());

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

        // Then patch Makefile for PHP 5.3.x on 64bit system.
        if( Utils::support_64bit() && $build->compareVersion('5.4') == -1 ) {
            $this->logger->info("===> Applying patch file for php5.3.x on 64bit machine.");
            system('sed -i \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');
            system('sed -i \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
        }
    }

    public function build()
    {

    }

    public function test()
    {

    }

    public function install()
    {

    }
}
