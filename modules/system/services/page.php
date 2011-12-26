<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 19:19)
 */
//@Entity
class PageService extends Service {

	function init_js() {
		header('Cache-Control: no-cache, must-revalidate');
		$locale = array_get($_GET, ':locale', 'en_US');
		if (empty($locale)) {
			$locale = "en_US";
		}
		$direction = i18n_meta('input/direction');
		$static = boolval(page_options_get('static', state_get('page.rendering.static', DEFAULT_BEHAVIOUR_STATIC))) == true;
		echo("Common.host = '" . $_SERVER['HTTP_HOST'] . "';");
		echo("Common.setRoot('" . url_base() . "');");
		echo("Common.dir = '" . $direction . "';");
		echo("Common.locale = '" . $locale . "';");
		echo("Common.staticRender = " . ($static ? 'true' : 'false') . ";");
		echo("Common.addInitializer(function() {
			Common.handleLinks(document.body);
			Common.host = '" . $_SERVER['HTTP_HOST'] . "';
			window.isNavigating = false;
			Common.setDirections(" . ($static ? "document.getElementsByTagName('body').length > 0 ? document.getElementsByTagName('body')[0] : null" : 'document.body') . ", '$direction');
		});
		Common.initPage();");
	}

	function get($arguments) {
		global $redirecting, $relocating;
		$redirecting = null;
		$relocating = null;
		$locale = array_get($_GET, ':locale', 'en_US');
		if (empty($locale)) {
			$locale = "en_US";
		}
		page_options_set('dir', i18n_meta('input/direction'));
		$info = page_info($arguments['url']);
		foreach (array_get($info, 'parameters') as $parameter => $value) {
			if (!array_key_exists($parameter, $_GET)) {
				$_GET[$parameter] = $value;
			}
			if (!array_key_exists($parameter, $_REQUEST)) {
				$_REQUEST[$parameter] = $value;
			}
		}
		page_options_set('module', $info['module']);
		page_options_set('page', $info['page']);
		page_options_set('call', $info['call']);
		page_options_set('path', $info['path']);
		page_options_set('relative', $info['relative']);
		page_options_set('locale', $locale);
		page_options_set('context', 'init');
		$data = array_get($arguments, 'data', null);
		if (!is_null($data)) {
			$data = json_decode(trim(state_decrypt($data)));
			$get = (array) $data->get;
			$post = (array) $data->post;
			foreach ($get as $key => $value) {
				$_GET[$key] = $value;
			}
			foreach ($post as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		$page = entity_load($info['path'], "Page");
		if (url_relocating() !== null) {
			page_options_set('relocation', $relocating['url']);
			return $this->get(array('url' => 'system/redirection'));
		}
		if (url_redirecting() !== null) {
			return $this->get(array('url' => url_redirecting()));
		}
		$result = array();
		/** @noinspection PhpUndefinedMethodInspection */
		$result['title'] = __($page->getTitle());
		/** @noinspection PhpUndefinedMethodInspection */
		$result['scripts'] = $page->getScripts();
		/** @noinspection PhpUndefinedMethodInspection */
		$result['styles'] = $page->getStyles();
		/** @noinspection PhpUndefinedMethodInspection */
		$result['favicon'] = $page->getFavicon();
		page_options_set('context', 'head');
		ob_start();
		/** @noinspection PhpUndefinedMethodInspection */
		$page->getHead();
		$buffer = ob_get_clean();
		if (url_relocating() !== null) {
			page_options_set('relocation', $relocating['url']);
			return $this->get(array('url' => 'system/redirection'));
		}
		if (url_redirecting() !== null) {
			return $this->get(array('url' => url_redirecting()));
		}
		$options = array_get($info, 'options', array());
		if (!is_array($options)) {
			$options = array();
		}
		foreach ($options as $option => $value) {
			page_options_set($option, $value);
		}
		$result['head'] = $buffer;
		page_options_set('context', 'body');
		ob_start();
		/** @noinspection PhpUndefinedMethodInspection */
		$page->getContent();
		$buffer = ob_get_clean();
		ob_start();
		print_tidy($buffer, true);
		$buffer = ob_get_clean();
		if (url_redirecting() !== null) {
			return $this->get(array('url' => url_redirecting()));
		}
		$result['body'] = $buffer;
		return $result;
	}

}

?>