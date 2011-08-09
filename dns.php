<?php
/**
 *
 * Rackspace DNS PHP API ...
 * @author paul
 * @copyright Original Webware Limited
 */

class RSdnsAPI {

	private $apiEndpoint;
	private $authEndpoint;
	private $serverUrl;
	private $account_id;
	private $authToken;
	private $authUser;
	private $authKey;
	private $lastResponseStatus;
	private $callbacks = array ();

	/**
	 * Timeout in seconds for an API call to respond
	 * @var integer
	 */
	const TIMEOUT = 20;

	/**
	 *
	 * rackspace api version ...
	 * @var string
	 */
	const DEFAULT_DNS_API_VERSION = '1.0';

	/**
	 *
	 * user agent ...
	 * @var string
	 */
	const USER_AGENT = 'Rackspace DNS PHP Binding - OriginalWebware.co.uk';

	/**
	 *
	 * usa auth endpoint ...
	 * @var string
	 */
	const US_AUTHURL = 'https://auth.api.rackspacecloud.com';

	/**
	 *
	 * uk auth endpoint...
	 * @var string
	 */
	const UK_AUTHURL = 'https://lon.auth.api.rackspacecloud.com';

	/**
	 *
	 * uk dns endpoint ...
	 * @var string
	 */
	const UK_DNS_ENDPOINT = 'https://lon.dns.api.rackspacecloud.com';

	/**
	 * Creates a new Rackspace Cloud Servers API object to make calls with
	 *
	 * Your API key needs to be generated using the Rackspace Cloud Management
	 * Console. You can do this under Cloud Files (not Cloud Servers).
	 *
	 * Authentication is done automatically when making the first API call
	 * using this object.
	 *
	 * @param string $user The username of the account to use
	 * @param string $key The API key to use
	 */
	public function __construct($user, $key, $auth_endpoint = self::UK_AUTHURL) {
		$this->authUser = $user;
		$this->authKey = $key;

		$this->authEndpoint = $auth_endpoint;
		$this->apiEndpoint = null;
		$this->authToken = NULL;
	}

	/**
	 *
	 * returns a list of domains ...
	 * @param int $limit show x reasults
	 * @param int $offset fetch from result x
	 */
	public function list_domains($limit = 10, $offset = 0) {
		$url = "/domains?limit=$limit&offset=$offset";

		return $this->makeApiCall ( $url );
	}

	/**
	 * list subdomains
	 * @param string|int $domainID
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function list_subdomains($domainID){
		if ($domainID == false || ! is_int ( $domainID )) {
			return false;
		}

		$url = "/domains/$domainID/subdomains";

		return $this->makeApiCall ( $url );
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $domainID
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function list_records($domainID){
		if ($domainID == false || ! is_int ( $domainID )) {
			return false;
		}

		$url = "/domains/$domainID/records";

		return $this->makeApiCall ( $url );
	}
	/**
	 * Enter description here ...
	 * @param unknown_type $domainID
	 * @param unknown_type $recordID
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function list_record_details($domainID,$recordID){
		if ($domainID == false || ! is_int ( $domainID ) || $recordID == false ) {
			return false;
		}

		$url = "/domains/$domainID/records/$recordID";

		return $this->makeApiCall ( $url );
	}
	/**
	 * Enter description here ...
	 * @param unknown_type $domainID
	 * @param unknown_type $recordID
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function delete_domain_record($domainID,$recordID){
		if ($domainID == false || ! is_int ( $domainID ) || $recordID == false ) {
			return false;
		}

		$url = "/domains/$domainID/records/$recordID";

		$call = $this->makeApiCall ( $url,null,'DELETE' );


		//@todo make callback function to cycle through registered call backs
		if (isset($call ['callbackUrl'])) {

			$this->callbacks [] = $call;
			//@todo make this a while loop with a max timeout
			sleep ( 10 );
			$url = explode ( 'status', $call ['callbackUrl'] );

			return $this->makeApiCall ( '/status' . array_pop ( $url ) );

		} else {
			return $call;
		}
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $domainID
	 * @param unknown_type $deleteSubdomains
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function delete_domain($domainID, $deleteSubdomains = true) {
		if ($domainID == false || ! is_int ( $domainID ) ) {
			return false;
		}

			$deleteSubdomains = ($deleteSubdomains == false) ? 'false' : 'true';

		$url = "/domains/$domainID?deleteSubdomains=$deleteSubdomains";

		$call = $this->makeApiCall ( $url,null,'DELETE' );


		//@todo make callback function to cycle through registered call backs
		if (isset($call ['callbackUrl'])) {

			$this->callbacks [] = $call;
			//@todo make this a while loop with a max timeout
			sleep ( 10 );
			$url = explode ( 'status', $call ['callbackUrl'] );

			return $this->makeApiCall ( '/status' . array_pop ( $url ) );

		} else {
			return $call;
		}
	}

	/**
	 *
	 * search domains for domains matching
	 *
	 * allows for searching of the avalible domains
	 * using list_domain_search("klwebconsultants") would return all TLD's
	 * @param string $domain
	 */
	public function list_domain_search($domain = false) {
		if ($domain == false) {
			return false;
		}

		$url = '/domains?name=' . rawurlencode ( $domain );

		return $this->makeApiCall ( $url );
	}

	/**
	 *
	 * list domain data by domainID ...
	 * @param int $domainID
	 * @param bool $showRecords
	 * @param bool $showSubdomains
	 */
	public function list_domain_details($domainID = false, $showRecords = false, $showSubdomains = false) {
		if ($domainID == false || ! is_int ( $domainID )) {
			return false;
		}

		$showRecords = ($showRecords == false) ? 'false' : 'true';
		$showRecords = ($showSubdomains == false) ? 'false' : 'true';

		$url = "/domains/$domainID?showRecords=$showRecords&showSubdomains=$showSubdomains";

		return $this->makeApiCall ( $url );
	}

	/**
	 * exports a domain as a BIND9 format ...
	 * @param int $domainID
	 * @return boolean|array
	 */
	public function domain_export($domainID = false) {
		if ($domainID == false || ! is_int ( $domainID )) {
			return false;
		}

		$url = "/domains/$domainID/export";

		$call = $this->makeApiCall ( $url );

		//@todo make callback function to cycle through registered call backs
		if (isset($call ['callbackUrl'])) {

			$this->callbacks [] = $call;
			//@todo make this a while loop with a max timeout
			sleep ( 10 );
			$url = explode ( 'status', $call ['callbackUrl'] );

			return $this->makeApiCall ( '/status' . array_pop ( $url ) );

		} else {
			return false;
		}

	}

	/**
	 * creates a new zone ...
	 * @param unknown_type $name
	 * @param unknown_type $email
	 * @param unknown_type $records
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function create_domain($name = false, $email = false, $records = false) {
		if (! $email || ! $name || ! is_array ( $records )) {
			return false;
		}


		$postData = array('domains' => array(array (
				'name' => $name,
				'emailAddress' => $email,
				'recordsList' => array('records' => $records) )));

		$url = '/domains';

		$call = $this->makeApiCall ( $url,$postData,'POST' );

		//@todo make callback function to cycle through registered call backs
		if (isset($call ['callbackUrl'])) {

			$this->callbacks [] = $call;
			//@todo make this a while loop with a max timeout
			sleep ( 10 );
			$url = explode ( 'status', $call ['callbackUrl'] );

			return $this->makeApiCall ( '/status' . array_pop ( $url ) );

		} else {
			return $call;
		}


	}
	/**
	 * creates a new record on a existing zone ...
	 * @param unknown_type $domainID
	 * @param unknown_type $records
	 * @return boolean|Ambigous <multitype:, NULL, mixed>
	 */
	public function create_domain_record($domainID = false, $records = false) {
		if (! $domainID|| ! is_array ( $records )) {
			return false;
		}


		$postData =  array('records' => $records) ;

		$url = "/domains/$domainID/records";

		$call = $this->makeApiCall ( $url,$postData,'POST' );

		//@todo make callback function to cycle through registered call backs
		if (isset($call ['callbackUrl'])) {

			$this->callbacks [] = $call;
			//@todo make this a while loop with a max timeout
			sleep ( 10 );
			$url = explode ( 'status', $call ['callbackUrl'] );

			return $this->makeApiCall ( '/status' . array_pop ( $url ) );

		} else {
			return $call;
		}


	}

	/**
	 * makes a array encapsulating a dns record for use with the api ...
	 * @param string $type
	 * @param string $name
	 * @param string $data
	 * @param string|int $ttl
	 * @param string|int $priority
	 * @return boolean|array
	 */
	public function create_domain_record_helper($type = false, $name = false, $data = false, $ttl = 86400, $priority = false) {

		if (! $type || ! $name || ! $data || ! is_int ( $ttl )) {
			return false;
		}

		$record = array (
				'ttl' => (string)$ttl,
				'data' => (string)$data,
				'name' => (string)$name,
				'type' => (string)strtoupper ( $type ) );

		if ($priority !== false) {
			$record ['priority'] = (string)$priority;
		}
		return $record;

	}

	/**
	 * Makes a call to an API
	 *
	 * @param string $url The relative URL to call (example: "/server")
	 * @param string $postData (Optional) The JSON string to send
	 * @param string $method (Optional) The HTTP method to use
	 * @return array The parsed response, or NULL if there was an error
	 */
	private function makeApiCall($url, $postData = NULL, $method = NULL) {
		// Authenticate if necessary
		if (! $this->isAuthenticated ()) {
			if (! $this->authenticate ()) {
				return NULL;
			}
		}

		$this->lastResponseStatus = NULL;

		$urlParts = explode ( '?', $url );

		$url = $urlParts [0] . ".json";

		if (isset ( $urlParts [1] )) {
			$url .= '?' . $urlParts [1];
		}

		$jsonUrl = $this->apiEndpoint . '/' . rawurlencode ( "v" . self::DEFAULT_DNS_API_VERSION ) . '/' . $this->account_id . $url;

		$httpHeaders = array (
				"X-Auth-Token: {$this->authToken}" );

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $jsonUrl );
		if ($postData) {
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($postData) );
			$httpHeaders [] = "Content-Type: application/json";
		}
		if ($method) {
			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, strtoupper ( $method ) );
		}
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $httpHeaders );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_USERAGENT, self::USER_AGENT );
		curl_setopt ( $ch, CURLOPT_HEADERFUNCTION, array (
				&$this,
				'parseHeader' ) );
		curl_setopt ( $ch, CURLOPT_ENCODING, 'gzip,deflate' );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, self::TIMEOUT );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );

		$jsonResponse = curl_exec ( $ch );
		curl_close ( $ch );

		//	 var_dump($jsonResponse);
		return json_decode ( $jsonResponse, TRUE );
	}

	/**
	 * Curl call back method to parse header values one by one (there will be
	 * many)
	 *
	 * @param resource $ch The Curl handler
	 * @param string $header The HTTP header line to parse
	 * @return integer The number of bytes in the header line
	 */
	private function parseHeader($ch, $header) {
		preg_match ( "/^HTTP\/1\.[01] (\d{3}) (.*)/", $header, $matches );
		if (isset ( $matches [1] )) {
			$this->lastResponseStatus = $matches [1];
		}

		return strlen ( $header );
	}

	/**
	 * Determines if authentication has been complete
	 *
	 * @return boolean TRUE if authentication is complete, FALSE if it needs to
	 * be done
	 */
	private function isAuthenticated() {
		return ($this->serverUrl && $this->authToken);
	}

	/**
	 * Authenticates with the API
	 *
	 * @return boolean TRUE if the authentication was successful
	 */
	private function authenticate() {
		$authHeaders = array (
				"X-Auth-User: {$this->authUser}",
				"X-Auth-Key: {$this->authKey}" );

		$ch = curl_init ();
		$url = $this->authEndpoint . '/' . rawurlencode ( "v" . self::DEFAULT_DNS_API_VERSION );

		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, True );
		//   curl_setopt($ch, CURLOPT_CAINFO,dirname(dirname(__FILE__)) . "/share/cacert.pem");
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_MAXREDIRS, 4 );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $authHeaders );
		curl_setopt ( $ch, CURLOPT_HEADER, true );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$response = curl_exec ( $ch );
		curl_close ( $ch );

		preg_match ( "/^HTTP\/1\.[01] (\d{3}) (.*)/", $response, $matches );
		if (isset ( $matches [1] )) {
			$this->lastResponseStatus = $matches [1];
			if ($this->lastResponseStatus == "204") {
				preg_match ( "/X-Server-Management-Url: (.*)/", $response, $matches );
				$this->serverUrl = trim ( $matches [1] );

				$account = explode ( '/', $this->serverUrl );
				$this->account_id = array_pop ( $account );
				// TODO Replace this with parsing the correct one once the Load Balancer API goes public
				$this->apiEndpoint = self::UK_DNS_ENDPOINT; // "https://ord.loadbalancers.api.rackspacecloud.com/v1.0/425464";


				preg_match ( "/X-Auth-Token: (.*)/", $response, $matches );
				$this->authToken = trim ( $matches [1] );

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Translates the HTTP response status from the last API call to a human
	 * friendly message
	 *
	 * @return string The response message from the last call
	 */
	public function getLastResponseMessage() {
		$map = array (
				"200" => "Successful informational response",
				"202" => "Successful action response",
				"203" => "Successful informational response from the cache",
				"204" => "Authentication successful",
				"400" => "Bad request (check the validity of input values)",
				"401" => "Unauthorized (check username and API key)",
				"403" => "Resize not allowed",
				"404" => "Item not found",
				"409" => "Item already exists",
				"413" => "Over API limit (check limits())",
				"415" => "Bad media type",
				"500" => "Cloud server issue",
				"503" => "API service in unavailable, or capacity is not available" );

		$status = $this->getLastResponseStatus ();
		if ($status) {
			return $map [$status];
		}

		return "UNKNOWN - Probably a timeout on the connection";
	}

	/**
	 * Gets the HTTP response status from the last API call
	 *
	 * - 200 - successful informational response
	 * - 202 - successful action response
	 * - 203 - successful informational response from the cache
	 * - 400 - bad request (possibly because the input values were invalid)
	 * - 401 - unauthorized (check username and API key)
	 * - 403 - resize not allowed
	 * - 404 - item not found
	 * - 409 - build, backup or resize in process
	 * - 413 - over API limit (check limits())
	 * - 415 - bad media type
	 * - 500 - cloud server issue
	 * - 503 - API service in unavailable, or capacity is not available
	 *
	 * @return integer The 3 digit HTTP response status, or NULL if the call had
	 * issues
	 */
	public function getLastResponseStatus() {
		return $this->lastResponseStatus;
	}


	/**
	 * Gets the API call limits for this account
	 *
	 * There are two types of limits enforced by Rackspace Cloud - rate limits
	 * and absolute limits.
	 *
	 * @return array Absolute and rate limits
	 */
	public function limits() {
		$response = $this->makeServersApiCall ( "/limits" );

		if (in_array ( $this->getLastResponseStatus (), array (
				200,
				203 ) ) && isset ( $response ['limits'] )) {
			return $response ['limits'];
		}

		return NULL;
	}





}

