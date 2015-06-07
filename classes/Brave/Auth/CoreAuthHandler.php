<?php

namespace Brave\Auth;

use \Mdanter\Ecc\EccFactory;
use \Mdanter\Ecc\Primitives\Point;
use \Mdanter\Ecc\Crypto\Signature\Signature;
use \Mdanter\Ecc\Random\RandomGeneratorFactory;
use \Mdanter\Ecc\Crypto\Key\PrivateKey;
use \Mdanter\Ecc\Crypto\Key\PublicKey;

/**
 * Class CoreAuthHandler
 *
 * @package Brave\Auth
 */
class CoreAuthHandler implements \Requests_Auth
{

	private $debug = FALSE;

	private $math_adapter = FALSE;
	private $service_name = FALSE;
	private $private_key = FALSE;
	private $public_key = FALSE;

	/**
	 * Constructor
	 *
	 * @param      $service
	 * @param      $private_key
	 * @param      $public_key
	 * @param bool $debug
	 */
	public function __construct( $service, $private_key, $public_key, $debug = FALSE )
	{
		$this->math_adapter = EccFactory::getAdapter();
		$this->service_name = $service;
		$this->private_key  = $private_key;
		$this->public_key   = $public_key;
		$this->debug        = $debug;
	}

	/**
	 * @param \Requests_Hooks $hooks
	 */
	public function register( \Requests_Hooks &$hooks )
	{
		$hooks->register('requests.before_request', [&$this, 'before_request']);
		$hooks->register('requests.after_request', [&$this, 'after_request']);
	}

	/**
	 * before_request
	 * Hook for PHP Requests
	 *
	 * @param $url
	 * @param $headers
	 * @param $data
	 */
	public function before_request( &$url, &$headers, &$data )
	{
		// generate a date string
		$date            = new \DateTime('NOW', new \DateTimeZone("GMT"));
		$headers['Date'] = $date->format("D, d M Y H:i:s \G\M\T");

		// build up the data to be signed
		$request_data = $headers['Date']."\n".$url."\n";
		if( !empty($data) )
		{
			$request_data .= http_build_query($data);
		}

		// get a signature for our request

		// private key is in hex form, needs to be converted into PrivateKey Object
		$generator   = EccFactory::getNistCurves()->generator256();
		$private_key = new PrivateKey($this->math_adapter, $generator, $this->math_adapter->hexDec($this->private_key));

		$hash         = $this->math_adapter->hexDec(hash("sha256", $request_data));
		$signer       = EccFactory::getSigner();
		$randomK      = RandomGeneratorFactory::getRandomGenerator()->generate($private_key->getPoint()->getOrder());
		$signatureObj = $signer->sign($private_key, $hash, $randomK);
		$signature    = $this->math_adapter->decHex($signatureObj->getR()).$this->math_adapter->decHex($signatureObj->getS());

		// apply the HTTP headers and send the request
		$headers['X-Service']   = $this->service_name;
		$headers['X-Signature'] = $signature;

		if( $this->debug )
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
	 *
	 * @throws
	 */
	public function after_request( \Requests_Response &$return )
	{
		$headers   = $return->headers;
		$url       = $return->url;
		$data      = $return->body;
		$signature = $headers['x-signature'];

		if( $this->debug )
		{
			echo "\n\nResponse Data:\n";
			var_dump($return);
		}

		// Check if signature header exists, if not the request failed
		if( !isset($headers['x-signature']) or $headers['x-signature'] == '' )
		{
			throw new \Exception('Request Failed');
		}

		// build up the data to be signed
		$request_data = $this->service_name."\n".$headers['date']."\n".$url."\n";
		if( !empty($data) )
		{
			$request_data .= trim($data);
		}

		// try and validate the signature

		// ------------------------------------
		$generator = EccFactory::getNistCurves()->generator256();

		$order_len  = strlen($this->math_adapter->decHex($generator->getOrder()));
		$x          = $this->math_adapter->hexDec(substr($this->public_key, 0, $order_len));
		$y          = $this->math_adapter->hexDec(substr($this->public_key, $order_len));
		$point      = new Point($this->math_adapter, EccFactory::getNistCurves()->curve256(), $x, $y, $generator->getOrder());
		$public_key = new PublicKey($this->math_adapter, $generator, $point);

		$r         = $this->math_adapter->hexDec(substr($signature, 0, $order_len));
		$s         = $this->math_adapter->hexDec(substr($signature, $order_len));
		$signature = new Signature($r, $s);

		$signer     = EccFactory::getSigner();
		$check_hash = $this->math_adapter->hexDec(hash("sha256", $request_data));
		$result     = $signer->verify($public_key, $signature, $check_hash);
		// ------------------------------------

		//$result = \ECDSA::validate($request_data, $signature, $this->public_key);

		// if signature validation failed, throw exception
		if( $result !== TRUE )
		{
			throw new \Exception('Signature Does Not Validate!');
		}
	}
}