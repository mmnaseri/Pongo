<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 15:18)
 */
//@Entity
class WidgetsService extends Service {

	public function initialize_options() {
		$locale = array_get($_GET, ':locale', 'en_US');
		if (empty($locale)) {
			$locale = "en_US";
		}
		$info = page_info($_POST['currentUrl']);
		page_options_set('module', $info['module']);
		page_options_set('page', $info['page']);
		page_options_set('call', $info['call']);
		page_options_set('path', $info['path']);
		page_options_set('relative', $info['relative']);
		page_options_set('locale', $locale);
		$options = array_get($info, 'options', array());
		if (!is_array($options)) {
			$options = array();
		}
		foreach ($options as $option => $value) {
			page_options_set($option, $value);
		}
		page_options_set('dir', i18n_meta('input/direction'));
	}

	function get($arguments) {
		$this->initialize_options();
		$result = array();
		$widget = widget_load($arguments['name']);
		ob_start();
		/** @noinspection PhpUndefinedMethodInspection */
		$content = $widget->getContent(null);
		$buffer = ob_get_clean();
		if (!empty($buffer)) {
			$content = $buffer;
		}
		$result['content'] = $content;
		/** @noinspection PhpUndefinedMethodInspection */
		$result['scripts'] = $widget->getScripts();
		/** @noinspection PhpUndefinedMethodInspection */
		$result['styles'] = $widget->getStyles();
		return $result;
	}

}

?>