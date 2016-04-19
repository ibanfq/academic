<?php
/**
 * Api helper library.
 */
class ApiHelper extends AppHelper {

/**
 * Other helpers used by FormHelper
 *
 * @var array
 * @access public
 */
	var $helpers = array('Javascript');

/**
 * Generates a JSend-compliant response: http://labs.omniti.com/labs/jsend
 *
 * ### Status
 *
 * - success - All went well, and (usually) some data was returned.
 * - fail - There was a problem with the data submitted, or some pre-condition of the API call wasn't satisfied
 * - error - An error occurred in processing the request, i.e. an exception was thrown
 *
 * @param array $data A wrapper for any data returned by the API call or for the details of why the request failed
 * @param string $status The status of the request
 * @param string|int $code A numeric code corresponding to the error, if applicable
 * @param string $message A meaningful, end-user-readable (or at the least log-worthy) message, explaining what went wrong.
 * @return string A JSON code block
 */
	function respond($data = null, $status = 'success', $code = null, $message = null) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');
    if ($status !== 'success') {
      header('HTTP/1.1 400 Bad Request');
    }
    
    $response = array('status' => $status);
    
    if (!empty($data) || $status === 'success' || $status === 'fail') {
      $response['data'] = $data;
    }
    
    if ($status === 'error') {
      $response['message'] = $message;
      
      if (!empty($code) || $code === 0) {
        $response['code'] = $code;
      }
    }
    
		return $this->Javascript->object($response);
	}
}
