<?php

/**
 * Class ECDSA
 *
 * Encapsulate ECDSA Functions from phpecc (https://github.com/mdanter/phpecc) into an easy to use API
 *
 * @author Matthew Glinski
 * @link
 * @version 1.0
 * @license MIT (http://opensource.org/licenses/MIT)
 *
 * ---------------------------------------
 *
 * Copyright (c) 2014 Matthew Glinski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class ECDSA
{

	/**
	 * generateEccKeys()
	 *
	 * Generate a EC Public and Private Key Pair and return the HEX formatted versions
	 * The CURVE type is hardcoded with prime256v1
	 * The DIGEST type is hardcoded with SHA256
	 *
	 * @return array
	 */
	public function generateEccKeys()
	{
		// Load up the curve constants
		$generator = \NISTcurve::generator_256();
		$order = $generator->getOrder();

		// Generate a random number as the private key
		$secret_multiplier = gmp_Utils::gmp_random( $order );
		// THIS NEEDS TO BE FIXED/VALIDATED, I DO NOT TRUST IT'S RANDOMNESS

		// build a public key object
		$pubkey = new PublicKey( $generator, Point::rmul( $generator, $secret_multiplier ) );

		// convert the public key points to hex
		$public = gmp_Utils::gmp_dechex($pubkey->getPoint()->getX()).gmp_Utils::gmp_dechex($pubkey->getPoint()->getY());

		// convert the private key number to hex
		$private = gmp_Utils::gmp_dechex($secret_multiplier);

		// return the key pair
		return array(
			'public' => $public,
			'private' => $private,
		);
	}

	/**
	 * sign_data()
	 *
	 * sign data blob using ECDSA
	 *
	 * @param $private_key
	 * @param $data
	 *
	 * @return array
	 */
	public static function sign_data($private_key, $data)
	{
		// Calculate SHA256 digest of the data blob
		$digest = hash('sha256', $data);

		// now that we have a digest instead of data, sign it
		return self::sign_digest($private_key, $digest);
	}

    /**
     * sign_digest($private_key, $digest)
     *
     * Sign a valid sha256 digest using a ECDSA Private Key
     *
     * @param $private_key_hex
     * @param $digest
     *
     * @throws Exception
     *
     * @internal param string $private_key
     * @internal param string $data @*
     *
     * @return string
     */
	public static function sign_digest($private_key_hex, $digest)
	{
		// Load up Curve Constants
		$generator = \NISTcurve::generator_256();
		$order = $generator->getOrder();

		// Convert the Private Key back into decimal notation
		$secret = gmp_Utils::gmp_hexdec( '0x'.$private_key_hex );

		// Load data into key class constructors
		$public_key = new PublicKey($generator, Point::rmul($generator, $secret));
		$private_key = new PrivateKey($public_key, $secret);

		// Get a random value for k
		$k = gmp_Utils::gmp_random($order);

		if(1 <= $k and $k < $order)
		{
			// Signing a hash value
			$signature = $private_key->sign(gmp_Utils::gmp_hexdec( '0x'.$digest ), $k);

			// Return the signature parts in a hex string
			return gmp_Utils::gmp_dechex( $signature->getR() ).gmp_Utils::gmp_dechex( $signature->getS() );
		}
		else
		{
			throw new Exception('"k" is not a valid random number');
		}
	}

	/**
	 * validate()
	 *
	 * validate a given signature with the data that was signed
	 *
	 * @param $data
	 * @param $signature
	 * @param $public_key
	 *
	 * @return int
	 */
	public static function validate($data, $signature, $public_key)
	{
		// Calculate SHA256 digest of the data
		$digest = hash('sha256', $data);

		// Load up the curve in use
		$generator = \NISTcurve::generator_256();
		$order = $generator->getOrder();
		$curve = $generator->getCurve();

		// get the length of the order, used to ensure sanity of hex strings
		$order_len = strlen( gmp_Utils::gmp_dechex($order) );

		// Generate Public Key Data Structure from HEX String if passed as HEX
		if(!is_array($public_key))
		{
			$public_key = array(
				'x' => gmp_Utils::gmp_hexdec( '0x'.substr($public_key, 0, $order_len) ),
				'y' => gmp_Utils::gmp_hexdec( '0x'.substr($public_key, $order_len) )
			);
		}

		// Generate Signature Key Data Structure from HEX String if passed as HEX
		if(!is_array($signature))
		{
			$signature = array(
				'r' => gmp_Utils::gmp_hexdec( '0x'.substr($signature, 0, $order_len) ),
				's' => gmp_Utils::gmp_hexdec( '0x'.substr($signature, $order_len) )
			);
		}

		// check for hex chars in the signature[r] string
		if( !is_numeric($signature['r']) and ctype_xdigit($signature['r']) )
		{
			$signature['r'] = gmp_Utils::gmp_hexdec( '0x'.$signature['r'] );
		}

		// check for hex chars in the signature[s] string
		if( !is_numeric($signature['s']) and ctype_xdigit($signature['s']) )
		{
			$signature['s'] = gmp_Utils::gmp_hexdec( '0x'.$signature['s'] );
		}

		// generate the public key object from the given x,y points
		$public_key = new PublicKey( $generator, new Point( $curve, $public_key['x'], $public_key['y'], $order ) );

		// create a signature object from the given signature data
		$signature = new Signature( $signature['r'], $signature['s']);

		// Verify the signature for the hashed data
		if( $public_key->verifies( gmp_Utils::gmp_hexdec( '0x'.$digest ), $signature ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}