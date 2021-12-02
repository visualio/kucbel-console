<?php

namespace Kucbel\Console\Output;

use Nette\SmartObject;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
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
	function write( $messages, bool $newline = false, int $options = 0 )
	{
		$this->output->write( $messages, $newline, $options );
	}

	/**
	 * @inheritdoc
	 */
	function writeln( $messages, int $options = 0 )
	{
		$this->output->writeln( $messages, $options );
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
	 * @inheritdoc
	 */
	function setDecorated( bool $decorated )
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
}