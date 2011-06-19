<?php

/**
 * PHP5 class for interacting with the Rapleaf API
 *
 * @author Angel S. Moreno <angelxmoreno@gmail.com>
 * @version 1.0
 * @access public
 * @copyright (c) 2011 Angel S. Moreno
 * @license MIT 
 *
 */
class RapleafApiPHP5 {
    /**
     * Rapleaf API key
     *
     * @var string
     * @access protected
     * @see setApiKey()
     */
    protected $_rapleafApiKey;
    
    /**
     * Rapleaf base url
     *
     * @var string
     * @access protected
     * @see _buildUrl()
     */
    protected $_rapleafBaseUrl = 'https://personalize.rapleaf.com/v4/dr';
    
     /**
     * cURL handle used for the http requests
     *
     * @var string
     * @access protected
     */
    protected $_curlHandle;
    
    /**
     * User Agent name for the CURL session
     *
     * @var string
     * @access protected
     * @see __construct()
     */
    protected $_userAgent = 'RapleafApi/PHP5.x/1.0';
    
    /** 
    * Initializes the class by setting the key and creating the cURL handle
    *
    * @param string $api_key  Rapleaf API Key
    * @return void
    * @access public
    */
    public function __construct($api_key = null) {
        $this->setApiKey($api_key);
        $this->_curlHandle = curl_init();
        curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->_curlHandle, CURLOPT_TIMEOUT, 2.0);
        curl_setopt($this->_curlHandle, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($this->_curlHandle, CURLOPT_USERAGENT, $this->_userAgent);
    }

    /**
     * Takes an e-mail and returns an array of attributes
     * If the hash_email option is set to true, then the email will be hashed via sha1 before it's sent to Rapleaf
     *
     * @param string $email a valid email address
     * @param boolean $hash_email whether or not to hash the email prior to sending to the Rapleaf server
     * @return array
     * @access public
     */
    public function query_by_email($email, $hash_email = false) {
        $email = strtolower($email);
        if ($hash_email === true) {
            $sha1_email = sha1($email);
            return $this->query_by_sha1($sha1_email);
        } else {
            $request = array('email' => $email);
            return $this->_get_json_response($request);
        }
    }

    /**
     * Takes an md5 e-mail and returns an array of attributes
     *
     * @param string $md5_email a valid md5 of a valid email address
     * @return array
     * @access public
     */
    public function query_by_md5($md5_email) {
        $request = array('md5_email' => $md5_email);
        return $this->_get_json_response($request);
    }

    /**
     * Takes an sha1 e-mail and returns an array of attributes
     *
     * @param string $sha1_email a valid sha1 of a valid email address
     * @return array
     * @access public
     */
    public function query_by_sha1($sha1_email) {
        $request = array('sha1_email' => $sha1_email);
        return $this->_get_json_response($request);
    }

    /**
     * Takes first name, last name, and postal (street, city, and state acronym),
     * and returns an array of attributes
     * Though not necessary, adding an e-mail increases hit rate
     *
     * @param string $first_name
     * @param string $last_name
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string $email
     * @return array
     * @access public
     */
    public function query_by_name_and_postal($first_name, $last_name, $street, $city, $state, $email = null) {
        $request = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'street' => $street,
            'city' => $city,
            'state' => $state,
        );
        if ($email) {
            $request['email'] = strtolower($email);
        }
        return $this->_get_json_response($request);
    }

    /**
     * Takes first name, last name, and zip4 code (5-digit zip 
     * and 4-digit extension separated by a dash as a string),
     * and returns a hash which maps attribute fields onto attributes
     * Though not necessary, adding an e-mail increases hit rate
     *
     * @param string $first_name
     * @param string $last_name
     * @param string $zip4
     * @param string $email
     * @return array
     * @access public
     */
    public function query_by_name_and_zip($first_name, $last_name, $zip4, $email = null) {
        $request = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'zip4' => $zip4,
        );
        if ($email) {
            $request['email'] = strtolower($email);
        }
        return $this->_get_json_response($request);
    }

    /**
     * Sets the Rapleaf API Key and throws a fatal error if not set
     *
     * @param string $api_key
     * @return void
     * @access protected
     */
    protected function setApiKey($api_key) {
        $this->_rapleafApiKey = $api_key;
    }

    /**
     * Sends a request to the Rapleaf API server
     * Note that a notice is triggered an HTTP response code
     * other than 200 is sent back. In this case, both the error code
     * the error code and error body are printed out and False is returned.
     * This seemed more practical when going through dozens or thousands of
     * emails and not wanting the entire script to fail.
     * 
     * @param array $request
     * @return mix array on success, Boolean False on failure
     * @access protected
     */
    protected function _get_json_response(array $request) {
        $url = $this->_buildUrl($request);
        curl_setopt($this->_curlHandle, CURLOPT_URL, $url);
        $json_string = curl_exec($this->_curlHandle);
        $response_code = curl_getinfo($this->_curlHandle, CURLINFO_HTTP_CODE);

        if ($response_code < 200 || $response_code >= 300) {
            trigger_error("Error Code: " . $response_code . "\nError Body: " . $json_string, 8);
            return false;
        } else {
            $response = json_decode($json_string, TRUE);
            return $response;
        }
    }

    /**
     * Builds the URL used for the request to the Rapleaf API server
     *
     * @param array $request
     * @return string
     * @access protected
     */
    protected function _buildUrl($request) {

        $requestUrlEncoded = array_map('urlencode', $request);
        $requestArray = array_merge($requestUrlEncoded, array(
            'api_key' => $this->_rapleafApiKey
                ));
        if (isset($request['email'])) {
            $requestArray['email'] = $request['email'];
        }
        $url = $this->_rapleafBaseUrl;
        $url .= '?';
        $url .= http_build_query($requestArray);
        echo "\n{$url}\n";
        return $url;
    }
    
    /**
     * Closes cURL Handle to free up system resources
     * 
     * @return void
     * @access protected
     */
    protected function __destruct() {
        curl_close($this->_curlHandle);
    }
}
?>