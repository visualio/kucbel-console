<?php

namespace Kucbel\Console\Output;

use Nette\InvalidStateException;
use Nette\SmartObject;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TimerOutput implements ConsoleOutputInterface
{
	use SmartObject;

	/**
	 * @var OutputInterface
	 */
	private $output;

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var int
	 */
	private $level;

	/**
	 * @var bool
	 */
	private $write = true;

	/**
	 * TimedOutput constructor.
	 *
	 * @param OutputInterface $output
	 * @param string $format
	 * @param int $level
	 */
	function __construct( OutputInterface $output, string $format = 'H:i:s.u  ', int $level = self::VERBOSITY_NORMAL )
	{
		$this->output = $output;
		$this->format = $format;
		$this->level = $level;
	}

	/**
	 * @inheritdoc
	 */
	function newLine( $count = 1 )
	{
		$this->output->write( str_repeat( PHP_EOL, $count ));
	}

	/**
	 * @param int $max
	 *
	 * @return ProgressBar
	 */
	function createProgressBar( $max = 0 )
	{
		return new ProgressBar( $this->output, $max );
	}

	/**
	 * @inheritdoc
	 */
	function write( $messages, $newline = false, $type = self::OUTPUT_NORMAL )
	{
		if( $this->isTracking() and $this->write ) {
			$this->output->write(( new DateTime )->format( $this->format ));
		}

		$this->output->write( $messages, $newline, $type );

		if( $newline ) {
			$this->write = true;
		} else {
			$this->write = false;
		}
	}

	/**
	 * @inheritdoc
	 */
	function writeln( $messages, $type = self::OUTPUT_NORMAL )
	{
		if( $this->isTracking() and $this->write ) {
			$this->output->write(( new DateTime )->format( $this->format ));
		}

		$this->output->writeln( $messages, $type );

		$this->write = true;
	}

	/**
	 * @inheritdoc
	 */
	function setVerbosity( $level )
	{
		$this->output->setVerbosity( $level );
	}

	/**
	 * @inheritdoc
	 */
	function getVerbosity()
	{
		return $this->output->getVerbosity();
	}

	/**
	 * @inheritdoc
	 */
	function setDecorated( $decorated )
	{
		$this->output->setDecorated( $decorated );
	}

	/**
	 * @inheritdoc
	 */
	function isDecorated()
	{
		return $this->output->isDecorated();
	}

	/**
	 * @inheritdoc
	 */
	function setFormatter( OutputFormatterInterface $formatter )
	{
		$this->output->setFormatter($formatter);
	}

	/**
	 * @inheritdoc
	 */
	function getFormatter()
	{
		return $this->output->getFormatter();
	}

	/**
	 * @inheritdoc
	 */
	function isQuiet()
	{
		return $this->output->isQuiet();
	}

	/**
	 * @inheritdoc
	 */
	function isVerbose()
	{
		return $this->output->isVerbose();
	}

	/**
	 * @inheritdoc
	 */
	function isVeryVerbose()
	{
		return $this->output->isVeryVerbose();
	}

	/**
	 * @inheritdoc
	 */
	function isDebug()
	{
		return $this->output->isDebug();
	}

	/**
	 * @param int $level
	 */
	function setTracking( int $level )
	{
		$this->level = $level;
	}

	/**
	 * @return bool
	 */
	function isTracking()
	{
		return $this->output->getVerbosity() >= $this->level;
	}

	/**
	 * @inheritdoc
	 */
	function getErrorOutput() : OutputInterface
	{
		if( $this->output instanceof ConsoleOutputInterface ) {
			return $this->output->getErrorOutput();
		} else {
			return $this->output;
		}
	}

	/**
	 * @inheritdoc
	 */
	function setErrorOutput( OutputInterface $error )
	{
		if( $this->output instanceof ConsoleOutputInterface ) {
			$this->output->setErrorOutput( $error );
		} else {
			throw new InvalidStateException("Output doesn't implement console interface.");
		}
	}

	/**
	 * @inheritdoc
	 */
	function section() : ConsoleSectionOutput
	{
		if( $this->output instanceof ConsoleOutputInterface ) {
			return $this->output->section();
		} else {
			throw new InvalidStateException("Output doesn't implement console interface.");
		}
	}
}