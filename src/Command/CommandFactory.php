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
			throw new CommandNotFoundException("Command '$name' does not exist.");
		}

		$command = $this->container->getService( $service );

		if( !$command instanceof Command ) {
			throw new CommandNotFoundException("Command '$name' does not exist.");
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
	function build()
	{
		if( $this->build ) {
			$this->build = false;

			if( $this->waits ) {
				$hash = md5( json_encode( $this->waits ));

				$this->names = $this->cache->load( $hash, [ $this, 'index']);
			}
		}
	}

	/**
	 * @return array
	 * @internal
	 */
	function index() : array
	{
		$codes = [];

		if( $this->waits ) {
			foreach( $this->waits as $name ) {
				$command = $this->container->getService( $name );

				if( !$command instanceof Command ) {
					throw new InvalidStateException("Service '$name' must be a command.");
				}

				$code = $command->getName();
				$dupe = $codes[ $code ] ?? null;

				if( $dupe ) {
					throw new InvalidStateException("Duplicate command '$code' found in services '$dupe' and '$name'.");
				}

				$codes[ $code ] = $name;
			}
		}

		return $codes;
	}
}