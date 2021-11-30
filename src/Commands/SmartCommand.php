<?php

namespace Kucbel\Console\Commands;

use Nette\NotImplementedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SmartCommand extends Command
{
	/**
	 * SmartCommand constructor.
	 */
	function __construct()
	{
		parent::__construct();

		$this->setCode( function( $input, $output ) {
			return $this->execute( $input, $output );
		});
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return mixed
	 */
	protected function execute( InputInterface $input, OutputInterface $output )
	{
		throw new NotImplementedException;
	}
}