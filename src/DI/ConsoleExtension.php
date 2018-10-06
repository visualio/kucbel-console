<?php

namespace Kucbel\Console\DI;

use Kucbel\Console;
use Kucbel\Scalar\Input\ExtensionInput;
use Nette\DI\CompilerExtension;
use Nette\InvalidStateException;
use Nette\Loaders\RobotLoader;
use Nette\Utils\Strings;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console as Symfony;
use Throwable;

class ConsoleExtension extends CompilerExtension
{
	/**
	 * @var array | null
	 */
	private $commands;

	/**
	 * @var array | null
	 */
	private $services;

	/**
	 * Config
	 */
	function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition( $command = $this->prefix('command.factory'))
			->setType( Console\CommandFactory::class )
			->setArguments([[]])
			->setInject();

		$param = $this->getApplicationParams();

		$builder->addDefinition( $this->prefix('application'))
			->setType( Symfony\Application::class )
			->setArguments([ $param['name'], $param['ver'] ])
			->addSetup('setCommandLoader', ["@$command"])
			->addSetup('setCatchExceptions', [ $param['catch'] ])
			->addSetup('setAutoExit', [ $param['exit'] ]);

		$param = $this->getHttpParams();

		if( $param['active'] ) {
			$builder->addDefinition( $request = $this->prefix('request.factory'))
				->setType( Console\RequestFactory::class )
				->setArguments([ $param['host'], $param['method'], $param['remote'] ]);

			$builder->getDefinition('http.request')
				->setFactory("@$request::create");
		}
	}

	/**
	 * Compile
	 */
	function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$param = $this->getCommandParams();

		$number = 1;
		$commands = $services = [];

		if( $param['scan'] ) {
			$robot = new RobotLoader;
			$robot->addDirectory( $param['scan'] );
			$robot->rebuild();

			foreach( $robot->getIndexedClasses() as $type => $path ) {
				if( $this->isCommand( $type )) {
					$commands[ $type ] = true;
				}
			}
		}

		$definitions = $builder->findByType( Symfony\Command\Command::class );

		foreach( $definitions as $name => $definition ) {
			$type = $definition->getType();

			if( $type ) {
				$services[ $name ] = $type;
				$commands[ $type ] = false;
			} else {
				$this->commands[] = $name;
			}
		}

		foreach( $commands as $type => $register ) {
			if( !$register ) {
				continue;
			}

			$index = Strings::padLeft( $number++, 2, '0');

			$builder->addDefinition( $name = $this->prefix("command.$index"))
				->setType( $type )
				->setInject();

			$services[ $name ] = $type;
		}

		foreach( $services as $name => $type ) {
			$real = $this->getCommandName( $type );

			if( $real and ( $dupe = $this->services[ $real ] ?? null )) {
				throw new InvalidStateException("Duplicate name '$real' found in commands {$services[ $dupe ]} and $type.");
			}

			if( $real ) {
				$this->services[ $real ] = $name;
			} else {
				$this->commands[] = $name;
			}
		}

		if( $this->services ) {
			$factory = $builder->getDefinition( $this->prefix('command.factory'));
			$factory->setArguments([ $this->services ]);
		}

		if( $this->commands ) {
			$this->commands = array_map( function( $command ) { return "@$command"; }, $this->commands );

			$console = $builder->getDefinition( $this->prefix('application'));
			$console->addSetup('addCommands', [ $this->commands ]);
		}
	}

	/**
	 * @param string $type
	 * @return bool
	 */
	private function isCommand( string $type ) : bool
	{
		try {
			$class = new ReflectionClass( $type );

			return $class->isSubclassOf( Symfony\Command\Command::class ) and $class->isInstantiable();
		} catch( ReflectionException $ex ) {
			return false;
		}
	}

	/**
	 * @param string $type
	 * @return string | null
	 */
	private function getCommandName( string $type ) : ?string
	{
		try {
			$class = new ReflectionClass( $type );

			/** @var Symfony\Command\Command $command */
			$command = $class->newInstanceWithoutConstructor();
		} catch( ReflectionException $ex ) {
			return null;
		}

		try {
			$command->setDefinition( new Symfony\Input\InputDefinition );

			$method = $class->getMethod('configure');
			$method->setAccessible( true );
			$method->invoke( $command );

			return $command->getName();
		} catch( Throwable $ex ) {
			return null;
		}
	}

	/**
	 * @return array
	 */
	private function getCommandParams() : array
	{
		$input = new ExtensionInput( $this );

		$param['scan'] = $input->create('scan')
			->optional()
			->array()
			->string()
			->dir( true )
			->fetch();

		$input->validate();

		return $param;
	}

	/**
	 * @return array
	 */
	private function getApplicationParams() : array
	{
		$input = new ExtensionInput( $this, 'app');

		$param['name'] = $input->create('name')
			->optional('C.P.A.M. - Console Peasant Assistance Module')
			->string()
			->fetch();

		$param['ver'] = $input->create('version')
			->optional('UNKNOWN')
			->string()
			->fetch();

		$param['exit'] = $input->create('exit')
			->optional( false )
			->bool()
			->fetch();

		$param['catch'] = $input->create('catch')
			->optional( false )
			->bool()
			->fetch();

		return $param;
	}


	/**
	 * @return array
	 */
	private function getHttpParams() : array
	{
		$input = new ExtensionInput( $this, 'http');

		$param['host'] = $input->create('host')
			->optional('http://localhost')
			->string()
			->url()
			->fetch();

		$param['method'] = $input->create('method')
			->optional('GET')
			->string()
			->fetch();

		$param['remote'] = $input->create('remote')
			->optional('127.0.0.1')
			->string()
			->fetch();

		$param['active'] = $input->create('active')
			->optional( PHP_SAPI === 'cli')
			->bool()
			->fetch();

		return $param;
	}
}