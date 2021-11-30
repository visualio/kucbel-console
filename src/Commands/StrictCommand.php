<?php

namespace Kucbel\Console\Commands;

use Nette\NotImplementedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class StrictCommand extends Command
{
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) : int
	{
		throw new NotImplementedException;
	}
}