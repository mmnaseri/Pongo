<?php

/**
 * pongo PHP Framework Kernel Component
 * Package THEME
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (11/12/11, 23:09)
 * @package com.agileapes.pongo.kernel.theme
 */

if (!defined("KERNEL_COMPONENT_THEME")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_THEME', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_THEME', 13000);

	class ThemeException extends KernelException {

		protected function getBase() {
			return KCE_THEME;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	function theme_dir($name) {
		return "/contents/themes/$name/";
	}

	function theme_load($name) {
		$path = url_local(theme_dir($name) . "/frontend.xml");
		if (!file_exists($path)) {
			throw new ThemeException("Theme not found: %1", null, array($name));
		}
		return xml_from_file($path, true, true);
	}

	function theme_render($name, $page = null) {
		$theme = theme_load($name);
		theme_resolve_includes($name, $theme);
		$body = xml_path($theme, 'theme/body');
		if (count($body) != 1) {
			throw new ThemeException("Exactly one &lt;body&gt; tag was expected");
		}
		$body = $body[0];
		$arguments = array('placeholders' => 0, 'page' => $page);
		xml_traverse($body, function (&$tag, &$arguments) {
			$locale = array_get($_GET, ':locale', 'en_US');
			if (empty($locale)) {
				$locale = "en_US";
			}
			if (xml_get_node_name($tag) == 'placeholder') {
				$placeholder = $arguments['placeholders']++;
				$tag = xml_from_string("<script type='text/javascript'>
//					Placeholder.render($placeholder);
				</script>");
			} else if (xml_get_node_name($tag) == 'content') {
				$page = uniqid("page-");
				$body = "<div class='theme-page-body'>";
				if ($arguments['page'] == null) {
					$data = state_encrypt(json_encode(array('get' => $_GET, 'post' => $_POST)));
					$body .= "<div id='$page'>&nbsp;</div>
									<script type='text/javascript'>
										document.page = '$page'.asElement();
										Page.navigate('" . array_get($_GET, ':url', '') . "', '$locale', ";
					$body .= json_encode($data);
					$body .= ");
									</script>";
				} else {
					$body .= "<div id='$page'>" . $arguments['page']['body'] . "</div>";
					$body .= "<script type='text/javascript'>
										'$page'.asElement().directionAware = true;
									</script>";
				}
				$body .= "</div>";
				$tag = xml_from_string($body);
			} else if (xml_get_node_name($tag) == 'widget') {
				$name = xml_get_attribute($tag, 'name');
				$ref = xml_get_attribute($tag, 'id');
				$tag = xml_from_string(widget_src($name, $ref));
			}
		}, $arguments);
		ob_start();
		echo("<html>");
		echo("<head>");
		echo("<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />");
		if ($page === null) {
			echo("<title>Pongo</title>");
			for ($i = 0; $i < count(Page::getDefaultScripts()); $i++) {
				echo("<script type='text/javascript' src='" . Page::getDefaultScripts($i) . "'></script>");
			}
		} else {
			echo("<title>$page[title]</title>");
			if (empty($page['favicon']) || !file_exists(url_local($page['favicon']))) {
				$page['favicon'] = "/contents/images/favicon.ico";
			}
			$scripts = Page::getDefaultScripts();
			for ($i = 0; $i < count($scripts); $i++) {
				echo("<script type='text/javascript' src='" . $scripts[$i] . "'></script>");
			}
			$page['favicon'] = url_relative($page['favicon']);
			echo("<link rel='shortcut icon' href='" . $page['favicon'] . "' />");
			$styles = theme_resolve_media($name, 'style');
			for ($i = 0; $i < count($styles); $i++) {
				if ($styles[$i]->mode == 'include') {
					echo("<link type='text/css' rel='stylesheet' href='" . $styles[$i]->src . "' />");
				}
			}
			for ($i = 0; $i < count($styles); $i++) {
				if ($styles[$i]->mode == 'inline') {
					echo("<style type='text/css'>\n" . $styles[$i]->content . "\n</style>");
				}
			}
			$scripts = theme_resolve_media($name, 'script');
			for ($i = 0; $i < count($scripts); $i++) {
				if ($scripts[$i]->mode == 'include') {
					echo("<script type='text/javascript' src='" . $scripts[$i]->src . "'></script>");
				}
			}
			for ($i = 0; $i < count($scripts); $i++) {
				if ($scripts[$i]->mode == 'inline') {
					echo("<script type='text/javascript'><!--\n" . $scripts[$i]->content . "\n//--></script>");
				}
			}
			$styles = $page['styles'];
			for ($i = 0; $i < count($styles); $i++) {
				echo("<link type='text/css' rel='stylesheet' href='" . $styles[$i] . "' />");
			}
			$scripts = $page['scripts'];
			for ($i = 0; $i < count($scripts); $i++) {
				if (array_search($scripts[$i], Page::getDefaultScripts()) !== false) {
					continue;
				}
				echo("<script type='text/javascript' src='" . $scripts[$i] . "'></script>");
			}
			echo('<script type="text/javascript"><!--
			Common.url = "' . $_GET[':url'] . '";
			//--></script>');
		}
		echo("</head>");
		echo("<body>");
		$body = xml_to_string($body);
		$body = str_replace("&amp;", "&", $body);
		echo($body);
		echo("</body>");
		echo("</html>");
		$buffer = ob_get_clean();
		//		echo($buffer);
		print_tidy($buffer);
	}

	function theme_resolve_media($name, $type) {
		$theme = theme_load($name);
		if (array_search($type, array('script', 'style')) === false) {
			throw new ThemeException('Invalid media type specified: %1', null, array($type));
		}
		$result = array();
		$media = xml_path($theme, 'theme/head/' . $type . '^');
		for ($i = 0; $i < count($media); $i++) {
			$medium = $media[$i];
			$medium = xml_from_string($medium);
			$fail = false;
			for ($j = 0; $j < count($medium['children']); $j++) {
				$condition = $medium['children'][$j];
				if (xml_get_node_name($condition) != 'condition') {
					if (xml_get_node_name($condition) == 'inline') {
						continue;
					}
					throw new ThemeException("Invalid nod at `theme/head/%1[%2]`: %3", null, array($type, $i, xml_get_node_name($condition)));
				}
				if (!theme_condition_check($condition)) {
					$fail = true;
					break;
				}
			}
			if ($fail) {
				continue;
			}
			$include = xml_has_attribute($medium, 'src');
			$inline = xml_get_child($medium, 'inline') !== false;
			if (($include && $inline) || !($include || $inline)) {
				throw new ThemeException("A media tag should either inline its content or reference an external source");
			}
			$target = array('type' => $type);
			if ($inline) {
				$target['mode'] = 'inline';
				$tag = xml_get_child($medium, 'inline');
				$tag = $tag['children'];
				$target['content'] = xml_to_string($tag);
				$target['content'] = preg_replace_callback("/([^\\\\])\\$\\{([^\\}]+)\\}/", function ($matches) {
					$options = explode("/", $matches[2], 2);
					if (count($options) < 2) {
						return "";
					}
					return $matches[1] . theme_option_resolve($options[0], $options[1]);
				}, $target['content']);
			} else {
				$target['mode'] = 'include';
				$target['src'] = url_relative(theme_dir($name) . "/" . xml_get_attribute($medium, 'src'));
			}
			$target = (object) $target;
			array_push($result, $target);
		}
		return $result;
	}

	function theme_resolve_includes($name, $theme) {
		$includes = xml_path($theme, 'theme/head/include^');
		for ($i = 0; $i < count($includes); $i++) {
			$include = $includes[$i];
			$include = xml_from_string($include);
			$fail = false;
			for ($j = 0; $j < count($include['children']); $j++) {
				$condition = $include['children'][$j];
				if (xml_get_node_name($condition) != 'condition') {
					throw new ThemeException("Invalid nod at `theme/head/include[$i]`: " . xml_get_node_name($condition));
				}
				if (!theme_condition_check($condition)) {
					$fail = true;
					break;
				}
			}
			if ($fail) {
				continue;
			}
			$src = url_local(theme_dir($name) . "/" . xml_get_attribute($include, "src", ""));
			if (!file_exists($src)) {
				throw new ThemeException("File not found: %1", null, array($src));
			}
			/** @noinspection PhpIncludeInspection */
			include($src);
		}
	}

	function theme_condition_check($condition) {
		$target = theme_option_resolve(xml_get_attribute($condition, 'target'), xml_get_attribute($condition, 'key'));
		$value = strval(xml_get_attribute($condition, 'value'));
		$inverse = false;
		if ($value[0] == '!') {
			$value = substr($value, 1);
			$inverse = true;
		}
		$pattern = '|^' . str_replace('|', '\\|', $value) . '$|msi';
		$check = @preg_match($pattern, $target);
		return (!$inverse && $check) || ($inverse && !$check);
	}

	function theme_option_resolve($target, $key) {
		$options = array();
		if ($target == 'page') {
			$options = page_options_get(null);
		} else if ($target == 'get') {
			$options = $_GET;
		} else if ($target == 'post') {
			$options = $_GET;
		} else if ($target == 'request') {
			$options = $_GET;
		} else if ($target == 'globals') {
			global $_GLOBALS;
			$options = $_GLOBALS;
		} else if ($target == 'session') {
			$options = state_get();
		}
		return strval(array_access($options, $key, ""));
	}

}

?>