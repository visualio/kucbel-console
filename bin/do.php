<?php

use Nette\DI\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Tracy\ILogger;

/** @var Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';

/** @var Application $application */
$application = $container->getByType( Application::class );

try {
	$input = new ArgvInput;
	$output = new ConsoleOutput;

	$application->run( $input, $output );
} catch( Throwable $exception ) {
	try {
		/** @var ILogger $logger */
		$logger = $container->getByType( ILogger::class );
		$logger->log( $exception, 'console');
	} catch( Throwable $exception ) {
	}

	if( !$exception instanceof Exception ) {
		$exception = new FatalThrowableError( $exception );
	}

	$application->renderException( $exception, $output->getErrorOutput() );
}