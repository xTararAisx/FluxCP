<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          https://developers.google.com/recaptcha/docs/php
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * @copyright Copyright (c) 2014, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
  * Modifications made by Tararais:  
 *    - Updated constructor syntax to modern PHP standards  
 *    - Improved method name consistency  
 *    - Enhanced error handling and corrected array key referencing  
 *    - Simplified and optimized query string encoding  
 *    - Added timeout and error handling for HTTP requests  
 *    - Checked for the existence of 'success' and 'error-codes' keys  
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
  
class ReCaptchaResponse {  
    public $success;  
    public $errorCodes;  
}  
  
class ReCaptcha {  
    private static $_signupUrl = "https://www.google.com/recaptcha/admin";  
    private static $_siteVerifyUrl = "https://www.google.com/recaptcha/api/siteverify?";  
    private $_secret;  
    private static $_version = "php_1.0";  
  
  /**  
     * Constructor.  
     *   
     * Initializes the ReCaptcha object with the provided secret key.  
     *   
     * @param string $secret Your reCAPTCHA secret key.  
     */  
	 
    public function __construct($secret) {  
        if ($secret == null || $secret == "") {  
            die("To use reCAPTCHA you must get an API key from <a href='" . self::$_signupUrl . "'>" . self::$_signupUrl . "</a>");  
        }  
        $this->_secret = $secret;  
    }  
   /**  
     * Encode Query String.  
     *   
     * Encodes an associative array into a query string format.  
     *   
     * @param array $data The data to be encoded.  
     * @return string The encoded query string.  
     */  
    private function _encodeQS($data) {  
        return http_build_query($data);  
    }  
  /**  
     * Submit HTTP GET Request.  
     *   
     * Submits an HTTP GET request to a specified path with given data.  
     *   
     * @param string $path The URL path to send the request to.  
     * @param array $data The data to be sent as query parameters.  
     * @return string|null The response from the server or null if an error occurs.  
     */  
    private function _submitHTTPGet($path, $data) {  
	    // Create a stream context with a timeout  
        $context = stream_context_create([  
            'http' => [  
                'timeout' => 10, // Set a timeout for the request  
            ]  
        ]);  
		// Encode the data into a query string  
        $req = $this->_encodeQS($data);  
		// Send the GET request  
        $response = @file_get_contents($path . $req, false, $context); 
		
        // Return null if an error occurs   
        if ($response === FALSE) {  
            // Handle error  
            return null;  
        }  
  
        return $response;  
    }  
   /**  
     * Verify reCAPTCHA Response.  
     *   
     * Verifies the user's reCAPTCHA response by communicating with the reCAPTCHA server.  
     *   
     * @param string $remoteIp The user's IP address.  
     * @param string $response The reCAPTCHA response token from the user.  
     * @return ReCaptchaResponse The verification result.  
     */  
    public function verifyResponse($remoteIp, $response) {
        // Check if the response token is empty  		
        if ($response == null || strlen($response) == 0) {  
            $recaptchaResponse = new ReCaptchaResponse();  
            $recaptchaResponse->success = false;  
            $recaptchaResponse->errorCodes = 'missing-input';  
            return $recaptchaResponse;  
        }  
  
		// Prepare the data for the verification request  
        $getResponse = $this->_submitHTTPGet(  
            self::$_siteVerifyUrl,  
            array(  
                'secret' => $this->_secret,  
                'remoteip' => $remoteIp,  
                'v' => self::$_version,  
                'response' => $response  
            )  
        );  
        // Handle HTTP request error  
        if ($getResponse === null) {  
            $recaptchaResponse = new ReCaptchaResponse();  
            $recaptchaResponse->success = false;  
            $recaptchaResponse->errorCodes = 'http-error';  
            return $recaptchaResponse;  
        }  
  
        $answers = json_decode($getResponse, true);  
        $recaptchaResponse = new ReCaptchaResponse();  
        if (isset($answers['success']) && $answers['success'] == true) {  
            $recaptchaResponse->success = true;  
        } else {  
            $recaptchaResponse->success = false;  
            $recaptchaResponse->errorCodes = isset($answers['error-codes']) ? $answers['error-codes'] : 'unknown-error';  
        }  
        return $recaptchaResponse;  
    }  
}  
?>  

