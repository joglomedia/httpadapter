<?php
namespace HttpAdapter;

use HttpAdapter\HttpAdapterInterface;

class Restful 
{
	private $adapter;
	
	public function __construct( HttpAdapterInterface $adapter = null )
	{
		$this->setAdapter( $adapter );
	}
	
	/**
	 * Set the adapter to use. The cURL adapter will be used by default.
	 *
	 * @param HttpAdapterInterface $adapter The HttpAdapter to use (optional).
	 */
	public function setAdapter( HttpAdapterInterface $adapter = null )
	{
        $this->adapter = ( $adapter ) ?: new CurlHttpAdapter();
	}
	
	/**
	 * Restful API method helper, send GET request
	 */
	public function get( $url = '', $params = array(), $headers = array() )
	{
		$response = $this->adapter->createRequest( 'GET', $url, $params, $headers );
		
		return $response['body'];
	}
	
	/**
	 * Restful API method helper, send POST request
	 */
	public function post( $url = '', $params = array(), $headers = array() )
	{
		return $this->adapter->createRequest( 'POST', $url, $params, $headers );
	}
	
	/**
	 * Restful API method helper, send UPDATE request
	 */
	public function update( $url = '', $params = array(), $headers = array() )
	{
		return $this->adapter->createRequest( 'UPDATE', $url, $params, $headers );
	}

	/**
	 * Restful API method helper, send DELETE request
	 */
	public function delete( $url = '', $params = array(), $headers = array() )
	{
		return $this->adapter->createRequest( 'DELETE', $url, $params, $headers );
	}
	
	/**
	 * Restful API method helper, send PUT request
	 */
	public function put( $url = '', $params = null, $headers = null )
	{
		return $this->adapter->createRequest( 'PUT', $url, $params, $headers );
	}

}
