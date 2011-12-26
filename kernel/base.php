<?php

/**
 * pongo PHP Framework Kernel Component
 * Package BASE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 13:59)
 * @package com.agileapes.pongo.kernel.base
 */

if (!defined("KERNEL_COMPONENT_BASE")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_BASE', "");

	define('KCE_BASE', 1000);

	class KernelException extends Exception {

		private $cause;
		private $arguments = array();

		protected function getBase() {
			return KCE_BASE;
		}

		protected function getBaseCode() {
			return 1;
		}

		public function __construct($message = "", $cause = null, $arguments = array()) {
			parent::__construct($message, $this->getBase() + $this->getBaseCode(), $cause);
			if (is_array($arguments)) {
				$this->setArguments($arguments);
			}
		}

		public function setCause($cause) {
			$this->cause = $cause;
		}

		public function getCause() {
			return $this->cause;
		}

		/**
		 * @param $arguments
		 * @return KernelException
		 */
		public function setArguments($arguments) {
			$this->arguments = $arguments;
			return $this;
		}

		public function getArguments() {
			return $this->arguments;
		}

		public function getLocalizedMessage() {
			return __($this->getMessage(), $this->getArguments());
		}

	}

	/**
	 * @param $array
	 * @param $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_get($array, $key, $default = null) {
		if (is_array($array) && array_key_exists($key, $array)) {
			return $array[$key];
		}
		return $default;
	}

	/**
	 * Returns an index hierarchy of the given string, separated by forward slashes
	 * @param $str
	 * @return array
	 */
	function array_index($str) {
		$result = array();
		$current = "";
		while (strlen($str) > 0) {
			if ($str[0] == '\\') {
				if (strlen($str) > 1 && $str[1] == '/') {
					$str = substr($str, 2);
					$current .= '/';
				} else {
					$str = substr($str, 1);
					$current .= "\\";
				}
			} else if ($str[0] == '/' && $current != "") {
				array_push($result, $current);
				$current = "";
				$str = substr($str, 1);
			} else {
				$current .= $str[0];
				$str = substr($str, 1);
			}
		}
		if ($current != "") {
			array_push($result, $current);
			return $result;
		}
		return $result;
	}

	/**
	 * Returns the value of the specified array cell accessed via hierarchy
	 * @param $array
	 * @param $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_access($array, $key, $default = null) {
		$keys = array_index($key);
		for ($i = 0; $i < count($keys); $i++) {
			$key = $keys[$i];
			if (!array_key_exists($key, $array)) {
				return $default;
			}
			if (!is_array(array_get($array, $key)) && $i < count($keys) - 1) {
				return $default;
			}
			$array = array_get($array, $key);
		}
		return $array;
	}

	/**
	 * Works just like
	 * @param $input
	 * @return bool|null
	 * @see strval
	 */
	function boolval($input) {
		if (is_bool($input)) {
			return $input;
		} else if (is_string($input)) {
			if ($input == "true" || $input == "1" || $input == "yes" || $input == "on") {
				return true;
			} else if ($input == "false" || $input == "0" || $input == "no" || $input == "off") {
				return false;
			}
		}
		return null;
	}

	/**
	 * Prints a clean version of the given string
	 * @param $html
	 * @param bool $body_only
	 * @param bool $return
	 * @return string
	 */
	function print_tidy($html, $body_only = false, $return = false) {
		$tidy = new tidy();
		$tidy->parseString($html, array('indent' => true, 'output-xhtml' => true, 'wrap' => 90, 'char-encoding' => 'utf8', 'input-encoding' => 'utf-8', 'output-encoding' => 'utf-8', 'wrap-sections' => false, 'logical-emphasis' => true, 'indent-cdata' => true, 'enclose-text' => true, 'tidy-mark' => true, 'show-body-only' => $body_only));
		$tidy->cleanRepair();
		if ($return) {
			return (string) $tidy;
		}
		echo($tidy);
		return "";
	}

	/**
	 * Writes and overrides the contents of <code>$file</code> with <code>$contents</code>
	 * @param  string $file
	 * @param  string $contents
	 * @return bool
	 */
	function file_set_contents($file, $contents) {
		$handle = @fopen($file, "w+");
		if ($handle === false) {
			return false;
		}
		$result = @fwrite($handle, $contents);
		if ($result === false) {
			return false;
		}
		$result = @fclose($handle);
		return $result !== false;
	}

	/**
	 * Prints the trace of the call so far
	 * @param $short print short version
	 */
	function print_trace($short = false) {
		try {
			throw new Exception();
		} catch (Exception $e) {
			kernel_out($short ? $e->getTraceAsString() : $e->getTrace());
		}
	}

	/**
	 * @link http://www.webmastertalkforums.com/php-functions/17175-php-hex2bin-function-convert-hexadecimal-into-binary.html
	 * @param $h
	 * @return null|string
	 */
	function hex2bin($h) {
		if (!is_string($h)) return null;
		$r = '';
		for ($a = 0; $a < strlen($h); $a += 2) {
			$r .= chr(hexdec($h{$a} . $h{($a + 1)}));
		}
		return $r;
	}

}

?>