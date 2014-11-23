<?php

namespace Brave\Auth;

/**
 * Class CoreAuthHandler
 * @package Brave\Auth
 */
class CoreAuthHandler implements \Requests_Auth
{

	private $debug = false;

    /**
     * Constructor
     *
     * @param $service
     * @param $private_key
     * @param $public_key
     */
    public function __construct($service, $private_key, $public_key)
	{
		$this->service_name = $service;
		$this->private_key = $private_key;
		$this->public_key = $public_key;
	}


    /**
     * @param \Requests_Hooks $hooks
     */
    public function register(\Requests_Hooks &$hooks)
	{
		$hooks->register('requests.before_request', array(&$this, 'before_request'));
		$hooks->register('requests.after_request', array(&$this, 'after_request'));
	}


    /**
     * before_request
     * Hook for PHP Requests
     *
     * @param $url
     * @param $headers
     * @param $data
     */
    public function before_request(&$url, &$headers, &$data)
	{
		// generate a date string
		$date = new \DateTime('NOW', new \DateTimeZone("GMT"));
		$headers['Date'] = $date->format("D, d M Y H:i:s \G\M\T");

		// build up the data to be signed
		$request_data = $headers['Date']."\n".$url."\n";
		if(!empty($data))
		{
			$request_data .= http_build_query($data);
		}

		// get a signature for our request
		$signature = \ECDSA::sign_data($this->private_key, $request_data);

		// apply the HTTP headers and send the request
		$headers['X-Service'] = $this->service_name;
		$headers['X-Signature'] = $signature;

		if($this->debug)
		{
			echo "\n\nRequest Data\n\n";

			echo "URL:\n";
			var_dump($url);

			echo "HEADERS:\n";
			var_dump($headers);

			echo "DATA:\n";
			var_dump($data);
		}
	}


    /**
     * after_request
     * Hook for PHP Requests
     *
     * @param \Requests_Response $return
     * @throws
     */
    public function after_request(\Requests_Response &$return)
	{
		$headers = $return->headers;
		$url = $return->url;
		$data = $return->body;
		$signature = $headers['x-signature'];

		if($this->debug)
		{
			echo "\n\nResponse Data:\n";
			var_dump($return);
		}

		// Check if signature header exists, if not the request failed
		if(!isset($headers['x-signature']) or $headers['x-signature'] == '')
		{
			throw new \Exception('Request Failed');
		}

		// build up the data to be signed
		$request_data = $this->service_name."\n".$headers['date']."\n".$url."\n";
		if(!empty($data))
		{
			$request_data .= trim($data);
		}

		// try and validate the signature
		$result = \ECDSA::validate($request_data, $signature, $this->public_key);

		// if signature validation failed, throw exception
		if ($result !== TRUE)
		{
			throw new \Exception('Signature Does Not Validate');
		}
	}
}