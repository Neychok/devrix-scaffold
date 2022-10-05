<?php

namespace App\Command\Plugin;

use Minicli\App;
use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()->info('Run dx-scaffold plugin help for usage help.');
    }
}