<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (13/12/11, 1:07)
 */

if (!class_exists('FormDeliveryService')) {

	//@Entity
	class FormDeliveryService extends Service {

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
			page_path($arguments['url']);
			$name = $arguments['name'];
			$form = forms_build($name);
			$result = array('form' => $form->render());
			$result = (object) $result;
			return $result;
		}

	}

}

?>