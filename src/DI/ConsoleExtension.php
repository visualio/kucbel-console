<?php

namespace Kucbel\Console\DI;

use Kucbel\Console;
use Kucbel\Scalar\Input\ContainerInput;
use Kucbel\Scalar\Input\ExtensionInput;
use Kucbel\Scalar\Validator\ValidatorException;
use Nette\Caching\IStorage;
use Nette\Caching\Storages\MemoryStorage;
use Nette\DI\CompilerExtension;
use Nette\Loaders\RobotLoader;
use Nette\Utils\Strings;
use ReflectionClass;
use Symfony\Component\Console as Symfony;
use Tracy\ILogger;

class ConsoleExtension extends CompilerExtension
{
	/**
	 * Config
	 */
	function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$param = $this->getExtensionParams();

		if( $param['cache'] ) {
			$storage = IStorage::class;
		} else {
			$builder->addDefinition( $storage = $this->prefix('storage'))
				->setType( MemoryStorage::class )
				->setAutowired( false );
		}

		$builder->addDefinition( $loader = $this->prefix('command.factory'))
			->setType( Console\Command\CommandFactory::class )
			->setArguments(['@container', "@$storage"]);

		$logger = ILogger::class;

		$param = $this->getApplicationParams();

		$builder->addDefinition( $console = $this->prefix('application'))
			->setType( Console\Application::class )
			->setArguments(["@$logger", $param['name'], $param['ver'] ])
			->addSetup('setCommandLoader', ["@$loader"])
			->addSetup('setCatchExceptions', [ $param['catch'] ])
			->addSetup('setAutoExit', [ $param['exit'] ]);

		$builder->addAlias('console', $console );

		$param = $this->getHttpParams();

		if( $param['active'] ) {
			$builder->addDefinition( $request = $this->prefix('http.factory'))
				->setType( Console\Http\RequestFactory::class )
				->setArguments([ $param['host'], $param['method'], $param['remote'] ]);

			$builder->getDefinition('http.request')
				->setFactory("@$request::create");
		}
	}

	/**
	 * Compile
	 *
	 * @throws
	 */
	function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$register =
		$services = [];

		$param = $this->getCommandParams();

		if( $param ) {
			$robot = new RobotLoader;
			$robot->addDirectory( $param );
			$robot->rebuild();

			foreach( $robot->getIndexedClasses() as $type => $path ) {
				$class = new ReflectionClass( $type );

				if( $class->isSubclassOf( Symfony\Command\Command::class ) and $class->isInstantiable() ) {
					$register[ $type ] = false;
				}
			}
		}

		$commands = $builder->findByType( Symfony\Command\Command::class );

		foreach( $commands as $name => $command ) {
			$services[] = $name;

			if( $type = $command->getType() ) {
				$register[ $type ] = true;
			}
		}

		$counter = 0;
		$spacer = strlen( count( $register ));

		foreach( $register as $type => $exist ) {
			if( $exist ) {
				continue;
			}

			$suffix = Strings::padLeft( ++$counter, $spacer, '0');

			$builder->addDefinition( $name = $this->prefix("command.$suffix"))
				->setType( $type )
				->setInject();

			$services[] = $name;
		}

		if( $services ) {
			$factory = $builder->getDefinition( $this->prefix('command.factory'));
			$factory->addSetup('add', $services );
		}

		$helpers = $builder->findByType( Symfony\Helper\HelperSet::class );

		if( $helpers ) {
			$console = $builder->getDefinition( $this->prefix('application'));

			foreach( $helpers as $name => $helper ) {
				$console->addSetup('addHelperSet', ["@$name"]);
			}
		}

		$param = $this->getAliasParams();

		if( $param ) {
			$commands = $builder->findByType( Symfony\Command\Command::class );

			foreach( $commands as $command ) {
				if( $type = $command->getType() ) {
					foreach( $param as [ $name, $regex, $class ]) {
						if(( $regex and Strings::match( $type, $regex )) or ( $class and is_a( $type, $class, true ))) {
							$command->addSetup("?->setName(\"{$name}:{?->getName()}\")", ['@self', '@self']);
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	private function getExtensionParams() : array
	{
		$input = new ContainerInput( $this->getContainerBuilder() );

		try {
			$cache = $input->create('productionMode')
				->bool()
				->fetch();
		} catch( ValidatorException $ex ) {
			$cache = true;
		}

		$input = new ExtensionInput( $this, 'command');

		$param['cache'] = $input->create('cache')
			->optional( $cache )
			->bool()
			->fetch();

		return $param;
	}

	/**
	 * @return array
	 */
	private function getCommandParams() : ?array
	{
		$input = new ExtensionInput( $this,  'command');

		return $input->create('scan')
			->optional()
			->array()
			->string()
			->dir( true )
			->fetch();
	}

	/**
	 * @return array
	 */
	private function getApplicationParams() : array
	{
		$input = new ExtensionInput( $this, 'application');

		$param['name'] = $input->create('name')
			->optional('C.P.A.M. - Console Peasant Assistance Module')
			->string()
			->fetch();

		$param['ver'] = $input->create('version')
			->optional('1.3.0')
			->string()
			->fetch();

		$param['exit'] = $input->create('exit')
			->optional( true )
			->bool()
			->fetch();

		$param['catch'] = $input->create('catch')
			->optional( true )
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

	/**
	 * @return array
	 */
	private function getAliasParams() : ?array
	{
		$quote = preg_quote('`~!@%&;/', '~');
		$param = null;

		$input = new ExtensionInput( $this );

		$names = $input->create('alias')
			->optional()
			->array( true )
			->string()
			->match('~^[a-z0-9]+(-[a-z0-9]+)*(:[a-z0-9]+(-[a-z0-9]+)*)*$~i')
			->fetch();

		if( $names ) {
			foreach( $names as $name ) {
				$aliases = $input->create("alias.{$name}")->array()->string();

				foreach( $aliases as $alias ) {
					try {
						$regex = $alias->match("~^([{$quote}]).+\\1[a-z]*$~i")->fetch();
						$class = null;
					} catch( ValidatorException $ex ) {
						$class = $alias->impl( Symfony\Command\Command::class, true )->fetch();
						$regex = null;
					}

					$param[] = [ $name, $regex, $class ];
				}
			}
		}

		$input->validate();

		return $param;
	}
}