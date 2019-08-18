<?php

namespace Kucbel\Console\Output;

use Iterator;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Output\OutputInterface;

class TimeStampOutput extends OutputDecorator
{
	const
		FORMAT_DATE = 'H:i:s.u',
		FORMAT_LINE = '%s  %s';

	/**
	 * @var string
	 */
	private $date;

	/**
	 * @var string
	 */
	private $line;

	/**
	 * @var bool
	 */
	private $write = true;

	/**
	 * TimeStampOutput constructor.
	 *
	 * @param OutputInterface $output
	 * @param string $date
	 * @param string $line
	 */
	function __construct( OutputInterface $output, string $date = self::FORMAT_DATE, string $line = self::FORMAT_LINE )
	{
		parent::__construct( $output );

		$this->date = $date;
		$this->line = $line;
	}

	/**
	 * @inheritdoc
	 */
	function write( $messages, $newline = false, $options = self::OUTPUT_NORMAL )
	{
		$messages = $this->format( $messages, $newline );

		$this->output->write( $messages, $newline, $options );
	}

	/**
	 * @inheritdoc
	 */
	function writeln( $messages, $options = self::OUTPUT_NORMAL )
	{
		$messages = $this->format( $messages, true );

		$this->output->writeln( $messages, $options );

		$this->write = true;
	}

	/**
	 * @param mixed $messages
	 * @param bool $newline
	 * @return array
	 */
	protected function format( $messages, $newline )
	{
		if( $messages instanceof Iterator ) {
			$messages = iterator_to_array( $messages );
		} elseif( !is_array( $messages )) {
			$messages = (array) $messages;
		}

		$current = $this->getTimeStamp();

		foreach( $messages as $i => $message ) {
			if( $this->write ) {
				$messages[ $i ] = sprintf( $this->line, $current, $message );
			}

			if( $newline ) {
				$this->write = true;
			} else {
				$this->write = false;
			}
		}

		return $messages;
	}

	/**
	 * @return string
	 * @throws
	 */
	function getTimeStamp() : string
	{
		return ( new DateTime )->format( $this->date );
	}
}
