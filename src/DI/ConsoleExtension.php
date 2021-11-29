<?php

namespace Kucbel\Console\DI;

use Composer\InstalledVersions;
use Kucbel\Console;
use Nette\Caching;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Loaders\RobotLoader;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
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
	 * @return Schema
	 */
	function getConfigSchema() : Schema
	{
		$quote = preg_quote('`~!@%&;/', '~');

		$cast = [];
		$cast['array'] = function( $value ) {
			return is_scalar( $value ) ? [ $value ] : $value;
		};

		$test = [];
		$test['address'] = [ Validators::class, 'isUrl'];
		$test['alias'] = function( string $value ) use( $quote ) {
			return class_exists( $value ) or Strings::match( $value, "~^([{$quote}]).+\\1[a-z]*$~i");
		};

		if( InstalledVersions::isInstalled('kucbel/console')) {
			$found = InstalledVersions::getVersion('kucbel/console');
		} else {
			$found = null;
		}

		return Expect::structure([
			'application' => Expect::structure([
				'name'		=> Expect::string('C.P.A.M. - Console Peasant Assistance Module'),
				'version'	=> Expect::string( $found ?? 'UNKNOWN'),
				'alias'		=> Expect::string('console')->nullable(),
				'logger'	=> Expect::anyOf( Expect::string(), Expect::bool() )->default( true ),
				'catch'		=> Expect::bool( true ),
				'exit'		=> Expect::bool( true ),
			]),

			'command' => Expect::structure([
				'inject'	=> Expect::bool( true ),
				'cache'		=> Expect::bool( false ),
			]),

			'request' => Expect::structure([
				'address'	=> Expect::string('http://localhost')->assert( $test['address'], "Request address must be a valid url."),
				'script'	=> Expect::string()->nullable(),
				'method'	=> Expect::string()->nullable(),
				'remote'	=> Expect::string('127.0.0.1')->nullable(),
				'active'	=> Expect::bool( PHP_SAPI === 'cli'),
			]),

			'search' => Expect::listOf(
				Expect::string()->assert('is_dir', "Command search folder must exist."))->before( $cast['array'] ),

			'alias' => Expect::arrayOf(
				Expect::listOf( Expect::string()->assert( $test['alias'], 'Command alias must be either class or regex.'))->before( $cast['array'] ),
				Expect::string() ),
		]);
	}

	/**
	 * Config
	 */
	function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		if( !$this->config->command->cache ) {
			$builder->addDefinition( $storage = $this->prefix('storage'))
				->setType( Caching\Storages\MemoryStorage::class )
				->setAutowired( false );
		} else {
			$storage = Caching\Storage::class;
		}

		$this->command = $builder->addDefinition( $command = $this->prefix('command.factory'))
			->setType( Console\Commands\CommandFactory::class )
			->setArguments(['@container', "@$storage"]);

		$this->console = $builder->addDefinition( $console = $this->prefix('application'))
			->setType( Console\Application::class )
			->setArguments([ $this->config->application->name, $this->config->application->version ])
			->addSetup('setCommandLoader', ["@$command"])
			->addSetup('setCatchExceptions', [ $this->config->application->catch ])
			->addSetup('setAutoExit', [ $this->config->application->exit ]);

		if( $level = $this->config->application->logger ) {
			$logger = Tracy\ILogger::class;

			$this->console->addSetup('setLogger', ["@$logger", is_string( $level ) ? $level : 'console']);
		}

		if( $this->config->application->alias ) {
			$builder->addAlias( $this->config->application->alias, $console );
		}

		if( $this->config->request->active ) {
			$this->request = $builder->addDefinition( $request = $this->prefix('request.factory'))
				->setType( Console\Http\RequestFactory::class )
				->setArguments([ $this->config->request->address, $this->config->request->script, $this->config->request->method, $this->config->request->remote ]);

			if( $builder->hasDefinition('http.request') and $service = $builder->getDefinition('http.request') and $service instanceof ServiceDefinition ) {
				$service->setFactory("@$request::create");
			}
		}
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
	 * @param array $classes
	 * @throws
	 */
	private function searchFolders( array &$classes ) : void
	{
		if( !$this->config->search ) {
			return;
		}

		$loader = new RobotLoader;
		$loader->addDirectory( ...$this->config->search );
		$loader->rebuild();

		foreach( $loader->getIndexedClasses() as $class => $source ) {
			$reflect = new ReflectionClass( $class );

			if( $reflect->isSubclassOf( Symfony\Command\Command::class ) and $reflect->isInstantiable() ) {
				$classes[ $class ] = false;
			}
		}
	}

	/**
	 * @param array $classes
	 * @param array $commands
	 */
	private function searchServices( array &$classes, array &$commands ) : void
	{
		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Command\Command::class );

		foreach( $services as $alias => $service ) {
			$commands[] = $alias;

			if( $class = $service->getType() ) {
				$classes[ $class ] = true;
			}
		}
	}

	/**
	 * @return void
	 * @throws
	 */
	private function addCommands() : void
	{
		$classes =
		$commands = [];

		$this->searchFolders( $classes );
		$this->searchServices( $classes, $commands );

		$builder = $this->getContainerBuilder();

		$count = 0;
		$space = strlen( count( $classes ));

		foreach( $classes as $class => $exist ) {
			if( $exist ) {
				continue;
			}

			$suffix = Strings::padLeft( ++$count, $space, '0');

			$service = $builder->addDefinition( $commands[] = "command.$suffix")
				->setType( $class )
				->setAutowired( false );

			if( $this->config->command->inject ) {
				$service->addTag('nette.inject');
			}
		}

		if( $commands ) {
			$this->command->addSetup('add', $commands );
		}
	}

	/**
	 * @return void
	 */
	private function addHelperSets() : void
	{
		$helpers = [];

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Helper\HelperSet::class );

		foreach( $services as $alias => $service ) {
			if( $service->getAutowired() ) {
				$helpers[] = "@$alias";
			}
		}

		if( $helpers ) {
			$this->console->addSetup('addHelperSets', $helpers );
		}
	}

	/**
	 * @return void
	 */
	private function addHelpers() : void
	{
		$helpers = [];

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Helper\Helper::class );

		foreach( $services as $alias => $service ) {
			if( $service->getAutowired() ) {
				$helpers[] = "@$alias";
			}
		}

		if( $helpers ) {
			$this->console->addSetup('addHelpers', $helpers );
		}
	}

	/**
	 * @return void
	 */
	private function addAliases() : void
	{
		if( !$this->config->alias ) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$services = $builder->findByType( Symfony\Command\Command::class );

		foreach( $services as $service ) {
			$class = $service->getType();

			if( !$class or !$service instanceof ServiceDefinition ) {
				continue;
			}

			foreach( $this->config->alias as $alias => $matches ) {
				$valid = false;

				foreach( $matches as $match ) {
					if( class_exists( $match )) {
						if( is_a( $class, $match, true )) {
							$valid = true;

							break;
						}
					} else {
						if( Strings::match( $class, $match )) {
							$valid = true;

							break;
						}
					}
				}

				if( !$valid ) {
					continue;
				}

				$alias = rtrim( $alias, ':');

				$service->addSetup("?->setName(\"{$alias}:{?->getName()}\")", ['@self', '@self']);
			}
		}
	}
}
