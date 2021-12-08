<?php

namespace Kucbel\Console\Input;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

abstract class InputDecorator implements InputInterface
{
	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * PropertyInput constructor.
	 *
	 * @param InputInterface $input
	 */
	function __construct( InputInterface $input )
	{
		$this->input = $input;
	}

	/**
	 * @inheritdoc
	 */
	function bind( InputDefinition $definition )
	{
		$this->input->bind( $definition );
	}

	/**
	 * @inheritdoc
	 */
	function validate()
	{
		$this->input->validate();
	}

	/**
	 * @inheritdoc
	 */
	function getFirstArgument()
	{
		return $this->input->getFirstArgument();
	}

	/**
	 * @inheritdoc
	 */
	function hasParameterOption( $values, bool $params = false )
	{
		return $this->input->hasParameterOption( $values, $params );
	}

	/**
	 * @inheritdoc
	 */
	function getParameterOption( $values, $default = false, bool $params = false )
	{
		return $this->input->getParameterOption( $values, $default, $params );
	}

	/**
	 * @inheritdoc
	 */
	function getArguments()
	{
		return $this->input->getArguments();
	}

	/**
	 * @inheritdoc
	 */
	function setArgument( string $name, $value )
	{
		$this->input->setArgument( $name, $value );
	}

	/**
	 * @inheritdoc
	 */
	function hasArgument( string $name )
	{
		return $this->input->hasArgument( $name );
	}

	/**
	 * @inheritdoc
	 */
	function getArgument( string $name )
	{
		return $this->input->getArgument( $name );
	}

	/**
	 * @inheritdoc
	 */
	function getOptions()
	{
		return $this->input->getOptions();
	}

	/**
	 * @inheritdoc
	 */
	function setOption( string $name, $value )
	{
		$this->input->setOption( $name, $value );
	}

	/**
	 * @inheritdoc
	 */
	function hasOption( string $name )
	{
		return $this->input->hasOption( $name );
	}

	/**
	 * @inheritdoc
	 */
	function getOption( string $name )
	{
		return $this->input->getOption( $name );
	}

	/**
	 * @inheritdoc
	 */
	function setInteractive( bool $interactive )
	{
		$this->input->setInteractive( $interactive );
	}

	/**
	 * @inheritdoc
	 */
	function isInteractive()
	{
		return $this->input->isInteractive();
	}
}