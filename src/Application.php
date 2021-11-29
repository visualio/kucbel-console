<?php

namespace Kucbel\Console;

use Symfony\Component\Console as Symfony;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tracy\ILogger;

class Application extends Symfony\Application
{
	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $level;

	/**
	 * @param ILogger $logger
	 * @param string $level
	 */
	function setLogger( ILogger $logger, string $level = ILogger::EXCEPTION ) : void
	{
		$this->logger = $logger;
		$this->level = $level;
	}

	/**
	 * @param HelperSet ...$helpers
	 */
	function addHelperSets( HelperSet ...$helpers )
	{
		foreach( $helpers as $helper ) {
			$this->addHelpers( ...iterator_to_array( $helper ));
		}
	}

	/**
	 * @param Helper ...$helpers
	 */
	function addHelpers( Helper ...$helpers )
	{
		$default = $this->getHelperSet();

		foreach( $helpers as $helper ) {
			$default->set( $helper );
		}
	}

	/**
	 * @inheritdoc
	 */
	function renderThrowable( Throwable $throwable, OutputInterface $output ) : void
	{
		if( $this->logger ) {
			$this->logger->log( $throwable, $this->level );
		}

		parent::renderThrowable( $throwable, $output );
	}
}
