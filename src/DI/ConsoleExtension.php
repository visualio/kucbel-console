<?php

namespace Kucbel\Console\DI;

use Kucbel\Console;
use Kucbel\Scalar\Input\ExtensionInput;
use Kucbel\Scalar\Validator\ValidatorException;
use Nette\Caching;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Loaders\RobotLoader;
use Nette\Utils\Strings;
use ReflectionClass;
use Symfony\Component\Console as Symfony;
use Tracy;

class ConsoleExtension extends CompilerExtension
{
	/**
	 * @var ServiceDefinition
	 */
	private $command;

	/**
	 * @var ServiceDefinition
	 */
	private $console;

	/**
	 * @var ServiceDefinition | null
	 */
	private $request;

	/**
	 * Config
	 */
	function loadConfiguration()
	{
		$storage = Caching\Storage::class;

		$config = $this->getExtensionParams();
		$builder = $this->getContainerBuilder();

		if( !$config['cache'] ) {
			$builder->addDefinition( $storage = $this->prefix('storage'))
				->setType( Caching\Storages\MemoryStorage::class )
				->setAutowired( false );
		}

		$this->command = $builder->addDefinition( $command = $this->prefix('command.factory'))
			->setType( Console\Commands\CommandFactory::class )
			->setArguments(['@container', "@$storage"]);

		$config = $this->getApplicationParams();

		$this->console = $builder->addDefinition( $console = $this->prefix('application'))
			->setType( Console\Application::class )
			->setArguments([ $config['name'], $config['ver'] ])
			->addSetup('setCommandLoader', ["@$command"])
			->addSetup('setCatchExceptions', [ $config['catch'] ])
			->addSetup('setAutoExit', [ $config['exit'] ])
			->addTag('nette.inject');

		$builder->addAlias('console', $console );

		$config = $this->getRequestParams();

		if( $config['active'] ) {
			$this->request = $builder->addDefinition( $request = $this->prefix('request.factory'))
				->setType( Console\Http\RequestFactory::class )
				->setArguments([ $config['server'], $config['script'], $config['method'], $config['remote'] ]);

			$service = $builder->getDefinition('http.request');

			if( $service instanceof ServiceDefinition ) {
				$service->setFactory("@$request::create");
			}
		}

		$this->compiler->addExportedType( Symfony\Application::class );
	}

	/**
	 * Compile
	 *
	 * @throws
	 */
	function beforeCompile()
	{
		$this->addCommands();
		$this->addHelperSets();
		$this->addHelpers();
		$this->addAliases();
	}

	/**
	 * @param array $types
	 * @throws
	 */
	private function findCommandTypes( array &$types = null ) : void
	{
		$types = (array) $types;

		$config = $this->getSearchParams();

		if( !$config ) {
			return;
		}

		$robot = new RobotLoader;
		$robot->addDirectory( ...$config );
		$robot->rebuild();

		foreach( $robot->getIndexedClasses() as $type => $path ) {
			$class = new ReflectionClass( $type );

			if( $class->isSubclassOf( Symfony\Command\Command::class ) and $class->isInstantiable() ) {
				$types[ $type ] = false;
			}
		}
	}

	/**
	 * @param array $types
	 * @param array $names
	 */
	private function findCommandNames( array &$types, array &$names = null ) : void
	{
		$names = (array) $names;

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Command\Command::class );

		foreach( $services as $name => $service ) {
			$names[] = $name;

			if( $type = $service->getType() ) {
				$types[ $type ] = true;
			}
		}
	}

	/**
	 * @return void
	 * @throws
	 */
	private function addCommands() : void
	{
		$this->findCommandTypes( $types );
		$this->findCommandNames( $types, $names );

		$builder = $this->getContainerBuilder();

		$count = 0;
		$space = strlen( count( $types ));

		foreach( $types as $type => $exist ) {
			if( $exist ) {
				continue;
			}

			$number = Strings::padLeft( ++$count, $space, '0');

			$builder->addDefinition( $names[] = $this->prefix("command.$number"))
				->setType( $type )
				->setAutowired( false )
				->addTag('nette.inject');
		}

		if( $names ) {
			$this->command->addSetup('add', $names );
		}
	}

	/**
	 * @return void
	 */
	private function addHelperSets() : void
	{
		$names = null;

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Helper\HelperSet::class );

		foreach( $services as $name => $service ) {
			if( $service->getAutowired() ) {
				$names[] = "@$name";
			}
		}

		if( $names ) {
			$this->console->addSetup('addHelperSets', $names );
		}
	}

	/**
	 * @return void
	 */
	private function addHelpers() : void
	{
		$names = null;

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Helper\Helper::class );

		foreach( $services as $name => $service ) {
			if( $service->getAutowired() ) {
				$names[] = "@$name";
			}
		}

		if( $names ) {
			$this->console->addSetup('addHelpers', $names );
		}
	}

	/**
	 * @return void
	 */
	private function addAliases() : void
	{
		$config = $this->getAliasParams();

		if( !$config ) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Command\Command::class );

		foreach( $services as $service ) {
			$type = $service->getType();

			if( !$type or !$service instanceof ServiceDefinition ) {
				continue;
			}

			foreach( $config as [ $name, $regex, $class ]) {
				if( $regex ) {
					$match = Strings::match( $type, $regex ) ? true : false;
				} elseif( $class ) {
					$match = is_a( $type, $class, true );
				} else {
					$match = false;
				}

				if( $match ) {
					$service->addSetup("?->setName(\"{$name}:{?->getName()}\")", ['@self', '@self']);

					break;
				}
			}
		}
	}

	/**
	 * @return array
	 */
	private function getExtensionParams() : array
	{
		$input = new ExtensionInput( $this, 'command');

		$param['cache'] = $input->create('cache')
			->optional( false )
			->bool()
			->fetch();

		return $param;
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
			->char( 1, 100 )
			->fetch();

		$param['ver'] = $input->create('version')
			->optional('2.0.0')
			->string()
			->match('~^[0-9]+([.][0-9]+){0,3}$~')
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
	private function getRequestParams() : array
	{
		$input = new ExtensionInput( $this, 'request');

		$param['server'] = $input->create('server')
			->optional('http://localhost')
			->string()
			->url()
			->fetch();

		$param['script'] = $input->create('script')
			->optional()
			->string()
			->fetch();

		$param['method'] = $input->create('method')
			->optional('GET')
			->string()
			->equal('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH', 'OPTIONS')
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
	 * @return array | null
	 */
	private function getSearchParams() : ?array
	{
		$input = new ExtensionInput( $this, 'command');

		return $input->create('scan')
			->optional()
			->array()
			->string()
			->folder()
			->fetch();
	}

	/**
	 * @return array | null
	 */
	private function getAliasParams() : ?array
	{
		$quote = preg_quote('`~!@%&;/', '~');
		$param = null;

		$input = new ExtensionInput( $this );

		$names = $input->create('alias')
			->optional()
			->index()
			->string()
			->match('~^[a-z0-9]+(-[a-z0-9]+)*(:[a-z0-9]+(-[a-z0-9]+)*)*$~i')
			->fetch();

		foreach( $names ?? [] as $name ) {
			$aliases = $input->create("alias.{$name}")
				->array()
				->string();

			foreach( $aliases as $alias ) {
				try {
					$regex = $alias->match("~^([{$quote}]).+\\1[a-z]*$~i")->fetch();
					$class = null;
				} catch( ValidatorException $ex ) {
					$class = $alias->class( Symfony\Command\Command::class )->fetch();
					$regex = null;
				}

				$param[] = [ $name, $regex, $class ];
			}
		}

		$input->match();

		return $param;
	}
}
