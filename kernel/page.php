<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 15:43)
 */
abstract class Page {

	private $title;
	private $favicon = "";
	private $scripts = array();
	private $styles = array();

	static function getDefaultScripts($index = null) {
		$locale = array_get($_GET, ':locale', 'en_US');
		if (empty($locale)) {
			$locale = "en_US";
		}
		$scripts = array(url_relative("/contents/scripts/common.js"), url_relative("/contents/scripts/ajax.js"), url_relative("/contents/scripts/service.js"), url_relative("/contents/scripts/page.js"), url_relative("/contents/scripts/common.js"), url_relative("/contents/scripts/widgets.js"), url_relative("/contents/scripts/forms.js"), url_relative("/service/$locale/page/init.js"),);
		if ($index !== null) {
			return array_get($scripts, $index, "");
		}
		return $scripts;
	}

	function getHead() {
	}

	abstract function getContent();

	function __construct() {
		$this->initialize();
	}

	public function initialize() {

	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getScripts() {
		$scripts = $this->scripts;
		for ($i = 0; $i < count(Page::getDefaultScripts()); $i++) {
			array_push($scripts, Page::getDefaultScripts($i));
		}
		return $scripts;
	}

	public function getStyles() {
		return $this->styles;
	}

	public function addScript($script) {
		array_push($this->scripts, url_relative($script));
	}

	public function addStyle($style) {
		array_push($this->styles, url_relative($style));
	}

	public function setFavicon($favicon) {
		$this->favicon = $favicon;
	}

	public function getFavicon() {
		return $this->favicon;
	}

}

define("GLOBAL_CONTEXT_PAGE", "GLOBAL_CONTEXT_PAGE");

globals_context_register(GLOBAL_CONTEXT_PAGE);

function page_options_set($key, $value) {
	globals_set(GLOBAL_CONTEXT_PAGE, $key, $value);
}

function page_options_get($key, $default = null) {
	if ($key === null) {
		return globals_context_get(GLOBAL_CONTEXT_PAGE);
	}
	return globals_get(GLOBAL_CONTEXT_PAGE, $key, $default);
}

function page_options_delete($key) {
	return globals_delete(GLOBAL_CONTEXT_PAGE, $key);
}

function page_url($url) {
	$info = page_info($url);
	page_options_set('module', $info['module']);
	return $info['relative'];
}

function page_info($url) {
	$url = preg_split('|/|', $url, 3, PREG_SPLIT_NO_EMPTY);
	if (count($url) == 0) {
		array_push($url, 'home');
		array_push($url, $url[0]);
		array_push($url, "");
	} else if (count($url) == 1) {
		array_push($url, $url[0]);
		array_push($url, "");
	} else if (count($url) == 2) {
		array_push($url, "");
	}
	$module = $url[0];
	$page = $url[1];
	$call = $url[2];
	$definition = page_discover($module);
	$definition = array_get($definition, $page, array());
	$parameters = page_parameters($definition, $call);
	$relative = "$module/$page";
	if ($parameters == null) {
		$parameters = array();
	}
	if (array_get($definition, 'type') == 'script') {
		$path = "/modules/$module/" . array_get($definition, 'url', '');
	} else if (array_get($definition, 'type') == 'service') {
		$path = "/modules/system/pages/service.php";
	} else {
		$path = "/modules/errors/404.php";
	}
	if (count($parameters) == 0 && !empty($call)) {
		$relative = "errors/404";
		$path = "/modules/errors/404.php";
	}
	return array('module' => $module, 'page' => $page, 'call' => $call, 'path' => $path, 'relative' => $relative, 'parameters' => $parameters, 'options' => array_get($definition, 'options'),);
}

function page_path($url) {
	$info = page_info($url);
	return $info['path'];
}

function page_discover($module) {
	$file = url_local(modules_file($module, 'pages.xml'));
	if (!file_exists($file)) {
		return array("");
	}
	$result = array();
	$file = xmlnode_path(xmlnode_from_file($file), 'pages//page');
	for ($i = 0; $i < count($file); $i++) {
		$result[$file[$i]['@name']] = array('url' => array_get($file[$i], '@url', $file[$i]['@name']), 'type' => array_get($file[$i], '@type', 'script'), 'parameters' => xmlnode_path($file[$i], 'parameters//parameter'), 'options' => xmlnode_path($file[$i], 'options//option'),);
		for ($j = 0; $j < count($result[$file[$i]['@name']]['parameters']); $j++) {
			$result[$file[$i]['@name']]['parameters'][$j] = array('name' => array_get($result[$file[$i]['@name']]['parameters'][$j], '@name'), 'default' => array_get($result[$file[$i]['@name']]['parameters'][$j], '@default', ''),);
		}
		for ($j = 0; $j < count($result[$file[$i]['@name']]['options']); $j++) {
			$result[$file[$i]['@name']]['options'][array_get($result[$file[$i]['@name']]['options'][$j], '@name')] = array_get($result[$file[$i]['@name']]['options'][$j], '@value', '');
			unset($result[$file[$i]['@name']]['options'][$j]);
		}
	}
	return $result;
}

function page_parameters($definition, $url) {
	$parameters = array_get($definition, 'parameters');
	if (count($parameters) == 0) {
		return null;
	}
	$url = explode("/", $url);
	if (count($url) > count($parameters)) {
		return null;
	}
	$result = array();
	for ($i = 0; $i < count($parameters); $i++) {
		if ($i >= count($url) || empty($url[$i])) {
			$result[array_access($parameters, $i . '/name')] = array_access($parameters, $i . '/default');
		} else {
			$result[array_access($parameters, $i . '/name')] = $url[$i];
		}
	}
	return $result;
}

?>