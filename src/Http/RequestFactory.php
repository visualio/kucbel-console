<?php

namespace Kucbel\Console\Http;

use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\SmartObject;

class RequestFactory
{
	use SmartObject;

	/**
	 * @var string
	 */
	private $server;

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
	 * @param string $server
	 * @param string $script
	 * @param string $method
	 * @param string $remote
	 */
	function __construct( string $server, string $script = null, string $method = null, string $remote = null )
	{
		$this->server = $server;
		$this->script = $script;
		$this->method = $method;
		$this->remote = $remote;
	}

	/**
	 * @return IRequest
	 */
	function create() : IRequest
	{
		return new Request( new UrlScript( $this->server, $this->script ), null, null, null, null, null, $this->method, $this->remote );
	}
}
