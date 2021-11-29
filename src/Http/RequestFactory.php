<?php

namespace Kucbel\Console\Http;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\SmartObject;

class RequestFactory
{
	use SmartObject;

	/**
	 * @var string
	 */
	private $address;

	/**
	 * @var string | null
	 */
	private $script;

	/**
	 * @var string | null
	 */
	private $method;

	/**
	 * @var string | null
	 */
	private $remote;

	/**
	 * RequestFactory constructor.
	 *
	 * @param string $address
	 * @param string | null $script
	 * @param string | null $method
	 * @param string | null $remote
	 */
	function __construct( string $address, string $script = null, string $method = null, string $remote = null )
	{
		$this->address = $address;
		$this->script = $script;
		$this->method = $method;
		$this->remote = $remote;
	}

	/**
	 * @return Request
	 */
	function create() : Request
	{
		$script = new UrlScript( $this->address, $this->script ?? '');

		return new Request( $script, null, null, null, null, $this->method, $this->remote );
	}
}
