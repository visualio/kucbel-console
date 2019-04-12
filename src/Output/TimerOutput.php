<?php

namespace Kucbel\Console\Output;

use Nette\Utils\DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use Iterator;
use stdClass;

class TimerOutput extends OutputDecorator
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
	 * @var int
	 */
	private $from;

	/**
	 * @var bool
	 */
	private $write = true;

	/**
	 * TimeOutput constructor.
	 *
	 * @param OutputInterface $output
	 * @param string $date
	 * @param string $line
	 * @param int $from
	 */
	function __construct( OutputInterface $output, string $date = self::FORMAT_DATE, string $line = self::FORMAT_LINE, int $from = self::VERBOSITY_NORMAL )
	{
		parent::__construct( $output );

		$this->date = $date;
		$this->line = $line;
		$this->from = $from;
	}

	/**
	 * @inheritdoc
	 */
	function write( $messages, $newline = false, $options = self::OUTPUT_NORMAL )
	{
		if( $this->isTracking() ) {
			$messages = $this->format( $messages, $newline );
		}

		$this->output->write( $messages, $newline, $options );
	}

	/**
	 * @inheritdoc
	 */
	function writeln( $messages, $options = self::OUTPUT_NORMAL )
	{
		if( $this->isTracking() ) {
			$messages = $this->format( $messages, true );
		}

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
		if( $messages instanceof stdClass ) {
			$messages = (array) $messages;
		} elseif( $messages instanceof Iterator ) {
			$messages = iterator_to_array( $messages );
		} elseif( is_scalar( $messages )) {
			$messages = [ $messages ];
		} elseif( !is_array( $messages )) {
			$messages = (string) $messages;
		}

		$current = $this->stamp();

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
	 */
	protected function stamp() : string
	{
		return ( new DateTime )->format( $this->date );
	}

	/**
	 * @param int $from
	 */
	function setTracking( int $from )
	{
		$this->from = $from;
	}

	/**
	 * @return bool
	 */
	function isTracking()
	{
		return $this->output->getVerbosity() >= $this->from;
	}
}