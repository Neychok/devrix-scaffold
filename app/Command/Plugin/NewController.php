<?php

namespace App\Command\Plugin;

use Minicli\Command\CommandController;

use Minicli\Input;

class NewController extends CommandController
{
    public function handle(): void
    {
        $printer = $this->getPrinter();
        $param = $this->getParams();

        exec('git clone https://github.com/DevriX/dx-plugin-boilerplate');

    }
}