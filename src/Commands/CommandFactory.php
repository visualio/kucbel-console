<?php

namespace Kucbel\Console\Commands;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\DI\Container;
use Nette\InvalidStateException;
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
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var bool
	 */
	private $rebuild = false;

	/**
	 * @var string[]
	 */
	private $commands = [];

	/**
	 * @var string[]
	 */
	private $services = [];

	/**
	 * CommandFactory constructor.
	 *
	 * @param Container $container
	 * @param Storage $storage
	 */
	function __construct( Container $container, Storage $storage )
	{
		$this->container = $container;
		$this->cache = new Cache( $storage, 'CommandFactory');
	}

	/**
	 * @param string $command
	 * @param string ...$commands
	 */
	function add( string $command, string ...$commands )
	{
		$commands = [ $command, ...$commands ];

		foreach( $commands as $command ) {
			$this->commands[] = $command;
		}

		$this->rebuild = true;
	}

	/**
	 * @param string $name
	 * @return Command
	 * @throws CommandNotFoundException
	 */
	function get( string $name ) : Command
	{
		$this->build();

		$service = $this->services[ $name ] ?? null;

		if( !$service ) {
			throw new CommandNotFoundException("Command \"{$name}\" doesn't exist.");
		}

		return $this->container->getService( $service );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function has( string $name ) : bool
	{
		$this->build();

		return isset( $this->services[ $name ] );
	}

	/**
	 * @return string[]
	 */
	function getNames() : array
	{
		$this->build();

		return array_keys( $this->services );
	}

	/**
	 * @internal
	 */
	function build() : void
	{
		if( $this->rebuild ) {
			$this->rebuild = false;
			$this->services = $this->cache->load( implode(', ', $this->commands ), [ $this, 'index']);
		}
	}

	/**
	 * @return array
	 * @internal
	 */
	function index() : array
	{
		$services = [];

		foreach( $this->commands as $command ) {
			$service = $this->container->getService( $command );

			if( !$service instanceof Command ) {
				$reject = get_class( $service );

				throw new InvalidStateException("Service {$reject} isn't command.");
			}

			$origin = $service->getName();

			if( !$origin ) {
				$reject = get_class( $service );

				throw new InvalidStateException("Command {$reject} doesn't have a name.");
			}

			if( isset( $services[ $origin ] )) {
				$reject = [
					get_class( $service ),
					get_class( $this->container->getService( $services[ $origin ] )),
				];

				throw new InvalidStateException("Commands {$reject[0]} and {$reject[1]} have the same name.");
			}

			$services[ $origin ] = $command;
		}

		return $services;
	}
}
