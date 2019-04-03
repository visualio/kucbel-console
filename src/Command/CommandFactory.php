<?php

namespace Kucbel\Console\Command;

use Nette\DI\Container;
use Nette\SmartObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandFactory implements CommandLoaderInterface
{
	use SmartObject;

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var array
	 */
	private $services;

	/**
	 * CommandFactory constructor.
	 *
	 * @param Container $container
	 * @param array $services
	 */
	function __construct( Container $container, array $services )
	{
		$this->container = $container;
		$this->services = $services;
	}

	/**
	 * @param string $name
	 * @return Command
	 * @throws CommandNotFoundException
	 */
	function get( $name ) : Command
	{
		$service = $this->services[ $name ] ?? null;

		if( $service === null ) {
			throw new CommandNotFoundException("Command '$name' does not exist.");
		}

		/** @var Command $command */
		$command = $this->container->getService( $service );

		return $command;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function has( $name ) : bool
	{
		return isset( $this->services[ $name ] );
	}

	/**
	 * @return string[]
	 */
	function getNames() : array
	{
		return array_keys( $this->services );
	}
}