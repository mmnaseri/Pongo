<?php
/**
 * pongo PHP Framework Kernel Component
 * Package INTERNATIONALIZATION
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 19:13)
 * @package com.agileapes.pongo.kernel.i18n
 */
if (!defined("KERNEL_COMPONENT_INTERNATIONALIZATION")) {

	define("KERNEL_COMPONENT_INTERNATIONALIZATION", "KERNEL_COMPONENT_INTERNATIONALIZATION");

	/**
	 * Constants
	 */

	define("GLOBAL_CONTEXT_I18N_DEFAULTS", "i18n_defaults");
	define("GLOBAL_CONTEXT_I18N_REGIONS", "i18n_regions");
	define("GLOBAL_CONTEXT_I18N_CALLBACKS", "i18n_callbacks");

	//Calendar Actions
	define("I18N_CALENDAR_MONTH_NAMES", "list month names");
	define("I18N_CALENDAR_WEEKDAY_NAMES", "list weekday names");
	define("I18N_CALENDAR_PARAMETERS", "list date parameters");
	define("I18N_CALENDAR_TO_GREGORIAN", "to gregorian");
	define("I18N_CALENDAR_FROM_GREGORIAN", "from gregorian");
	define("I18N_CALENDAR_IS_LEAP", "leap");

	globals_context_register(GLOBAL_CONTEXT_I18N_REGIONS);
	globals_context_register(GLOBAL_CONTEXT_I18N_DEFAULTS);
	globals_context_register(GLOBAL_CONTEXT_I18N_CALLBACKS);
	/**
	 * @param string $option
	 * @param mixed $value
	 * @return void
	 */
	function i18n_set_option($option, $value) {
		globals_set(GLOBAL_CONTEXT_I18N_DEFAULTS, $option, $value);
	}

	/**
	 * @param string $option
	 * @param mixed $default
	 * @return mixed
	 */
	function i18n_get_option($option, $default = "") {
		return globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, $option, $default);
	}

	/**
	 * Initializes internationalization
	 * @return void
	 */
	function i18n_initialize() {
		if (globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'regions', null) === null) {
			i18n_defaults_regions_location(dirname(__FILE__) . "/regions");
		}
		if (globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'root', null) === null) {
			i18n_defaults_root(dirname(__FILE__));
		}
		i18n_region_set('en', 'US');
	}

	/**
	 * Finalizes internationalization
	 */
	function i18n_finalize() {
	}

	/**
	 * Registers callback for various actions
	 * @param  string $action
	 * @param  string|Closure $callback
	 * @return void
	 */
	function i18n_register_callback($action, $callback) {
		globals_set(GLOBAL_CONTEXT_I18N_CALLBACKS, $action, $callback);
	}

	/**
	 * Unregisters callbacks from $action
	 * @param  string $action
	 * @return void
	 */
	function i18n_unregister_callback($action) {
		globals_set(GLOBAL_CONTEXT_I18N_CALLBACKS, $action, null);
	}

	/**
	 * Calls registered callback for $action
	 * @param  string $action
	 * @param  mixed $arguments
	 * @return void
	 */
	function i18n_call($action, &$arguments) {
		$callback = globals_get(GLOBAL_CONTEXT_I18N_CALLBACKS, $action, null);
		if ($callback === null) {
			return;
		}
		if ((is_string($callback) && !function_exists($callback)) && !is_object($callback)) {
			return;
		}
		$callback($action, $arguments);
	}

	/**
	 * Generates a simple hash for a string
	 * @param string $string
	 * @return int
	 */
	function i18n_strings_generate_id($string) {
		$result = 0;
		for ($i = 0; $i < strlen($string); $i++) {
			$result += ($i + 1) * ord($string[$i]);
		}
		return $result;
	}

	/**
	 * Finds all .lang.xml files from the 'root' specified using i18n_s
	 * @param string $location
	 * @return array
	 */
	function i18n_strings_find($location = "") {
		$default = false;
		if (!empty($location) && $location[strlen($location) - 1] == "/") {
			$location = substr($location, 0, strlen($location) - 1);
		}
		if (empty($location)) {
			$location = globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'root', null);
			$default = true;
		}
		if (!is_dir($location)) {
			trigger_error("Specified path is not a directory ($location)", E_USER_ERROR);
		}
		$result = array();
		$dir = opendir($location);
		while ($file = readdir($dir)) {
			if ($file[0] == ".") {
				continue;
			}
			if (is_dir($location . "/" . $file)) {
				$exploration = i18n_strings_find($location . "/" . $file);
				if (is_array($exploration) && count($exploration) > 0) {
					for ($i = 0; $i < count($exploration); $i++) {
						array_push($result, $exploration[$i]);
					}
				}
			} else if (preg_match('/^.+\.lang\.xml$/mi', $file)) {
				array_push($result, $location . "/" . $file);
			}
		}
		closedir($dir);
		sort($result);
		if ($default) {
			globals_set(GLOBAL_CONTEXT_I18N_DEFAULTS, 'strings', $result);
		}
		return $result;
	}

	/**
	 * Loads strings from read string files into the database for fast-fetch
	 * @return void
	 */
	function i18n_strings_load() {
		$files = globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'strings', null);
		if ($files === null) {
			trigger_error("No strings to load", E_USER_ERROR);
		}
		for ($i = 0; $i < count($files); $i++) {
			$node = xmlnode_from_file($files[$i]);
			$region = xmlnode_path($node, 'strings//$attributes(language|country|context|score)');
			$region = $region[0];
			if (!i18n_region_loaded($region['@language'], $region['@country'])) {
				i18n_region_load($region['@language'], $region['@country']);
			}
			$values = xmlnode_path($node, 'string//value');
			$translations = xmlnode_path($node, 'string//translation');
			for ($j = 0; $j < min(count($values), count($translations)); $j++) {
				$values[$j] = i18n_strings_unify(xmlnode_to_string($values[$j], 0, true));
				$key = "i18n:" . $region['@language'] . "_" . $region['@country'] . "{" . (array_key_exists('@context', $region) ? $region['@context'] : "") . "}=" . $values[$j];
				cache_set($key, xmlnode_to_string($translations[$j]));
			}
		}
	}

	/**
	 * Unloads all internationalization strings
	 */
	function i18n_strings_unload() {
		$keys = cache_keys("i18n:*");
		for ($i = 0; $i < count($keys); $i++) {
			cache_delete($keys[$i]);
		}
	}

	/**
	 * Creates a unified version of the string
	 * @param  $string
	 * @return mixed|string
	 */
	function i18n_strings_unify($string) {
		$string = trim(strtolower($string));
		$string = preg_replace("/<[^>]+>/mi", "", $string);
		$string = preg_replace('/,|\.|\&|\^|\*|\(|\)\'"`/mi', " ", $string);
		$string = preg_replace('/\s+/mi', " ", $string);
		return $string;
	}

	/**
	 * Translates the string with specified parameters
	 * @param string $string
	 * @param array $arguments
	 * @param string $language
	 * @param string $country
	 * @param bool $no_localization
	 * @return mixed|string
	 */
	function i18n_translate($string, $arguments = array(), $language = "", $country = "", $no_localization = false) {
		$context = i18n_get_option('context', '');
		if (!is_array($arguments)) {
			$arguments = array($arguments);
		}
		$defaults = i18n_region_get();
		if ($language == "") {
			if ($defaults !== null) {
				$language = $defaults['language'];
			} else {
				trigger_error("No default region set.", E_USER_ERROR);
			}
		}
		if ($country == "") {
			if ($defaults !== null) {
				$country = $defaults['country'];
			} else {
				trigger_error("No default region set.", E_USER_ERROR);
			}
		}
		$result = null;
		$unified = i18n_strings_unify($string);
		$pattern = "i18n:" . $language . "_" . $country . "{*}=" . $unified;
		$keys = cache_keys($pattern);
		if (count($keys) == 0) {
			$result = $string;
		}
		if ($result === null) {
			for ($i = 0; $i < count($keys); $i++) {
				preg_match("/^i18n:[a-z]{2}_[A-Z]{2}\\{([^\\}]*)\\}/msi", $keys[$i], $matches);
				if ($matches[1] == $context) {
					$result = cache_get($keys[$i]);
				}
			}
		}
		if ($result === null) {
			$result = count($keys) > 0 ? cache_get($keys[0]) : $string;
		}
		$result = strings_vformat($result, $arguments);
		if (!$no_localization) {
			$result = i18n_localize_string($result);
		}
		return $result;
	}

	/**
	 * @param string $location
	 * @return void
	 */
	function i18n_defaults_regions_location($location) {
		if (strlen($location) > 0 && $location[strlen($location) - 1] == '/') {
			$location = substr($location, 0, strlen($location) - 1);
		}
		globals_set(GLOBAL_CONTEXT_I18N_DEFAULTS, 'regions', $location);
	}

	/**
	 * @param string $path
	 * @return void
	 */
	function i18n_defaults_root($path) {
		if (!is_dir($path)) {
			trigger_error("Specified path is not a directory ($path)", E_USER_ERROR);
		}
		globals_set(GLOBAL_CONTEXT_I18N_DEFAULTS, 'root', $path);
	}

	/**
	 * Checks if a specified region has been loaded
	 * @param string $language
	 * @param string $country
	 * @return bool
	 */
	function i18n_region_loaded($language, $country) {
		$region = globals_get(GLOBAL_CONTEXT_I18N_REGIONS, $language . "_" . $country, null);
		return $region !== null;
	}

	/**
	 * Loads the region data for $language_$country
	 * @param string $language
	 * @param string $country
	 * @param bool $reload
	 * @return bool
	 */
	function i18n_region_load($language, $country, $reload = false) {
		if (!$reload && i18n_region_loaded($language, $country)) {
			return globals_get(GLOBAL_CONTEXT_I18N_REGIONS, $language . "_" . $country, null);
		}
		$path = i18n_region_path($language, $country);
		if ($path === false) {
			trigger_error("No such region exists (" . $language . "_$country)");
		}
		$nodes = xmlnode_from_file($path);
		$nodes = xmlnode_to_array($nodes, true, function ($element) {
			return $element[0] != '@';
		});
		$nodes = $nodes['region'];
		$nodes = xmlnode_flatten($nodes);
		globals_set(GLOBAL_CONTEXT_I18N_REGIONS, $language . "_" . $country, $nodes);
		preg_match("/^(.*)\\/[^\\/]+$/mi", __FILE__, $matches);
		$path = $matches[1] . "/calendars/" . $nodes['calendar'] . ".php";
		if (!file_exists($path)) {
			trigger_error("Could not find calendar file ($path)", E_USER_ERROR);
		}
		/** @noinspection PhpIncludeInspection */
		require_once($path);
		return true;
	}

	/**
	 * Returns a list of all available regions
	 * @return array
	 */
	function i18n_region_list() {
		$list = array();
		$dir = opendir(globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'regions'));
		while ($region = readdir($dir)) {
			if ($region[0] == '.' || strlen($region) != 9 || substr($region, strlen($region) - 4) != '.xml') {
				continue;
			}
			$region = substr($region, 0, strlen($region) - 4);
			$region = explode('_', $region);
			array_push($list, array('language' => $region[0], 'country' => $region[1], 'name' => $region[0] . "_" . $region[1]));
			i18n_region_load($list[count($list) - 1]['language'], $list[count($list) - 1]['country']);
		}
		closedir($dir);
		return $list;
	}

	function i18n_region_path($language, $country) {
		if (!preg_match('/^[a-z][a-z]$/mi', $language)) {
			trigger_error("Bad language code ($language)", E_USER_ERROR);
		}
		if (!preg_match('/^[A-Z][A-Z]$/mi', $country)) {
			trigger_error("Bad country code ($country)", E_USER_ERROR);
		}
		$region = $language . "_" . $country . ".xml";
		$path = globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'regions') . '/' . $region;
		if (!file_exists($path)) {
			return false;
		} else {
			return $path;
		}
	}

	/**
	 * Returns the requested meta-data $key for region
	 * @param  string $language
	 * @param  string $country
	 * @param  string $key
	 * @return string
	 */
	function i18n_region_meta($language, $country, $key = null) {
		if (!i18n_region_loaded($language, $country)) {
			trigger_error("Region data unavailable ($language, $country)", E_USER_ERROR);
		}
		$region = i18n_region_load($language, $country);
		if ($key === null) {
			return $region;
		}
		$value = $region[$key];
		//        actions_do('i18n.meta.' . $language . "_" . $country . "." . $key, $value);
		return $value;
	}

	/**
	 * Sets the active region
	 * @param string $language
	 * @param string $country
	 * @return void
	 */
	function i18n_region_set($language, $country) {
		if (!i18n_region_loaded($language, $country)) {
			i18n_region_load($language, $country);
		}
		globals_set(GLOBAL_CONTEXT_I18N_DEFAULTS, 'region', array('language' => $language, 'country' => $country));
		date_default_timezone_set(i18n_meta('timezone'));
	}

	function i18n_region_set_by_name($region) {
		$result = explode("_", $region);
		if (count($result) != 2) {
			trigger_error("Invalid region name specified: " . $region, E_USER_ERROR);
		}
		i18n_region_set($result[0], $result[1]);
	}

	/**
	 * Returns the active region
	 * @param string $key
	 * @param string $default
	 * @return array
	 */
	function i18n_region_get($key = null, $default = "") {
		$region = globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'region');
		if ($key == null) {
			return $region;
		}
		if (array_key_exists($key, $region)) {
			return $region[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Returns '$key' meta-data for the active region
	 * @param string $key
	 * @param string $language
	 * @param string $country
	 * @return string
	 */
	function i18n_meta($key = null, $language = "", $country = "") {
		if (!empty($language) && empty($country)) {
			$language = explode('_', $language);
			$country = $language[1];
			$language = $language[0];
		}
		$defaults = i18n_region_get();
		if ($language == "") {
			if ($defaults !== null) {
				$language = $defaults['language'];
			} else {
				trigger_error("No default region set.", E_USER_ERROR);
			}
		}
		if ($country == "") {
			if ($defaults !== null) {
				$country = $defaults['country'];
			} else {
				trigger_error("No default region set.", E_USER_ERROR);
			}
		}
		return i18n_region_meta($language, $country, $key);
	}

	/**
	 * Converts the $currency to active region currency
	 * @param float $currency
	 * @return float
	 */
	function i18n_currency_to_native($currency) {
		return floatval(i18n_meta('currency/rate')) * floatval($currency);
	}

	/**
	 * Converts the native region currency to system default
	 * @param float $currency
	 * @return float
	 */
	function i18n_currency_from_native($currency) {
		return floatval($currency) / floatval(i18n_meta('currency/rate'));
	}

	/**
	 * Localizes string by converting numbers
	 * @param string|number $number
	 * @return string
	 */
	function i18n_localize_string($number) {
		$number = strval($number);
		$number = htmlspecialchars_decode($number);
		$number = str_replace("0", i18n_meta('numerics/digits/zero'), $number);
		$number = str_replace("1", i18n_meta('numerics/digits/one'), $number);
		$number = str_replace("2", i18n_meta('numerics/digits/two'), $number);
		$number = str_replace("3", i18n_meta('numerics/digits/three'), $number);
		$number = str_replace("4", i18n_meta('numerics/digits/four'), $number);
		$number = str_replace("5", i18n_meta('numerics/digits/five'), $number);
		$number = str_replace("6", i18n_meta('numerics/digits/six'), $number);
		$number = str_replace("7", i18n_meta('numerics/digits/seven'), $number);
		$number = str_replace("8", i18n_meta('numerics/digits/eight'), $number);
		$number = str_replace("9", i18n_meta('numerics/digits/nine'), $number);
		$number = str_replace("%", i18n_meta('numerics/symbols/percent'), $number);
		$number = str_replace("(", i18n_meta('numerics/symbols/openParenthesis'), $number);
		$number = str_replace(")", i18n_meta('numerics/symbols/closeParenthesis'), $number);
		$number = str_replace(";", i18n_meta('numerics/symbols/semiColon'), $number);
		$number = preg_replace("/\\&(\\S+)" . i18n_meta('numerics/symbols/semiColon') . "/msi", '&\1;', $number);
		return $number;
	}

	/**
	 * Decodes a local string to form meaningful digits
	 * @param $number
	 * @return mixed|string
	 */
	function i18n_digitize($number) {
		$number = strval($number);
		$number = htmlspecialchars_decode($number);
		$number = str_replace(i18n_meta('numerics/digits/zero'), "0", $number);
		$number = str_replace(i18n_meta('numerics/digits/one'), "1", $number);
		$number = str_replace(i18n_meta('numerics/digits/two'), "2", $number);
		$number = str_replace(i18n_meta('numerics/digits/three'), "3", $number);
		$number = str_replace(i18n_meta('numerics/digits/four'), "4", $number);
		$number = str_replace(i18n_meta('numerics/digits/five'), "5", $number);
		$number = str_replace(i18n_meta('numerics/digits/six'), "6", $number);
		$number = str_replace(i18n_meta('numerics/digits/seven'), "7", $number);
		$number = str_replace(i18n_meta('numerics/digits/eight'), "8", $number);
		$number = str_replace(i18n_meta('numerics/digits/nine'), "9", $number);
		return $number;
	}

	/**
	 * Formats the currency string using pattern
	 * @param string $pattern
	 * @param int $currency
	 * @return mixed|string
	 * Pattern arguments:
	 * N: the native value for $currency
	 * S: Separated value for $currency in native format
	 * $: Currency sign
	 * C: Currency unit name
	 */
	function i18n_currency_str($pattern, $currency = 0) {
		$value = strval(i18n_currency_to_native($currency));
		$separated = "";
		for ($i = strlen($value) - 1; $i >= 0; $i--) {
			$separated = $value[$i] . $separated;
			if ($i > 0 && (strlen($value) - $i) % 3 == 0) {
				$separated = i18n_meta('currency/separator') . $separated;
			}
		}
		$str = str_replace("N", $value, $pattern);
		$str = str_replace("S", $separated, $str);
		$str = str_replace("$", __(i18n_meta('currency/sign')), $str);
		if (intval($currency) == 1) {
			$str = str_replace("C", __(i18n_meta('currency/name/singular')), $str);
		} else {
			$str = str_replace("C", __(i18n_meta('currency/name/plural')), $str);
		}
		$str = i18n_localize_string($str);
		return $str;
	}

	/**
	 * Localizes XML tree
	 * <b>Note:</b> ignores anything that has a class attribute containing "no-localization"
	 * @param array $tree
	 * @return bool
	 */
	function i18n_localize_tree(&$tree) {
		if (is_array($tree) && !array_key_exists('name', $tree)) {
			for ($i = 0; $i < count($tree); $i++) {
				i18n_localize_tree($tree[$i]);
			}
			return;
		}
		xml_traverse($tree, function (&$tag) {
			if (xml_has_attribute($tag, 'class')) {
				$class = xml_get_attribute($tag, 'class');
				$class = explode(" ", $class);
				for ($i = 0; $i < count($class); $i++) {
					if ($class[$i] == "no-localization") {
						return false;
					}
				}
			}
			$node_name = xml_get_node_name($tag);
			if ($node_name[0] == "#") {
				$tag['value'] = trim(i18n_localize_string($tag['value']));
			}
			return true;
		});
	}

	/**
	 * Calls to active calendar handler with $arguments for $action
	 * @param  string $action
	 * @param  array $arguments
	 * @param null $region
	 * @return mixed
	 */
	function i18n_calendar_call($action, $arguments = null, $region = null) {
		if ($region === null) {
			$region = globals_get(GLOBAL_CONTEXT_I18N_DEFAULTS, 'region');
			$region = "$region[language]_$region[country]";
		}
		$locale = explode('_', $region);
		$calendar = i18n_meta('calendar', $locale[0], $locale[1]);
		if ($calendar === null) {
			trigger_error("No calendars registered for this region ($region)");
		}
		if (!function_exists('calendar_' . $calendar)) {
			trigger_error("No calendar handlers found for calendar `$calendar`", E_USER_ERROR);
		}
		$calendar = "calendar_" . $calendar;
		if ($action != I18N_CALENDAR_MONTH_NAMES && $action != I18N_CALENDAR_PARAMETERS && $action != I18N_CALENDAR_WEEKDAY_NAMES && $action != I18N_CALENDAR_TO_GREGORIAN && $action != I18N_CALENDAR_FROM_GREGORIAN && $action != I18N_CALENDAR_IS_LEAP
		) {
			trigger_error("Unknown calendar action requested ($action)");
		}
		return $calendar($action, $arguments);
	}

	/**
	 * Returns a timestamp, from the given local date
	 * @param $year
	 * @param $month
	 * @param $day
	 * @return int
	 */
	function i18n_calendar_timestamp($year, $month, $day) {
		$date = i18n_calendar_from_native($year, $month, $day);
		return strtotime(strings_vformat('%year-%month-%day', $date));
	}

	/**
	 * Returns the Gregorian equivalent of the given local date
	 * @param $year
	 * @param $month
	 * @param $day
	 * @return mixed
	 */
	function i18n_calendar_from_native($year, $month, $day) {
		return i18n_calendar_call(I18N_CALENDAR_TO_GREGORIAN, array('year' => $year, 'month' => $month, 'day' => $day));
	}

	/**
	 * Latin names for weekday names
	 * @return array
	 */
	function i18n_calendar_weekday_names() {
		return i18n_calendar_call(I18N_CALENDAR_WEEKDAY_NAMES);
	}

	/**
	 * Latin names for weekday names
	 * @return array
	 */
	function i18n_calendar_month_names() {
		return i18n_calendar_call(I18N_CALENDAR_MONTH_NAMES);
	}

	/**
	 * Formats string date according to "date()" function standard.
	 * @see date
	 * @param  string $format
	 * @param  int $timestamp
	 * @param array $args
	 * @return string
	 */
	function i18n_calendar_date($format, $timestamp = null, $args = array()) {
		if ($timestamp === null) {
			$timestamp = time();
		}
		$parameters = i18n_calendar_call(I18N_CALENDAR_PARAMETERS, array('timestamp' => $timestamp));
		$format = str_replace("c", "Y-m-d H:i:sP", $format);
		$format = str_replace("r", "D, j M Y H:i:s O", $format);
		$result = "";
		for ($i = 0; $i < strlen($format); $i++) {
			if (array_key_exists($format[$i], $parameters)) {
				$result .= i18n_translate($parameters[$format[$i]], $args, "", "");
			} else {
				$result .= $format[$i];
			}
		}
		return $result;
	}

	/**
	 * Alias functions
	 */

	/**
	 * Uses active region
	 * @alias i18n_translate
	 * @param string $string
	 * @param array $arguments
	 * @return mixed|string
	 */
	function __($string, $arguments = array()) {
		return i18n_translate($string, $arguments, "", "");
	}

	/**
	 * Echos the output of i18n_translate
	 * @param string $string
	 * @param array $arguments
	 * @return void
	 */
	function _e($string, $arguments = array()) {
		echo(__($string, $arguments));
	}

	/**
	 * Encloses translated value with $tag tag
	 * @param string $tag
	 * @param string $string
	 * @param array $arguments
	 * @return void
	 */
	function _t($tag, $string, $arguments = array()) {
		$inline = array();
		while (preg_match('/\.([^\.#]+)/msi', $tag, $matches)) {
			array_push($inline, $matches[1]);
			$tag = str_replace("." . $matches[1], "", $tag);
		}
		if (count($inline) > 0) {
			$inline = " class='" . implode(" ", $inline) . "'";
		} else {
			$inline = "";
		}
		if (preg_match('/#([^\.#]+)/msi', $tag, $matches)) {
			$tag = str_replace("#" . $matches[1], "", $tag);
			$inline .= " id='" . $matches[1] . "'";
		}
		printf("<$tag$inline>%s</$tag>\n", __($string, $arguments));
	}

	/**
	 * Prints out a header
	 * @param $string
	 * @param array $arguments
	 * @param int $level
	 * @param string $append
	 */
	function _h($string, $arguments = array(), $level = 1, $append = "") {
		_t("h$level$append", $string, $arguments);
	}

	/**
	 * Encloses translated value with &lt;p&gt; tag
	 * @param  string $string
	 * @param array $arguments
	 * @return void
	 */
	function _p($string, $arguments = array()) {
		_t("p", $string, $arguments);
	}

	function i18n_calendar_timezones() {
		return array("Africa/Abidjan", "Africa/Accra", "Africa/Addis_Ababa", "Africa/Algiers", "Africa/Asmara", "Africa/Asmera", "Africa/Bamako", "Africa/Bangui", "Africa/Banjul", "Africa/Bissau", "Africa/Blantyre", "Africa/Brazzaville", "Africa/Bujumbura", "Africa/Cairo", "Africa/Casablanca", "Africa/Ceuta", "Africa/Conakry", "Africa/Dakar", "Africa/Dar_es_Salaam", "Africa/Djibouti", "Africa/Douala", "Africa/El_Aaiun", "Africa/Freetown", "Africa/Gaborone", "Africa/Harare", "Africa/Johannesburg", "Africa/Kampala", "Africa/Khartoum", "Africa/Kigali", "Africa/Kinshasa", "Africa/Lagos", "Africa/Libreville", "Africa/Lome", "Africa/Luanda", "Africa/Lubumbashi", "Africa/Lusaka", "Africa/Malabo", "Africa/Maputo", "Africa/Maseru", "Africa/Mbabane", "Africa/Mogadishu", "Africa/Monrovia", "Africa/Nairobi", "Africa/Ndjamena", "Africa/Niamey", "Africa/Nouakchott", "Africa/Ouagadougou", "Africa/Porto-Novo", "Africa/Sao_Tome", "Africa/Timbuktu", "Africa/Tripoli", "Africa/Tunis", "Africa/Windhoek", "America/Adak", "America/Anchorage", "America/Anguilla", "America/Antigua", "America/Araguaina", "America/Argentina/Buenos_Aires", "America/Argentina/Catamarca", "America/Argentina/ComodRivadavia", "America/Argentina/Cordoba", "America/Argentina/Jujuy", "America/Argentina/La_Rioja", "America/Argentina/Mendoza", "America/Argentina/Rio_Gallegos", "America/Argentina/Salta", "America/Argentina/San_Juan", "America/Argentina/San_Luis", "America/Argentina/Tucuman", "America/Argentina/Ushuaia", "America/Aruba", "America/Asuncion", "America/Atikokan", "America/Atka", "America/Bahia", "America/Barbados", "America/Belem", "America/Belize", "America/Blanc-Sablon", "America/Boa_Vista", "America/Bogota", "America/Boise", "America/Buenos_Aires", "America/Cambridge_Bay", "America/Campo_Grande", "America/Cancun", "America/Caracas", "America/Catamarca", "America/Cayenne", "America/Cayman", "America/Chicago", "America/Chihuahua", "America/Coral_Harbour", "America/Cordoba", "America/Costa_Rica", "America/Cuiaba", "America/Curacao", "America/Danmarkshavn", "America/Dawson", "America/Dawson_Creek", "America/Denver", "America/Detroit", "America/Dominica", "America/Edmonton", "America/Eirunepe", "America/El_Salvador", "America/Ensenada", "America/Fort_Wayne", "America/Fortaleza", "America/Glace_Bay", "America/Godthab", "America/Goose_Bay", "America/Grand_Turk", "America/Grenada", "America/Guadeloupe", "America/Guatemala", "America/Guayaquil", "America/Guyana", "America/Halifax", "America/Havana", "America/Hermosillo", "America/Indiana/Indianapolis", "America/Indiana/Knox", "America/Indiana/Marengo", "America/Indiana/Petersburg", "America/Indiana/Tell_City", "America/Indiana/Vevay", "America/Indiana/Vincennes", "America/Indiana/Winamac", "America/Indianapolis", "America/Inuvik", "America/Iqaluit", "America/Jamaica", "America/Jujuy", "America/Juneau", "America/Kentucky/Louisville", "America/Kentucky/Monticello", "America/Knox_IN", "America/La_Paz", "America/Lima", "America/Los_Angeles", "America/Louisville", "America/Maceio", "America/Managua", "America/Manaus", "America/Marigot", "America/Martinique", "America/Matamoros", "America/Mazatlan", "America/Mendoza", "America/Menominee", "America/Merida", "America/Mexico_City", "America/Miquelon", "America/Moncton", "America/Monterrey", "America/Montevideo", "America/Montreal", "America/Montserrat", "America/Nassau", "America/New_York", "America/Nipigon", "America/Nome", "America/Noronha", "America/North_Dakota/Center", "America/North_Dakota/New_Salem", "America/Ojinaga", "America/Panama", "America/Pangnirtung", "America/Paramaribo", "America/Phoenix", "America/Port-au-Prince", "America/Port_of_Spain", "America/Porto_Acre", "America/Porto_Velho", "America/Puerto_Rico", "America/Rainy_River", "America/Rankin_Inlet", "America/Recife", "America/Regina", "America/Resolute", "America/Rio_Branco", "America/Rosario", "America/Santa_Isabel", "America/Santarem", "America/Santiago", "America/Santo_Domingo", "America/Sao_Paulo", "America/Scoresbysund", "America/Shiprock", "America/St_Barthelemy", "America/St_Johns", "America/St_Kitts", "America/St_Lucia", "America/St_Thomas", "America/St_Vincent", "America/Swift_Current", "America/Tegucigalpa", "America/Thule", "America/Thunder_Bay", "America/Tijuana", "America/Toronto", "America/Tortola", "America/Vancouver", "America/Virgin", "America/Whitehorse", "America/Winnipeg", "America/Yakutat", "America/Yellowknife", "Antarctica/Casey", "Antarctica/Davis", "Antarctica/DumontDUrville", "Antarctica/Mawson", "Antarctica/McMurdo", "Antarctica/Palmer", "Antarctica/Rothera", "Antarctica/South_Pole", "Antarctica/Syowa", "Antarctica/Vostok", "Arctic/Longyearbyen", "Asia/Aden", "Asia/Almaty", "Asia/Amman", "Asia/Anadyr", "Asia/Aqtau", "Asia/Aqtobe", "Asia/Ashgabat", "Asia/Ashkhabad", "Asia/Baghdad", "Asia/Bahrain", "Asia/Baku", "Asia/Bangkok", "Asia/Beirut", "Asia/Bishkek", "Asia/Brunei", "Asia/Calcutta", "Asia/Choibalsan", "Asia/Chongqing", "Asia/Chungking", "Asia/Colombo", "Asia/Dacca", "Asia/Damascus", "Asia/Dhaka", "Asia/Dili", "Asia/Dubai", "Asia/Dushanbe", "Asia/Gaza", "Asia/Harbin", "Asia/Ho_Chi_Minh", "Asia/Hong_Kong", "Asia/Hovd", "Asia/Irkutsk", "Asia/Istanbul", "Asia/Jakarta", "Asia/Jayapura", "Asia/Jerusalem", "Asia/Kabul", "Asia/Kamchatka", "Asia/Karachi", "Asia/Kashgar", "Asia/Kathmandu", "Asia/Katmandu", "Asia/Kolkata", "Asia/Krasnoyarsk", "Asia/Kuala_Lumpur", "Asia/Kuching", "Asia/Kuwait", "Asia/Macao", "Asia/Macau", "Asia/Magadan", "Asia/Makassar", "Asia/Manila", "Asia/Muscat", "Asia/Nicosia", "Asia/Novokuznetsk", "Asia/Novosibirsk", "Asia/Omsk", "Asia/Oral", "Asia/Phnom_Penh", "Asia/Pontianak", "Asia/Pyongyang", "Asia/Qatar", "Asia/Qyzylorda", "Asia/Rangoon", "Asia/Riyadh", "Asia/Saigon", "Asia/Sakhalin", "Asia/Samarkand", "Asia/Seoul", "Asia/Shanghai", "Asia/Singapore", "Asia/Taipei", "Asia/Tashkent", "Asia/Tbilisi", "Asia/Tehran", "Asia/Tel_Aviv", "Asia/Thimbu", "Asia/Thimphu", "Asia/Tokyo", "Asia/Ujung_Pandang", "Asia/Ulaanbaatar", "Asia/Ulan_Bator", "Asia/Urumqi", "Asia/Vientiane", "Asia/Vladivostok", "Asia/Yakutsk", "Asia/Yekaterinburg", "Asia/Yerevan", "Atlantic/Azores", "Atlantic/Bermuda", "Atlantic/Canary", "Atlantic/Cape_Verde", "Atlantic/Faeroe", "Atlantic/Faroe", "Atlantic/Jan_Mayen", "Atlantic/Madeira", "Atlantic/Reykjavik", "Atlantic/South_Georgia", "Atlantic/St_Helena", "Atlantic/Stanley", "Australia/ACT", "Australia/Adelaide", "Australia/Brisbane", "Australia/Broken_Hill", "Australia/Canberra", "Australia/Currie", "Australia/Darwin", "Australia/Eucla", "Australia/Hobart", "Australia/LHI", "Australia/Lindeman", "Australia/Lord_Howe", "Australia/Melbourne", "Australia/NSW", "Australia/North", "Australia/Perth", "Australia/Queensland", "Australia/South", "Australia/Sydney", "Australia/Tasmania", "Australia/Victoria", "Australia/West", "Australia/Yancowinna", "Brazil/Acre", "Brazil/DeNoronha", "Brazil/East", "Brazil/West", "CET", "CST6CDT", "Canada/Atlantic", "Canada/Central", "Canada/East-Saskatchewan", "Canada/Eastern", "Canada/Mountain", "Canada/Newfoundland", "Canada/Pacific", "Canada/Saskatchewan", "Canada/Yukon", "Chile/Continental", "Chile/EasterIsland", "Cuba", "EET", "EST", "EST5EDT", "Egypt", "Eire", "Etc/GMT", "Etc/GMT+0", "Etc/GMT+1", "Etc/GMT+10", "Etc/GMT+11", "Etc/GMT+12", "Etc/GMT+2", "Etc/GMT+3", "Etc/GMT+4", "Etc/GMT+5", "Etc/GMT+6", "Etc/GMT+7", "Etc/GMT+8", "Etc/GMT+9", "Etc/GMT-0", "Etc/GMT-1", "Etc/GMT-10", "Etc/GMT-11", "Etc/GMT-12", "Etc/GMT-13", "Etc/GMT-14", "Etc/GMT-2", "Etc/GMT-3", "Etc/GMT-4", "Etc/GMT-5", "Etc/GMT-6", "Etc/GMT-7", "Etc/GMT-8", "Etc/GMT-9", "Etc/GMT0", "Etc/Greenwich", "Etc/UCT", "Etc/UTC", "Etc/Universal", "Etc/Zulu", "Europe/Amsterdam", "Europe/Andorra", "Europe/Athens", "Europe/Belfast", "Europe/Belgrade", "Europe/Berlin", "Europe/Bratislava", "Europe/Brussels", "Europe/Bucharest", "Europe/Budapest", "Europe/Chisinau", "Europe/Copenhagen", "Europe/Dublin", "Europe/Gibraltar", "Europe/Guernsey", "Europe/Helsinki", "Europe/Isle_of_Man", "Europe/Istanbul", "Europe/Jersey", "Europe/Kaliningrad", "Europe/Kiev", "Europe/Lisbon", "Europe/Ljubljana", "Europe/London", "Europe/Luxembourg", "Europe/Madrid", "Europe/Malta", "Europe/Mariehamn", "Europe/Minsk", "Europe/Monaco", "Europe/Moscow", "Europe/Nicosia", "Europe/Oslo", "Europe/Paris", "Europe/Podgorica", "Europe/Prague", "Europe/Riga", "Europe/Rome", "Europe/Samara", "Europe/San_Marino", "Europe/Sarajevo", "Europe/Simferopol", "Europe/Skopje", "Europe/Sofia", "Europe/Stockholm", "Europe/Tallinn", "Europe/Tirane", "Europe/Tiraspol", "Europe/Uzhgorod", "Europe/Vaduz", "Europe/Vatican", "Europe/Vienna", "Europe/Vilnius", "Europe/Volgograd", "Europe/Warsaw", "Europe/Zagreb", "Europe/Zaporozhye", "Europe/Zurich", "Factory", "GB", "GB-Eire", "GMT", "GMT+0", "GMT-0", "GMT0", "Greenwich", "HST", "Hongkong", "Iceland", "Indian/Antananarivo", "Indian/Chagos", "Indian/Christmas", "Indian/Cocos", "Indian/Comoro", "Indian/Kerguelen", "Indian/Mahe", "Indian/Maldives", "Indian/Mauritius", "Indian/Mayotte", "Indian/Reunion", "Iran", "Israel", "Jamaica", "Japan", "Kwajalein", "Libya", "MET", "MST", "MST7MDT", "Mexico/BajaNorte", "Mexico/BajaSur", "Mexico/General", "NZ", "NZ-CHAT", "Navajo", "PRC", "PST8PDT", "Pacific/Apia", "Pacific/Auckland", "Pacific/Chatham", "Pacific/Easter", "Pacific/Efate", "Pacific/Enderbury", "Pacific/Fakaofo", "Pacific/Fiji", "Pacific/Funafuti", "Pacific/Galapagos", "Pacific/Gambier", "Pacific/Guadalcanal", "Pacific/Guam", "Pacific/Honolulu", "Pacific/Johnston", "Pacific/Kiritimati", "Pacific/Kosrae", "Pacific/Kwajalein", "Pacific/Majuro", "Pacific/Marquesas", "Pacific/Midway", "Pacific/Nauru", "Pacific/Niue", "Pacific/Norfolk", "Pacific/Noumea", "Pacific/Pago_Pago", "Pacific/Palau", "Pacific/Pitcairn", "Pacific/Ponape", "Pacific/Port_Moresby", "Pacific/Rarotonga", "Pacific/Saipan", "Pacific/Samoa", "Pacific/Tahiti", "Pacific/Tarawa", "Pacific/Tongatapu", "Pacific/Truk", "Pacific/Wake", "Pacific/Wallis", "Pacific/Yap", "Poland", "Portugal", "ROC", "ROK", "Singapore", "Turkey", "UCT", "US/Alaska", "US/Aleutian", "US/Arizona", "US/Central", "US/East-Indiana", "US/Eastern", "US/Hawaii", "US/Indiana-Starke", "US/Michigan", "US/Mountain", "US/Pacific", "US/Pacific-New", "US/Samoa", "UTC", "Universal", "W-SU", "WET", "Zulu");
	}

}
?>