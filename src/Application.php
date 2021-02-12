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
	 * @param HelperSet ...$sets
	 */
	function addHelperSets( HelperSet ...$sets )
	{
		$defaults = $this->getHelperSet();

		foreach( $sets as $set ) {
			foreach( $set as $alias => $helper ) {
				$defaults->set( $helper, $alias );
			}
		}
	}

	/**
	 * @param Helper ...$helpers
	 */
	function addHelpers( Helper ...$helpers )
	{
		$defaults = $this->getHelperSet();

		foreach( $helpers as $helper ) {
			$defaults->set( $helper );
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

	/**
	 * @inheritdoc
	 */
	function renderThrowable( Throwable $throwable, OutputInterface $output ) : void
	{
		$this->logger->log( $throwable, 'console');

		parent::renderThrowable( $throwable, $output );
	}
}
