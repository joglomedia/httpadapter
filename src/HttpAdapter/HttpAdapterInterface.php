<?php
/**
 * All Http adapters need to implement this interface.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HttpAdapter;

/**
 * HttpAdapter interface.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Antoine Corcy <contact@sbin.dk>
 */
interface HttpAdapterInterface
{	
	/**
	 * Set adapter config
	 *
	 * @author Edi Septriyanto <me@masedi.net>
	 *
	 * @param array $config
	 *
	 * @return array
	 */
    public function setConfig(array $config);
	
    /**
	 * Returns the content fetched from a given URL.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
    public function getContent($url);

    /**
	 * Returns the name of the HTTP Adapter.
	 *
	 * @return string
	 */
    public function getName();
	
	/**
	 * Restfull helper, GET request
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 *
	 * @return string
	 */
	public function get( $url, $params, $headers );
	
	/**
	 * Restfull helper, POST request
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 *
	 * @return string
	 */
	public function post( $url, $params, $headers );
	
	/**
	 * Restfull helper, UPDATE request
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 *
	 * @return string
	 */
	public function update( $url, $params, $headers );
	
	/**
	 * Restfull helper, DELETE request
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 *
	 * @return string
	 */
	public function delete( $url, $params, $headers );
}
