<?php
namespace Ubiquity\utils\http\session;

use WyriHaximus\React\Http\Middleware\SessionMiddleware;

/**
 * Session object for ReactPHP.
 * Ubiquity\utils\http\session$ReactPhpSession
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.1
 *
 */
class ReactPhpSession extends AbstractSession {

	private $visitorCount = 0;

	/**
	 *
	 * @var \WyriHaximus\React\Http\Middleware\Session
	 */
	private $sessionInstance;

	private function getContents() {
		return $this->sessionInstance->getContents();
	}

	public function setRequest(\Psr\Http\Message\ServerRequestInterface $request) {
		$this->sessionInstance = $request->getAttribute(SessionMiddleware::ATTRIBUTE_NAME);
	}

	public function set($key, $value) {
		$contents = $this->getContents();
		$contents[$key] = $value;
		$this->sessionInstance->setContents($contents);
	}

	public function getAll() {
		return $this->getContents();
	}

	public function get($key, $default = null) {
		$contents = $this->getContents();
		return $contents[$key] ?? $default;
	}

	public function start($name = null) {
		$this->sessionInstance->begin();
		$this->visitorCount ++;
	}

	public function exists($key) {
		$contents = $this->getContents();
		return isset($contents[$key]);
	}

	public function terminate() {
		$this->sessionInstance->end();
		$this->visitorCount --;
	}

	public function isStarted() {
		return $this->sessionInstance->isActive();
	}

	public function delete($key) {
		$contents = $this->getContents();
		unset($contents[$key]);
		$this->sessionInstance->setContents($contents);
	}

	public function visitorCount(): int {
		return $this->visitorCount;
	}
}

