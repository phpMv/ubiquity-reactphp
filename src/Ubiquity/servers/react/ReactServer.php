<?php
namespace Ubiquity\servers\react;

use React\Cache\ArrayCache;
use Ubiquity\utils\http\foundation\ReactHttp;
use Ubiquity\utils\http\session\ReactPhpSession;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;
use Ubiquity\utils\http\foundation\Psr7;
use React\Http\Response;
use React\Stream\ThroughStream;

/**
 * React Http server for Ubiquity.
 * Ubiquity\servers\react$ReactServer
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
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
		ini_set('memory_limit', '1G');
		set_time_limit(0);
		// end To remove
		$httpInstance = new ReactHttp();
		$sessionInstance = new ReactPhpSession();
		$this->server = new \React\Http\Server([
			new SessionMiddleware($config['sessionName'] ?? 'cookie_name', new ArrayCache(), $this->sessionCookieOptions),
			function (\Psr\Http\Message\ServerRequestInterface $request) use ($config, $httpInstance, $sessionInstance, $basedir) {
				$_GET['c'] = '';
				$httpInstance->setResponseCode(200);
				$uri = ltrim(urldecode(parse_url($request->getUri()->getPath(), PHP_URL_PATH)), '/');
				if ($uri == null || ! file_exists($basedir . '/../' . $uri)) {
					$_GET['c'] = $uri;
				} else {
					$headers = $request->getHeaders();
					$headers['Content-Type'] = current($headers['Accept']);
					return new \React\Http\Response($httpInstance->getResponseCode(), $headers, file_get_contents($basedir . '/../' . $uri));
				}

				$headers = $request->getHeaders();
				// $headers['Content-Type'] = current($headers['Accept']);
				$response = new Response(200, [
					'Content-Type' => current($headers['Accept'])
				]);
				$httpInstance->setRequest($request, $response);
				$sessionInstance->setRequest($request);
				$this->parseRequest($request);
				if (\Ubiquity\orm\DAO::$db == null || \Ubiquity\orm\DAO::$db->getPdoObject() == null) {
					\Ubiquity\orm\DAO::startDatabase($config);
				}
				\ob_start();
				\Ubiquity\controllers\Startup::setHttpInstance($httpInstance);
				\Ubiquity\controllers\Startup::setSessionInstance($sessionInstance);
				\Ubiquity\controllers\Startup::run($config);
				$content = ob_get_clean();
				if (\Ubiquity\orm\DAO::isConnected()) {
					\Ubiquity\orm\DAO::closeDb();
				}
				$response->getBody()->write($content);
				return $response;
			}
		]);
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

