<?php

namespace Kucbel\Console\Output;

use Nette\SmartObject;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class OutputDecorator implements OutputInterface
{
	use SmartObject;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * OutputDecorator constructor.
	 *
	 * @param OutputInterface $output
	 */
	function __construct( OutputInterface $output )
	{
		$this->output = $output;
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
		$this->output->write( $messages, $newline, $type );
	}

	/**
	 * @inheritdoc
	 */
	function writeln( $messages, $type = self::OUTPUT_NORMAL )
	{
		$this->output->writeln( $messages, $type );
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
}
