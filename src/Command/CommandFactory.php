<?php

namespace Kucbel\Console\Command;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
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
	 * @var string[] | null
	 */
	private $waits;

	/**
	 * @var string[] | null
	 */
	private $names;

	/**
	 * @var bool
	 */
	private $build = false;

	/**
	 * CommandFactory constructor.
	 *
	 * @param Container $container
	 * @param IStorage $storage
	 */
	function __construct( Container $container, IStorage $storage )
	{
		$this->container = $container;
		$this->cache = new Cache( $storage, 'CommandFactory');
	}

	/**
	 * @param string ...$names
	 */
	function add( string ...$names )
	{
		if( !$names ) {
			throw new InvalidArgumentException;
		}

		foreach( $names as $name ) {
			$this->waits[] = $name;
		}

		$this->build = true;
	}

	/**
	 * @param string $name
	 * @return Command
	 * @throws CommandNotFoundException
	 */
	function get( $name ) : Command
	{
		$this->build();

		$service = $this->names[ $name ] ?? null;

		if( !$service ) {
			throw new CommandNotFoundException("Command '$name' doesn't exist.");
		}

		$command = $this->container->getService( $service );

		if( !$command instanceof Command ) {
			throw new CommandNotFoundException("Command '$name' doesn't exist.");
		}

		return $command;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function has( $name ) : bool
	{
		$this->build();

		return isset( $this->names[ $name ] );
	}

	/**
	 * @return string[]
	 */
	function getNames() : array
	{
		$this->build();

		return array_keys( $this->names );
	}

	/**
	 * @internal
	 */
	function build() : void
	{
		if( $this->build ) {
			$this->build = false;
			$this->names = $this->cache->load( implode(', ', $this->waits ), [ $this, 'index']);
		}
	}

	/**
	 * @return array
	 * @internal
	 */
	function index() : array
	{
		$names = [];

		foreach( $this->waits as $wait ) {
			$command = $this->container->getService( $wait );

			if( !$command instanceof Command ) {
				throw new InvalidStateException("Service '$wait' must be a command.");
			}

			$name = $command->getName();

			if( !$name ) {
				throw new InvalidStateException("Service '$wait' doesn't have a command name.");
			}

			$dupe = $names[ $name ] ?? null;

			if( $dupe ) {
				throw new InvalidStateException("Duplicate command '$name' found in services '$dupe' and '$wait'.");
			}

			$names[ $name ] = $wait;
		}

		return $names;
	}
}
