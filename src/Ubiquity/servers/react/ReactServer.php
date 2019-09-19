<?php
namespace Ubiquity\servers\react;

use React\Cache\ArrayCache;
use Ubiquity\utils\http\foundation\ReactHttp;
use Ubiquity\utils\http\session\ReactPhpSession;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;
use Ubiquity\utils\http\foundation\Psr7;

/**
 * React Http server for Ubiquity.
 * Ubiquity\servers\react$ReactServer
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.2
 *         
 */
class ReactServer {

	private $server;

	private $sessionCookieOptions = [
		0,
		'',
		'',
		false,
		false
	];

	public function init($config, $basedir) {
		// To remove: side effects
		\ini_set('memory_limit', '1G');
		\set_time_limit(0);
		// end To remove
		$httpInstance = new ReactHttp();
		$sessionInstance = new ReactPhpSession();
		$this->server = new \React\Http\Server([
			new SessionMiddleware($config['sessionName'] ?? 'cookie_name', new ArrayCache(), $this->sessionCookieOptions),
			function (\Psr\Http\Message\ServerRequestInterface $request) use ($config, $httpInstance, $sessionInstance, $basedir) {
				$_GET['c'] = '';
				$httpInstance->setResponseCode(200);
				$uri = \ltrim(\urldecode(\parse_url($request->getUri()->getPath(), \PHP_URL_PATH)), '/');
				if ($uri == null || ! ($fe=\file_exists($basedir . '/../' . $uri))) {
					$_GET['c'] = $uri;
				} else {
					$headers = $request->getHeaders();
					$headers['Content-Type'] = \current($headers['Accept']);
					if($fe){
						return new \React\Http\Response($httpInstance->getResponseCode(), $headers, \file_get_contents($basedir . '/../' . $uri));
					}
					return new \React\Http\Response(404, $headers, 'File not found '. $uri);
				}
				$httpInstance->setRequest($request);
				$sessionInstance->setRequest($request);
				$this->parseRequest($request);
				\ob_start();
				\Ubiquity\controllers\Startup::setHttpInstance($httpInstance);
				\Ubiquity\controllers\Startup::setSessionInstance($sessionInstance);
				\Ubiquity\controllers\Startup::forward($_GET['c']);
				$content = \ob_get_clean();
				return new \React\Http\Response($httpInstance->getResponseCode(), $httpInstance->getAllHeaders(), $content);
			}
			]);
		\Ubiquity\controllers\Startup::init($config);
	}

	/**
	 * Sets the session cookie options
	 *
	 * @param int $expiresAt
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httpOnly
	 */
	public function setSessionCookieOptions($expiresAt = 0, $path = '', $domain = '', $secure = false, $httpOnly = false) {
		$this->sessionCookieOptions = [
			$expiresAt,
			$path,
			$domain,
			$secure,
			$httpOnly
		];
	}

	public function run($port) {
		$loop = \React\EventLoop\Factory::create();
		$socket = new \React\Socket\Server($port, $loop);
		$this->server->listen($socket);
		$loop->run();
	}

	public function parseRequest(\Psr\Http\Message\ServerRequestInterface $request) {
		Psr7::requestToGlobal($request);
	}
}

