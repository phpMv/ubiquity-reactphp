<?php

namespace Ubiquity\servers\react;

use React\Cache\ArrayCache;
use Ubiquity\utils\http\foundation\ReactHttp;
use Ubiquity\utils\http\session\ReactPhpSession;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;

/**
 * React Http server for Ubiquity.
 * Ubiquity\servers\react$ReactServer
 * This class is part of Ubiquity
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *
 */
class ReactServer {
	private $server;
	private $sessionCookieOptions=[0,'','',false,false];
	
	public function init($config,$basedir){
		//To remove: side effects
		ini_set('memory_limit', '1G');
		set_time_limit(0);
		//end To remove
		$httpInstance=new ReactHttp();
		$sessionInstance=new ReactPhpSession();
		$this->server = new \React\Http\Server([
				new SessionMiddleware($config['sessionName']??'cookie_name',new ArrayCache(),$this->sessionCookieOptions),
				function (\Psr\Http\Message\ServerRequestInterface $request) use($config,$httpInstance,$sessionInstance,$basedir){
					$_GET['c']='';
					$uri = ltrim(urldecode(parse_url( $request->getUri()->getPath(), PHP_URL_PATH)),'/');
					if ($uri==null || !file_exists($basedir . '/../' .$uri)) {
						$_GET['c'] = $uri;
					}else{
						$headers=$request->getHeaders();
						$headers['Content-Type']=current($headers['Accept']);
						return new \React\Http\Response(
								$httpInstance->getResponseCode(),
								$headers,
								file_get_contents($basedir . '/../' .$uri)
								);
					}
					
					$headers=$request->getHeaders();
					$headers['Content-Type']=current($headers['Accept']);
					$httpInstance->setRequest($request);
					$sessionInstance->setRequest($request);
					$this->parseRequest($request);
					if(\Ubiquity\orm\DAO::$db==null || \Ubiquity\orm\DAO::$db->getPdoObject()==null){
						\Ubiquity\orm\DAO::startDatabase($config);
					}
					\ob_start ();
					\Ubiquity\controllers\Startup::setHttpInstance($httpInstance);
					\Ubiquity\controllers\Startup::setSessionInstance($sessionInstance);
					\Ubiquity\controllers\Startup::run($config);
					$content=ob_get_clean();
					if(\Ubiquity\orm\DAO::isConnected()){
						\Ubiquity\orm\DAO::closeDb();
					}
					return new \React\Http\Response(200,$httpInstance->getAllHeaders(),$content);
				}
		]);
	}
	
	/**
	 * Sets the session cookie options
	 * @param int $expiresAt
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httpOnly
	 */
	public function setSessionCookieOptions($expiresAt=0,$path='',$domain='', $secure=false,$httpOnly=false){
		$this->sessionCookieOptions=[$expiresAt,$path,$domain,$secure,$httpOnly];
	}
	
	public function run($port){
		$loop = \React\EventLoop\Factory::create();
		$socket = new \React\Socket\Server($port, $loop);
		$this->server->listen($socket);
		$loop->run();
	}
	
	public function parseRequest(\Psr\Http\Message\ServerRequestInterface $request){
		$method=$request->getMethod();
		$uri=$request->getUri()->getPath();
		$parameters=$request->getQueryParams();
		$parsedBody=$request->getParsedBody();
		$headers=$request->getHeaders();
		$server = array_replace([
				'SERVER_NAME' => 'localhost',
				'SERVER_PORT' => 80,
				'HTTP_HOST' => 'localhost',
				'HTTP_USER_AGENT' => 'Ubiquity',
				'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
				'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
				'REMOTE_ADDR' => '127.0.0.1',
				'SCRIPT_NAME' => '',
				'SCRIPT_FILENAME' => '',
				'SERVER_PROTOCOL' => 'HTTP/1.1',
				'REQUEST_TIME' => time(),
		], $request->getServerParams());
		$server['PATH_INFO'] = '';
		$server['REQUEST_METHOD'] = strtoupper($method);
		$components = parse_url($uri);
		if (isset($components['host'])) {
			$server['SERVER_NAME'] = $components['host'];
			$server['HTTP_HOST'] = $components['host'];
		}
		if (isset($components['scheme'])) {
			if ('https' === $components['scheme']) {
				$server['HTTPS'] = 'on';
				$server['SERVER_PORT'] = 443;
			} else {
				unset($server['HTTPS']);
				$server['SERVER_PORT'] = 80;
			}
		}
		if (isset($components['port'])) {
			$server['SERVER_PORT'] = $components['port'];
			$server['HTTP_HOST'] .= ':'.$components['port'];
		}
		if (isset($components['user'])) {
			$server['PHP_AUTH_USER'] = $components['user'];
		}
		if (isset($components['pass'])) {
			$server['PHP_AUTH_PW'] = $components['pass'];
		}
		if (!isset($components['path'])) {
			$components['path'] = '/';
		}
		switch (strtoupper($method)) {
			case 'POST':
			case 'PUT':
			case 'DELETE':
				if (!isset($server['CONTENT_TYPE'])) {
					$server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
				}
				// no break
			case 'PATCH':
				$query = [];
				break;
			default:
				$query = $parameters;
				break;
		}
		$queryString = '';
		if (isset($components['query'])) {
			parse_str(html_entity_decode($components['query']), $qs);
			if ($query) {
				$query = array_replace($qs, $query);
				$queryString = http_build_query($query, '', '&');
			} else {
				$query = $qs;
				$queryString = $components['query'];
			}
		} elseif ($query) {
			$queryString = http_build_query($query, '', '&');
		}
		$server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
		$server['QUERY_STRING'] = $queryString;
		if(isset($headers['X-Requested-With']) && array_search('XMLHttpRequest',$headers['X-Requested-With'])!==false){
			$server ['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
		}
		if(strtoupper($method)==='POST'){
			$_POST=$parsedBody;
		}
		if(sizeof($parameters)>0){
			$_GET=array_merge($_GET,$parameters);
		}
		$_SERVER=$server;
	}
}

