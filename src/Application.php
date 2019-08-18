<?php

namespace Kucbel\Console;

use Exception;
use Symfony\Component\Console as Symfony;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\ILogger;

class Application extends Symfony\Application
{
	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Application constructor.
	 *
	 * @param ILogger $logger
	 * @param string $name
	 * @param string $version
	 */
	function __construct( ILogger $logger, string $name = 'UNKNOWN', string $version = 'UNKNOWN')
	{
		parent::__construct( $name, $version );

		$this->logger = $logger;
	}

	/**
	 * @param HelperSet $helpers
	 */
	function addHelperSet( HelperSet $helpers )
	{
		$defaults = $this->getHelperSet();

		foreach( $helpers as $alias => $helper ) {
			$defaults->set( $helper, $alias );
		}
	}

	/**
	 * @inheritdoc
	 */
	function renderException( Exception $exception, OutputInterface $output )
	{
		$this->logger->log( $exception, 'console');

		parent::renderException( $exception, $output );
	}
}
