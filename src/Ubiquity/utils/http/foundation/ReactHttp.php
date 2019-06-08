<?php
namespace Ubiquity\utils\http\foundation;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

/**
 * Http instance for ReactPHP.
 * Ubiquity\utils\http\foundation$ReactHttp
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *         
 */
class ReactHttp extends AbstractHttp {

	private $headers;

	/**
	 *
	 * @var ServerRequestInterface
	 */
	private $request;

	/**
	 *
	 * @var Response
	 */
	private $response;

	public function getAllHeaders() {
		return $this->headers;
	}

	public function header($key, $value, $replace = null, $http_response_code = null) {
		$this->headers[$key] = $value;
		if ($http_response_code != null) {
			$this->response->withStatus($http_response_code);
		}
		$this->response->withHeader($key, $value);
	}

	/**
	 *
	 * @return int
	 */
	public function getResponseCode() {
		return $this->response->getStatusCode();
	}

	/**
	 *
	 * @param mixed $headers
	 */
	private function setHeaders($headers) {
		foreach ($headers as $k => $header) {
			if (is_array($header) && sizeof($header) == 1) {
				$this->headers[$k] = current($header);
			} else {
				$this->headers[$k] = $header;
			}
			$this->response->withHeader($k, $header);
		}
	}

	/**
	 *
	 * @param int $responseCode
	 */
	public function setResponseCode($responseCode) {
		if ($responseCode != null) {
			$this->response->withStatus($responseCode);
		}
	}

	public function headersSent(string &$file = null, int &$line = null) {
		return false;
	}

	public function getInput() {
		return $this->request->getParsedBody();
	}

	/**
	 *
	 * @param mixed $request
	 */
	public function setRequest(ServerRequestInterface $request, Response $response) {
		$this->request = $request;
		$this->response = $response;
		$default = [
			'Content-Type' => 'text/html; charset=utf-8',
			'Server' => $request->getUri()->getHost()
		];
		$this->setHeaders(array_merge($default, $response->getHeaders()));
	}
}

