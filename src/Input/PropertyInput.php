<?php

namespace Kucbel\Console\Input;

use Nette\MemberAccessException;
use Nette\Utils\ObjectHelpers;

class PropertyInput extends InputDecorator
{
	/**
	 * @param string $name
	 * @return mixed
	 * @throws MemberAccessException
	 */
	function __get( string $name ) : mixed
	{
		if( $this->hasArgument( $name )) {
			return $this->getArgument( $name );
		} elseif( $this->hasOption( $name )) {
			return $this->getOption( $name );
		} else {
			ObjectHelpers::strictGet( static::class, $name );
		}
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @throws MemberAccessException
	 */
	function __set( string $name, mixed $value ) : void
	{
		if( $this->hasArgument( $name )) {
			$this->setArgument( $name, $value );
		} elseif( $this->hasOption( $name )) {
			$this->setOption( $name, $value );
		} else {
			ObjectHelpers::strictSet( static::class, $name );
		}
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	function __isset( string $name ) : bool
	{
		return $this->hasArgument( $name ) or $this->hasOption( $name );
	}

}