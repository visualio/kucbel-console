<?php

namespace Kucbel\Console\Format;

trait FormatMethod
{
	/**
	 * @var string
	 */
	protected static $defaultPrint = '%s  %s';

	/**
	 * @var string
	 */
	protected static $defaultDate = 'H:i:s';

	/**
	 * @param string $message
	 * @param string | null $status
	 * @return string
	 */
	protected function format( string $message, string $status = null ) : string
	{
		$message = sprintf( self::$defaultPrint, date( static::$defaultDate ), $message );

		if( $status ) {
			$message = "<{$status}>{$message}</{$status}>";
		}

		return $message;
	}
}