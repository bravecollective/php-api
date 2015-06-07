<?php

namespace Brave;

use Brave\Auth\CoreAuthHandler;

/**
 * Class Net
 * @package Brave\API
 */
Class Net{

    /**
     * @var string
     */
    private $identity = '';

    /**
     * @var string
     */
    private $private_key = '';

    /**
     * @var string
     */
    private $public_key = '';

    /**
     * @var array
     */
    private $options = [];

	/**
	 * @param      $identity
	 * @param      $private_key
	 * @param      $public_key
	 * @param bool $debug
	 */
	public function __construct($identity, $private_key, $public_key, $debug = false)
    {
        $this->identity = $identity;
        $this->private_key = $private_key;
        $this->public_key = $public_key;

        $this->options = [
            'auth' => new CoreAuthHandler($identity, $private_key, $public_key, $debug)
        ];
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function post($url, $data = [])
    {
	    $response = \Requests::post($url, [], $data, $this->options);

        return $response;
    }
}