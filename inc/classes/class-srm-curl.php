<?php

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

class TeamOneRedirectManagerCurl {
	private $curl;
	private $response;
	private $error;
	private $options = array();

	public function __construct() {
		$this->curl = curl_init();
		$this->set_option( CURLOPT_RETURNTRANSFER, true );
		$this->set_option( CURLOPT_FOLLOWLOCATION, true );
		$this->set_option( CURLOPT_AUTOREFERER, true );
		$this->set_option( CURLOPT_TIMEOUT, 30 );
		$this->set_option( CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0" );
	}

	public function __destruct() {
		curl_close( $this->curl );
	}

	public function set_option( $option, $value ) {
		$this->options[ $option ] = $value;
		curl_setopt( $this->curl, $option, $value );
	}

	public function get( $url ) {
		$this->set_option( CURLOPT_URL, $url );
		$this->response = curl_exec( $this->curl );
		$this->error    = curl_error( $this->curl );

		return $this->response;
	}

	public function post( $url, $data ) {
		$this->set_option( CURLOPT_URL, $url );
		$this->set_option( CURLOPT_POST, true );
		$this->set_option( CURLOPT_POSTFIELDS, $data );
		$this->response = curl_exec( $this->curl );
		$this->error    = curl_error( $this->curl );

		return $this->response;
	}

	public function get_error() {
		return $this->error;
	}
}