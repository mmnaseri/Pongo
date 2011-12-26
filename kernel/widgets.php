<?php

/**
 * pongo PHP Framework Kernel Component
 * Package WIDGETS
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 15:08)
 * @package com.agileapes.pongo.kernel.widgets
 */

if (!defined("KERNEL_COMPONENT_WIDGETS")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_WIDGETS', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_WIDGETS', 9000);

	class WidgetsException extends KernelException {

		protected function getBase() {
			return KCE_WIDGETS;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	abstract class Widget {

		private $scripts = array();
		private $styles = array();

		abstract function getContent($options);

		public function addScript($script) {
			array_push($this->scripts, url_relative($script));
		}

		public function addStyle($style) {
			array_push($this->styles, url_relative($style));
		}

		public function getScripts() {
			return $this->scripts;
		}

		public function getStyles() {
			return $this->styles;
		}

	}

	function widget_load($name) {
		$file = cache_get('widget:' . $name);
		if ($file == null) {
			throw new WidgetsException("Widget not found: %1", null, array($name));
		}
		return entity_load($file, 'Widget');
	}

	function widget_register($file) {
		$suffix = "Widget";
		$name = entity_name($file, 'Widget');
		if (!preg_match("/$suffix$/", $name)) {
			throw new WidgetsException("Invalid widget name: %1", null, array($name));
		}
		$name = substr($name, 0, -strlen($suffix));
		cache_set("widget:" . $name, $file);
	}

	function widget_src($name, $ref) {
		$id = uniqid("widget-");
		if (boolval(page_options_get('static', false))) {
			$widget = widget_load($name);
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
			$src = "<div id='$id' class='widget $name'>" . $result['content'] . "</div>";
			$src .= '<script type="text/javascript"><!--' . "
			'$id'.asElement().directionAware = true;" . "Widget.register('$id', '$name', '$ref');\n";
			for ($i = 0; $i < count($result['scripts']); $i++) {
				$src .= "Common.includeJS('" . $result['scripts'][$i] . "');";
			}
			for ($i = 0; $i < count($result['styles']); $i++) {
				$src .= "Common.includeCSS('" . $result['styles'][$i] . "');";
			}
			$src .= '//--></script>';
			return $src;
		}
		$src = "<div id='$id'>&nbsp;</div>\n";
		$src .= "<script type=\"text/javascript\"><!--
			Widget.render('$name', '$id', '$ref');
		//--></script>\n";
		return $src;
	}

	function widget_render($name, $ref) {
		echo(widget_src($name, $ref));
	}

}

?>