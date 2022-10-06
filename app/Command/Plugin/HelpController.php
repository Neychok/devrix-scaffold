<?php

namespace App\Command\Plugin;

use Minicli\Command\CommandController;


class NewController extends CommandController
{
	public function handle(): void
	{
		$this->getPrinter()->info('Here we will have info about how to use this command.');
	}
}