<?php
/**
 * This file is part of the HttpAdapter library.
 *
 * (c) Edi Septriyanto <me@masedi.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace HttpAdapter;

/**
 * CurlHttpAdapter class.
 *
 * @author Edi Septriyanto <me@masedi.net>
 */
class CurlHttpAdapter implements HttpAdapterInterface 
{
	/**
	 * The adapter version.
	 */
	const VERSION = '0.2.0';
	
	/**
	 * The adapter config.
	 */
	private $config;
	
	/**
	 * The adapter request setting to use.
	 */
	private $requestSetting;
	
	/**
	 * The adapter response returned from http request.
	 */
	private $response;
	
	/**
	 * Creates a new CurlHttpAdapter object.
	 *
	 * @param array $config, the adapter configuration to use for this request.
	 * @return void
	 */
	public function __construct($config = array()) 
	{
		$this->setConfig($config);
		$this->setRequestSetting();
		$this->setRequestUserAgent();
	}

	/**
	 * setConfig interface method.
	 * Set adapter config.
	 *
	 * @param array $config curl option.
	 * @return void value is stored to the config array class variable.
	 */
	public function setConfig($config = array()) 
	{
		// Set and overwrite default CurlHttpAdapter config options.
		$this->config = array_merge(
			array(
				// You probably need this part for your API host.
				'host'					=> '',
				'port'					=> '',
				
				// You probably don't want to change any of these curl values.
				'curl_http_version'		=> 'HTTP 1.1',	// HTTP version to send request.
				'curl_connecttimeout'	=> 30,			// Maximum connection time out for the request.
				'curl_timeout'			=> 10,			// Maximum operation timeout before closing connection.
				
				// If you want to include header in curl response, these should be set to true.
				'curl_header'			=> false,
				// If you want to exclude html body in curl response, these should be set to true.
				'curl_nobody'			=> false,

				// Leave 'curl_useragent' blank for default, otherwise set this to
				// something that clearly identifies your app.
				'curl_useragent'		=> '',	// Eg. Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.
				
				// Leave 'curl_referer' blank for request without referer, otherwise set this to
				// something that required to perform request.
				'curl_referer'			=> '',
				
				// If your HTTP request set to follows redirect, this should be set to true.
				'curl_followlocation'	=> true,
				
				// Basic HTTP Auth.
				'curl_http_auth'		=> false,
				
				// If you set 'curl_followlocation' to true, this should be set to something greater than 0.
				'curl_maxredirs'		=> 10,
				
				// For HTTP request using SSL connection, this should be set to true.
				'use_ssl'				=> false,
				// For secure connection with SSL verification, this should always be set to true.
				'curl_ssl_verifypeer'	=> true,
				// For secure connection with SSL verification, this should always be set to 2.
				'curl_ssl_verifyhost'	=> 2,

				// For HTTP request using SSL connection, this should be set to your cacert.pem file.
				// you can get the latest cacert.pem from here http://curl.haxx.se/ca/cacert.pem
				// if you're getting HTTP 0 responses, check cacert.pem exists and is readable
				// without it curl won't be able to create an SSL connection.
				'curl_cainfo'			=> __DIR__ . DIRECTORY_SEPARATOR . 'Cert' . DIRECTORY_SEPARATOR . 'cacert.pem',
				'curl_capath'			=> __DIR__ . DIRECTORY_SEPARATOR . 'Cert',
				
				// For HTTP request using pre-defined cookie, this should be set to true.
				'use_cookie'			=> false,
				// For HTTP request using pre-defined cookie, this should be set to 
				// the contents of the "Cookie: " header to be used in the HTTP request.
				'curl_cookie'			=> '',
				// For HTTP request using saved cookie file, this should be set to
				// the name of the file containing the cookie data.
				'curl_cookiefile'		=> __DIR__ . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR . 'cookie.dat',
				// If you want to save all internal cookies to when the request is closed, set it to true.
				'save_cookie'			=> false,
				// To save all internal cookies, this should be set to the name of the file for saving the cookie data.
				'curl_cookiejar'		=> __DIR__ . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR . 'cookie.dat',
				
				// For HTTP request using proxy, this should be set to true.
				'use_proxy'				=> false,
				// For proxy information.
				'curl_proxy'			=> '',	// Proxy host format is hostname:port, eg. 192.168.0.10:8080
				'curl_proxyuserpwd'		=> '',	// Format username:password for proxy, if required
				
				// Request encoding.
				'curl_encoding'			=> '',	// Leave blank for all supported formats, else use gzip, deflate, identity etc
			),
			$config
		);
	}
	
	/**
	 * Set CurlHttpAdapter default request setting.
	 *
	 * @param array $settings request settings.
	 * @return void value is stored to the requestSetting array class variable.
	 */
	protected function setRequestSetting($settings = array()) 
	{
		// Set default and overwrite http request settings.
		$this->requestSetting = array_merge(
			array(
				'method'	=> 'GET',	// http method, GET or POST, (PUT, UPDATE, DELETE). Default = GET
				'url'		=> '',
				'params'    => array(),
				'multipart' => false,
				'headers'   => array(),
			),
			$settings
		);
	}
	
	/**
	 * Sets the useragent for Curl request to use
	 * If '$this->config['curl_useragent']' already has a value it is used instead of one being generated.
	 *
	 * @return void value is stored to the config array class variable.
	 */
	protected function setRequestUserAgent() 
	{
		if (! empty($this->config['curl_useragent']))
			return;

		$ssl = ($this->config['curl_ssl_verifyhost'] && $this->config['curl_ssl_verifypeer'] && $this->config['use_ssl']) ? '+' : '-';
		$ua = 'CurlHttpAdapter ' . self::VERSION . $ssl . 'SSL - //github.com/joglomedia/httpadapter';
		$this->config['curl_useragent'] = $ua;
	}
	
	/**
	 * Prepares the http method for use in the base string by converting it to uppercase.
	 *
	 * @return void value is stored to the class variable '$this->requestSetting['method']'
	 */
	protected function setRequestMethod() 
	{
		$this->requestSetting['method'] = strtoupper($this->requestSetting['method']);
	}

	/**
	 * Prepares the URL for use in the base string by parsing it apart and reconstructing it.
	 *
	 * Ref: 3.4.1.2
	 *
	 * @return void value is stored to the class array variable '$this->requestSetting['url']'
	 */
	protected function setRequestUrl() 
	{
		// Parse request url.
		$parts = parse_url($this->requestSetting['url']);
		
		if ($this->config['port'] != '' && $this->config['port'] > 0) {
			$port = $this->config['port'];
		} else {
			$port = isset($parts['port']) ? $parts['port'] : false;
		}
		
		$scheme	= $parts['scheme'];
		$host	= $parts['host'];
		$path   = isset($parts['path']) ? $parts['path'] : false;

		$port or $port = ($scheme == 'https') ? '443' : '80';

		if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
			$host = "$host:$port";
		}

		// HTTP scheme and host MUST be lowercase
		$this->requestSetting['url'] = strtolower("$scheme://$host");
		// but not the path
		$this->requestSetting['url'] .= $path;
	}

	/**
	 * Prepares all parameters for the base string and request.
	 * Multipart parameters are ignored as they are not defined in the specification,
	 * all other types of parameter are encoded for compatibility with OAuth.
	 *
	 * @param array $params the parameters for the request
	 * @return void prepared values are stored in the class array variable '$this->requestSetting'
	 */
	protected function setRequestParams($encode = false) 
	{
		if (!is_array($this->requestSetting['params']) || empty($this->requestSetting['params']))
			return;

		$this->requestSetting['prepared_params'] = array();
		$prepared = &$this->requestSetting['prepared_params'];
		$prepared_pairs = array();

		$params = $this->requestSetting['params'];

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		// Encode params unless we're doing multipart.
		foreach ($params as $k => $v) {
			$k = $this->requestSetting['multipart'] ? $k : $this->safeEncode($k);

			if (is_array($v))
				$v = implode(',', $v);

			$v = $this->requestSetting['multipart'] ? $v : $this->safeEncode($v);

			$prepared[$k] = $v;
			$prepared_pairs[] = "{$k}={$v}";
		}

		// Setup params for GET/POST method handling.
		if (!empty($prepared_pairs)) {
			$content = implode('&', $prepared_pairs);

			switch ($this->requestSetting['method']) {
				case 'GET':
					$this->requestSetting['querystring'] = $content;
				break;
				case 'POST':
					$this->requestSetting['postfields'] = $this->requestSetting['multipart'] ? $prepared : $content;
				break;
				// For custom request method, eg. PUT/UPDATE/DELETE
				default:
					$this->requestSetting['postfields'] = $this->requestSetting['multipart'] ? $prepared : $content;
				break;
			}
		}
	}
	
	/**
	 * Utility function to create the request URL in the requested format.
	 * If a fully-qualified URI is provided, it will be returned.
	 * Any multi-slashes (except for the protocol) will be replaced with a single slash.
	 *
	 *
	 * @param string $endpoint the API method without extension
	 * @param string $extension the format of the response. Default json. Set to an empty string to exclude the format
	 * @return string the concatenation of the host, API version, API method and format, or $request if it begins with http
	 */
	public function setUrl($endpoint, $extension = '') 
	{
		// remove multi-slashes
		$endpoint = preg_replace('$([^:])//+$', '$1/', $endpoint);

		if (stripos($endpoint, 'http') === 0 || stripos($endpoint, '//') === 0) {
			return $endpoint;
		}

		$extension = strlen($extension) > 0 ? ".$extension" : '';
		$proto = $this->config['use_ssl'] ? 'https:/' : 'http:/';

		// trim trailing slash
		$endpoint = ltrim($endpoint, '/');

		$pos = strlen($endpoint) - strlen($extension);
		if (substr($endpoint, $pos) === $extension)
			$request = substr_replace($endpoint, '', $pos);

		return implode('/', array(
			$proto,
			$this->config['host'],
			$endpoint . $extension
		));
	}
	
	/**
	 * Encodes the string or array passed in a way compatible with OAuth.
	 * If an array is passed each array value will will be encoded.
	 *
	 * @param mixed $data the scalar or array to encode
	 * @return $data encoded in a way compatible with OAuth
	 */
	private function safeEncode($data) 
	{
		if (is_array($data)) {
			return array_map(array($this, 'safeEncode'), $data);
		} else if (is_scalar($data)) {
			return str_ireplace(
				array('+', '%7E'),
				array(' ', '~'),
				rawurlencode($data)
			);
		} else {
			return '';
		}
	}

	/**
	 * Decodes the string or array from it's URL encoded form
	 * If an array is passed each array value will will be decoded.
	 *
	 * @param mixed $data the scalar or array to decode
	 * @return string $data decoded from the URL encoded form
	 */
	private function safeDecode($data) 
	{
		if (is_array($data)) {
			return array_map(array($this, 'safeDecode'), $data);
		} else if (is_scalar($data)) {
			return rawurldecode($data);
		} else {
			return '';
		}
	}

	/**
	 * Public access to the private safe decode/encode methods
	 *
	 * @param string $text the text to transform
	 * @param string $mode the transformation mode. either encode or decode
	 * @return string $text transformed by the given $mode
	 */
	public function transformText($text, $mode='encode') {
		$mode = ucfirst($mode);
		return $this->{"safe$mode"}($text);
	}
	
	/**
	 * Make an HTTP request using this library. 
	 * This method return processRequest method.
	 *
	 * @param string $method the HTTP method being used. e.g. POST, GET, HEAD etc
	 * @param string $url the request URL without query string parameters
	 * @param array $params the request parameters as an array of key=value pairs. Default empty array
	 * @param string $useauth whether to use authentication when making the request. Default true
	 * @param string $multipart whether this request contains multipart data. Default false
	 * @param array $headers any custom headers to send with the request. Default empty array
	 * @return int the http response code for the request. 0 is returned if a connection could not be made
	 */
	public function createRequest($method='GET', $url='', $params=array(), $headers=array(), $useauth=false, $multipart=false) 
	{
		$params		= is_array($params) ? $params : array($params);
		$headers	= is_array($headers) ? $headers : array($headers);

		// Set default http request settings.
		$settings = array(
			'method'    => strtoupper($method),
			'url'       => $url,
			'params'    => $params,
			'multipart' => $multipart,
			'headers'   => $headers
		);

		$this->setRequestSetting($settings);
		$this->setRequestMethod();
		$this->setRequestUrl();
		$this->setRequestParams();
		
		return $this->processRequest();
	}
	
	/**
	 * Make a Curl request. Takes no parameters as all should have been prepared by the request method.
	 * The response data is stored in the class variable 'response' as an array containing all Curl response.
	 *
	 * @return array $response the Curl http response the request. Null is returned if a curl http connection could not be made.
	 */
	private function processRequest() 
	{
		// Set default response array.
		$this->response = array(
			'header'	=> array(),
			'body'		=> '',
			'info'		=> '',
			'error'		=> '',
		);
		
		// Check curl function.
		if (! function_exists('curl_init') || ! extension_loaded('curl')) {
			$this->response['code'] = 0;
			$this->response['error'] = 'Curl module not loaded.';
			$this->response['errno'] = 2;
			
			return null;
		}
		
		// Set curl option.
		$ch = curl_init();
		
		// Set version of http request.
		if (strtolower($this->config['curl_http_version']) == 'http 1.1') {
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		}

		// Set request method.
		switch ($this->requestSetting['method']) 
		{
			case 'GET':
				if (isset($this->requestSetting['querystring'])) {
					$this->requestSetting['url'] = $this->requestSetting['url'] . '?' . $this->requestSetting['querystring'];
				}
			break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				
				if (isset($this->requestSetting['postfields'])) {
					$postfields = $this->requestSetting['postfields'];
				} else {
					$postfields = array();
				}

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			break;
			default:
				// For custom request method, eg. PUT/UPDATE/DELETE. Ensure that your server side support for custom request.
				if (isset($this->requestSetting['postfields'])) {
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->requestSetting['method']);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestSetting['postfields']);
				}
			break;
		}
		
		// Set basic HTTP Auth.
		if ($this->config['curl_http_auth']) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $this->config['curl_http_auth']);
		}

		// Process the headers.
		if ($this->config['curl_header']) {
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
			curl_setopt($ch, CURLOPT_HEADER, $this->config['curl_header']); // default = false.
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}

		// Set curl options.
		curl_setopt_array($ch, array(
			CURLOPT_USERAGENT		=> $this->config['curl_useragent'],
			CURLOPT_CONNECTTIMEOUT	=> $this->config['curl_connecttimeout'],
			CURLOPT_TIMEOUT			=> $this->config['curl_timeout'],
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_FOLLOWLOCATION	=> $this->config['curl_followlocation'],
			CURLOPT_PROXY			=> $this->config['curl_proxy'],
			CURLOPT_ENCODING		=> $this->config['curl_encoding'],
			CURLOPT_URL				=> $this->requestSetting['url'],
			// Body response
			CURLOPT_NOBODY			=> $this->config['curl_nobody'], // default = false.
		));
		
		// Set request header.
		if (! empty($this->requestSetting['headers'])) {
			foreach ($this->requestSetting['headers'] as $k => $v) {
				$headers[] = trim($k . ': ' . $v);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		// Set cookie.
		if ($this->config['use_cookie']) {
			if (! empty($this->config['curl_cookie']))
				curl_setopt($ch, CURLOPT_COOKIE, $this->config['curl_cookie']);
			
			if (! empty($this->config['curl_cookiefile']))
				curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['curl_cookiefile']);
		}

		// Save cookie.
		if ($this->config['save_cookie'])
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['curl_cookiejar']);
		
		// Set referer.
		if (! empty($this->config['curl_referer'])) {
			curl_setopt($ch, CURLOPT_REFERER, $this->config['curl_referer']);
		} 
		else {
			curl_setopt($ch, CURLOPT_REFERER, $this->requestSetting['url']);
		}

		// Verify SSL secure connection.
		if($this->config['use_ssl']) {
			if($this->config['curl_ssl_verifypeer']) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config['curl_ssl_verifypeer']);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config['curl_ssl_verifyhost']);

				if ($this->config['curl_cainfo']) {
					curl_setopt($ch, CURLOPT_CAINFO, $this->config['curl_cainfo']);
				}

				if ($this->config['curl_capath']) {
					curl_setopt($ch, CURLOPT_CAPATH, $this->config['curl_capath']);
				}
			}
			else {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			}
		}

		// Set proxy authentication.
		if ($this->config['curl_proxyuserpwd']) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['curl_proxyuserpwd']);
		}
			
		// Set alternative port to connect.
		if (($this->config['port'] != '' && $this->config['port'] > 0)) {
			curl_setopt($ch, CURLOPT_PORT, $this->config['port']);
		}
	
	    // Process Curl request.
		$content= curl_exec($ch);
		$code	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$info	= curl_getinfo($ch);
		$error	= curl_error($ch);
		$errno	= curl_errno($ch);

		// Close Curl request.
		curl_close($ch);

		// Store the url response to class array variable 'response'.
		$this->response['code'] = $code;
		$this->response['body'] = $content;
		$this->response['info'] = $info;
		$this->response['error'] = $error;
		$this->response['errno'] = $errno;

		return $this->response;
	}
	
	/**
	 * Method to parse the returned curl http headers and store them in the class array variable.
	 *
	 * @param object $ch curl handle
	 * @param string $header the response headers
	 * @return string the length of the header
	 */
	private function getHeader($ch, $header) 
	{
		// Set response array.
		$this->response['body'] .= $header;

		list($key, $value) = array_pad(explode(':', $header, 2), 2, null);

		$key = trim($key);
		$value = trim($value);

		if (! isset($this->response['headers'][$key])) {
			$this->response['headers'][$key] = $value;
		} else {
			if (!is_array($this->response['headers'][$key])) {
				$this->response['headers'][$key] = array($this->response['headers'][$key]);
			}
			$this->response['headers'][$key][] = $value;
		}

		return strlen($header);
	}
	
	/**
	 * Utility method to fetch cookie string from header response.
	 * this doesn't really belong here, but mostly when this class is used, 
	 * i use this function method as well, so i have placed it here.
	 *
	 * @param string $headerstring
	 * @return string $cookies
	 **/
	public function getCookie($headerstring = '') 
	{	
		if (function_exists('http_parse_headers')) {
			$headers = http_parse_headers($headerstring);

			$_cookies = array();
			foreach ($headers as $key => $header) {
				if (strtolower($key) == 'set-cookie') {
					foreach ($header as $k => $value) {
						$_cookies[] = http_parse_cookie($value);
					}
				}
			}

			$__cookies = array();
			foreach ($_cookies as $row) {
				$__cookies[] = $row->cookies;
			}

			$cookies = array();
			// sort k=>v format
			foreach($__cookies as $v){
				foreach ($v as $k1 => $v1){
					$cookies[$k1]=$v1;
				}
			}
			
			return implode(';', $cookies);
		} 
		else {
			//preg_match_all("#Set-Cookie: ([^;\s]+)($|;)#", $headerstring, $matches);
			preg_match_all("#^Set-Cookie: (.*?);#sm", $headerstring, $matches);
			
			$cookies = '';
			foreach ($matches[1] as $cookie) {
				if ($cookie{0} == '=')
					continue;
				
				// Skip over "expired cookies which were causing problems; by Neerav; 4 Apr 2006
				if ((strpos($cookie, "EXPIRED") !== false) || (strpos($cookie, "GoogleAccountsLocale_session") !== false)) 
					continue;
		
				$cookies .= $cookie . "; ";
			}
			
			$cookies = substr($cookies, 0, -1);
			if (empty($cookies))
				return false;
			
			return $cookies;
		}
	}
	
	/**
	 * Restful API method helper, send GET request
	 */
	public function get($url, $params = array(), $headers = array())
	{
		$response = $this->createRequest('GET', $url, $params, $headers);
		
		return $response['body'];
	}
	
	/**
	 * Restful API method helper, send POST request
	 */
	public function post($url, $params = array(), $headers = array())
	{
		return $this->createRequest('POST', $url, $params, $headers);
	}
	
	/**
	 * Restful API method helper, send UPDATE request
	 */
	public function update($url, $params = array(), $headers = array())
	{
		return $this->createRequest('UPDATE', $url, $params, $headers);
	}

	/**
	 * Restful API method helper, send DELETE request
	 */
	public function delete($url, $params = array(), $headers = array())
	{
		return $this->createRequest('DELETE', $url, $params, $headers);
	}
	
	/**
	 * Restful API method helper, send PUT request
	 */
	public function put($url, $params = null, $headers = null)
	{
		return $this->createRequest('PUT', $url, $params, $headers);
	}
	
	/**
	 * Helper method to return adapter configuration.
	 *
	 * @return array of adapter configuration.
	 */
	public function getConfig()
	{
		return $this->config;
	}

    /**
	 * getContent interface method
	 * Get adapter response body (content).
	 *
	 * @params mix
	 * @return string response body (content).
     */
    public function getContent($method='GET', $url='', $params=array(), $headers=array(), $useauth=false, $multipart=false)
    {
		$response = $this->createRequest($method, $url, $params, $headers, $useauth, $multipart);
		return $response['body'];
	}

	/**
	 * getName interface method.
	 * Get adapter class name.
	 *
	 * @return string adapter class name.
	 */
	public function getName()
	{
		return 'curl';
	}
}
