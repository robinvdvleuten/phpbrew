<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use Exception;

class SwitchCommand extends \CLIFramework\Command
{
    public function brief() { return 'switch default php version.'; }

    public function execute($version = null)
    {
        throw new Exception("You should not see this, please check if phpbrew bashrc is sourced in your shell.");
    }
}
