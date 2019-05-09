<?php

namespace Ubiquity\utils\http\foundation;

/**
 * Http instance for ReactPHP.
 * Ubiquity\utils\http\foundation$ReactHttp
 * This class is part of Ubiquity
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *
 */
class ReactHttp extends AbstractHttp {
	private $headers;
	private $responseCode=200;
	private $request;
	
	
	public function getAllHeaders() {
		return $this->headers;
	}

	public function header($key,$value,$replace=null,$http_response_code=null) {
		$this->headers[$key]=$value;
		if($http_response_code!=null){
			$this->responseCode=$http_response_code;
		}
	}
	
	/**
	 * @return int
	 */
	public function getResponseCode() {
		return $this->responseCode;
	}

	/**
	 * @param mixed $headers
	 */
	private function setHeaders($headers) {
		foreach ($headers as $k=>$header){
			if(is_array($header) && sizeof($header)==1){
				$this->headers[$k] = current($header);
			}else{
				$this->headers[$k]=$header;
			}
		}
	}

	/**
	 * @param int $responseCode
	 */
	public function setResponseCode($responseCode) {
		if($responseCode!=null){
			$this->responseCode = $responseCode;
		}
	}
	
	public function headersSent(string &$file = null, int &$line = null) {
		return false;
	}
	
	public function getInput() {
		return $this->request->getParsedBody();
	}
	/**
	 * @param mixed $request
	 */
	public function setRequest($request) {
		$this->request=$request;
		$default=['Content-Type'=>'text/html','Server'=>$request->getUri()->getHost()];
		$this->setHeaders(array_merge($default,$request->getHeaders()));
	}
}

