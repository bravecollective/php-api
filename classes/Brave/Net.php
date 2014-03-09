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
    private $options = array();

    /**
     * @param $token
     * @param $private_key
     * @param $public_key
     */
    function __construct($identity, $private_key, $public_key)
    {
        $this->identity = $identity;
        $this->private_key = $private_key;
        $this->public_key = $public_key;

        $this->options = array(
            'auth' => new CoreAuthHandler($identity, $private_key, $public_key)
        );
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function post($url, $data = array())
    {
	    $response = \Requests::post($url, array(), $data, $this->options);

        return $response;
    }
}