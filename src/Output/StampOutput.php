<?php

namespace Kucbel\Console\Output;

class StampOutput extends OutputDecorator
{
	/**
	 * @var string
	 */
	protected $date = 'H:i:s';

	/**
	 * @var string
	 */
	protected $line = '%s  %s';

	/**
	 * @param string $date
	 */
	function setDateFormat( string $date ) : void
	{
		$this->date = $date;
	}

	/**
	 * @param string $line
	 */
	function setLineFormat( string $line ) : void
	{
		$this->line = $line;
	}

	/**
	 * @param iterable|string $messages
	 * @param bool $newline
	 * @param int $options
	 */
	function stamp( iterable | string $messages, bool $newline = false, int $options = 0 ) : void
	{
		$this->output->write( $this->iterate( $messages, $newline ), $options );
	}

	/**
	 * @param iterable|string $messages
	 * @param int $options
	 */
	function stampln( iterable | string $messages, int $options = 0 ) : void
	{
		$this->output->writeln( $this->iterate( $messages, true ), $options );
	}

	/**
	 * @param iterable | string $messages
	 * @param bool $newline
	 * @return array
	 */
	protected function iterate( iterable | string $messages, bool $newline ) : array
	{
		$contents = [];

		if( is_string( $messages )) {
			$contents[] = $this->format( $messages );
		} elseif( $newline ) {
			foreach( $messages as $message ) {
				$contents[] = $this->format( $message );
			}
		} else {
			$initial = true;

			foreach( $messages as $message ) {
				if( $initial ) {
					$initial = false;

					$contents[] = $this->format( $message );
				} else {
					$contents[] = $message;
				}
			}
		}

		return $contents;
	}

	/**
	 * @param string $message
	 * @return string
	 */
	protected function format( string $message ) : string
	{
		if( str_starts_with( $message, '<') and ( $segment = strpos( $message, '>')) > 2 and !str_contains( $request = substr( $message, 1, $segment - 1 ), ' ')) {
			$message = sprintf( $this->line, date( $this->date ), substr( $message, $segment + 1 ));

			$message = "<{$request}>{$message}";
		} else {
			$message = sprintf( $this->line, date( $this->date ), $message );
		}

		return $message;
	}
}