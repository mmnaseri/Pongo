<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 3, 2010
 * Time: 12:16:33 PM
 * @package com.bowfin.lib.remote
 */
if (!defined("COM_BOWFIN_LIB_REMOTE")) {

	define("COM_BOWFIN_LIB_REMOTE", "Bowfin Remote Connection Library");

	/**
	 * Constants
	 */

	define("REMOTE_HTTP_LENGTH_KEY", 'Content-Length');
	define("REMOTE_DEFAULT_AGENT", "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.3) Gecko/20060426 Firefox/1.5.0.3");

	/**
	 * Stores the contents of given remote file locally
	 * @param string $url
	 * @param string $local
	 * @return bool
	 */
	function remote_get($url, $local) {
		$url = remote_parse_url($url);
		$local = @fopen($local, "w");
		if ($local === false) {
			trigger_error("Local file could not be created.", E_USER_ERROR);
			return false;
		}
		$handle = remote_get_connection($url);
		if ($handle === false) {
			trigger_error("Connection failed.", E_USER_WARNING);
			return false;
		}
		if (!remote_request_url($handle, $url)) {
			trigger_error("HTTP headers were not sent successfully.", E_USER_WARNING);
		}
		$headers = remote_get_http_headers($handle, $url, true);
		$length = array_key_exists(REMOTE_HTTP_LENGTH_KEY, $headers) ? intval($headers['Content-Length']) : -1;
		$buffer_size = 1024 * 1024;
		$size = 0;
		while (true) {
			if ($length > 0) {
				if ($size > $length - $buffer_size) {
					$buffer_size = $length - $size;
				}
			}
			if ($buffer_size == 0) {
				break;
			}
			if (feof($handle)) {
				break;
			}
			set_time_limit(0);
			$buffer = fread($handle, $buffer_size);
			$size += strlen($buffer);
			fwrite($local, $buffer);
		}
		fclose($handle);
		fclose($local);
		return true;
	}

	/**
	 * Creates an HTTP request string
	 * @param string $agent
	 * @param array $url
	 * @return string
	 */
	function remote_generate_http_request($url, $agent = REMOTE_DEFAULT_AGENT) {
		$request = "GET " . $url['path'] . " HTTP/1.0\r\n" . "Host: " . $url['host'] . "\r\n" . "User-Agent: " . $agent . "\r\n" . "Accept: */*\r\n" . "Accept-Language: en-us,en;q=0.5\r\n" . "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7" . "Keep-Alive: 300\r\n" . "Connection: close\r\n" . "Referer: " . $url['scheme'] . "://" . $url['host'] . "\r\n\r\n";
		return $request;
	}

	/**
	 * Requests a URL through <code>$handle</code>
	 * @param int $handle
	 * @param array $url
	 * @return boolean
	 */
	function remote_request_url(&$handle, $url) {
		return fwrite($handle, remote_generate_http_request($url)) !== false;
	}

	/**
	 * Opens a connection to remote host
	 * @param array $url
	 * @param int $errno
	 * @param string $errstr
	 * @param int $timeout
	 * @return int
	 */
	function remote_get_connection($url, &$errno = null, &$errstr = null, $timeout = 10) {
		return @fsockopen($url['host'], $url['port'], $errno, $errstr, $timeout);
	}

	/**
	 * Parses the received HTTP headers and returns them as a parameterized array
	 * @param string $headers
	 * @return array
	 */
	function remote_parse_header($headers) {
		$headers = explode("\r\n", $headers);
		$result = array();
		if (count($headers) > 0) {
			$result["Http-Property"] = $headers[0];
		}
		for ($i = 1; $i < count($headers); $i++) {
			if (strpos($headers[$i], ":") === false) {
				continue;
			}
			$key = substr($headers[$i], 0, strpos($headers[$i], ":"));
			$value = substr($headers[$i], strpos($headers[$i], ":") + 1);
			$result[trim($key)] = trim($value);
		}
		return $result;
	}

	/**
	 * Extracts status code from HTTP headers
	 * @param array $headers
	 * @return string
	 */
	function remote_http_status($headers) {
		if (!is_array($headers)) {
			return "200";
		}
		if (!array_key_exists('Http-Property', $headers)) {
			return "200";
		}
		$result = $headers['Http-Property'];
		$result = explode(" ", $result);
		$result = $result[1];
		$result = intval($result);
		return strval($result);
	}

	/**
	 * Translates HTTP status codes
	 * @param string $code
	 * @return string
	 */
	function remote_http_status_name($code) {
		if (strlen($code) == 0) {
			return "unspecified";
		}
		if ($code[0] == "1") {
			return "acknowledged";
		}
		if ($code[0] == "2") {
			return "succeeded";
		}
		if ($code[0] == "3") {
			return "redirected";
		}
		if ($code[0] == "4") {
			return "failed";
		}
		if ($code[0] == "5") {
			return "error";
		}
		return "unknown";
	}

	/**
	 * Parameterizes given URL into an array
	 * @param string $url
	 * @return array
	 */
	function remote_parse_url($url, $oldurl = null) {
		$url = parse_url($url);
		if (!array_key_exists('host', $url)) {
			if ($oldurl == null || !is_array($oldurl) || !array_key_exists('host', $oldurl)) {
				trigger_error("Invalid url: no host name", E_USER_WARNING);
				return false;
			}
			$url['host'] = $oldurl['host'];
		}
		if (!array_key_exists('path', $url)) {
			$url['path'] = "/";
		}
		if (array_key_exists('query', $url)) {
			$url['path'] .= "?" . $url['query'];
		}
		if (!array_key_exists('port', $url)) {
			$url['port'] = "80";
		}
		return $url;
	}

	/**
	 * Creates a string URL from given parameters
	 * @param array $url
	 * @return string
	 */
	function remote_create_url($url) {
		$result = "";
		$result .= array_key_exists('scheme', $url) ? $url['scheme'] . "://" : "";
		$result .= array_key_exists('host', $url) ? $url['host'] : "";
		$result .= array_key_exists('path', $url) ? $url['path'] : "";
		$result .= array_key_exists('query', $url) ? $url['query'] : "";
		return $result;
	}

	/**
	 * Queries the remote server for a concrete HTTP response
	 * @param int $handle
	 * @param bool $recursive
	 * @return array
	 */
	function remote_get_http_headers(&$handle, &$url, $recursive = false) {
		$headers = "";
		while (true) {
			$line = fgets($handle, 1024);
			if (trim($line) == "") {
				break;
			}
			$headers .= $line;
		}
		$headers = remote_parse_header($headers);
		if ($recursive) {
			$status = remote_http_status_name(remote_http_status($headers));
			if ($status == "redirected" && array_key_exists('Location', $headers)) {
				$newurl = $headers['Location'];
				$url = remote_parse_url($newurl, $url);
				$new_handle = remote_get_connection($url);
				if ($new_handle === false) {
					trigger_error("Could not stablish connection to " . $url['host'], E_USER_WARNING);
				} else {
					$handle = $new_handle;
					remote_request_url($handle, $url);
					$headers = remote_get_http_headers($handle, $url, true);
				}
			}
		}
		return $headers;
	}

}
?>