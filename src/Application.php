<?php

namespace Kucbel\Console;

use Exception;
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
	 * @param HelperSet ...$groups
	 */
	function addHelperSets( HelperSet ...$groups )
	{
		$default = $this->getHelperSet();

		foreach( $groups as $helpers ) {
			foreach( $helpers as $alias => $helper ) {
				$default->set( $helper, $alias );
			}
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
		$this->logger->log( $throwable, 'console');

		parent::renderThrowable( $throwable, $output );
	}
}
