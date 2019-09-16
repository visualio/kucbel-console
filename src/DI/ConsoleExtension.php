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
		$param = $this->getExtensionParams();
		$builder = $this->getContainerBuilder();

		$logger = Tracy\ILogger::class;
		$storage = Caching\IStorage::class;

		$this->command = $builder->addDefinition( $command = $this->prefix('command.factory'))
			->setType( Console\Command\CommandFactory::class )
			->setArguments(['@container', $param['cache'] ? "@$storage" : null ]);

		$param = $this->getApplicationParams();

		$this->console = $builder->addDefinition( $console = $this->prefix('application'))
			->setType( Console\Application::class )
			->setArguments(["@$logger", $param['name'], $param['ver'] ])
			->addSetup('setCommandLoader', ["@$command"])
			->addSetup('setCatchExceptions', [ $param['catch'] ])
			->addSetup('setAutoExit', [ $param['exit'] ]);

		$builder->addAlias('console', $console );

		$param = $this->getRequestParams();

		if( $param['active'] ) {
			$this->request = $builder->addDefinition( $request = $this->prefix('request.factory'))
				->setType( Console\Http\RequestFactory::class )
				->setArguments([ $param['server'], $param['script'], $param['method'], $param['remote'] ]);

			/** @var ServiceDefinition $service */
			$service = $builder->getDefinition( 'http.request');
			$service->setFactory("@$request::create");
		}
	}

	/**
	 * Compile
	 *
	 * @throws
	 */
	function beforeCompile()
	{
		$types =
		$names = [];

		$param = $this->getCommandParams();
		$builder = $this->getContainerBuilder();

		if( $param ) {
			$robot = new RobotLoader;
			$robot->addDirectory( ...$param );
			$robot->rebuild();

			foreach( $robot->getIndexedClasses() as $type => $path ) {
				$class = new ReflectionClass( $type );

				if( $class->isSubclassOf( Symfony\Command\Command::class ) and $class->isInstantiable() ) {
					$types[ $type ] = true;
				}
			}
		}

		$services = $builder->findByType( Symfony\Command\Command::class );

		foreach( $services as $name => $service ) {
			$names[] = $name;

			if( $type = $service->getType() ) {
				$types[ $type ] = false;
			}
		}

		$count = 0;
		$space = strlen( count( $types ));

		foreach( $types as $type => $new ) {
			if( !$new ) {
				continue;
			}

			$number = Strings::padLeft( ++$count, $space, '0');

			$builder->addDefinition( $names[] = $this->prefix("command.$number"))
				->setType( $type )
				->addTag('nette.inject');
		}

		if( $names ) {
			$this->command->addSetup('add', $names );
		}

		$services = $builder->findByType( Symfony\Helper\HelperSet::class );

		if( $services ) {
			foreach( $services as $name => $service ) {
				$this->console->addSetup('addHelperSet', ["@$name"]);
			}
		}

		$param = $this->getAliasParams();

		if( $param ) {
			$services = $builder->findByType( Symfony\Command\Command::class );

			foreach( $services as $service ) {
				$type = $service->getType();

				if( !$type or !$service instanceof ServiceDefinition ) {
					continue;
				}

				foreach( $param as [ $name, $regex, $class ]) {
					if(( $regex and Strings::match( $type, $regex )) or ( $class and is_a( $type, $class, true ))) {
						$service->addSetup("?->setName(\"{$name}:{?->getName()}\")", ['@self', '@self']);

						break;
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
			->length( 100, 1 )
			->fetch();

		$param['ver'] = $input->create('version')
			->optional('2.0.0')
			->string()
			->match('~^[0-9]+([.][0-9]+)$~')
			->length( 100, 1 )
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
	private function getCommandParams() : ?array
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
