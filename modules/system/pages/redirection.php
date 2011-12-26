<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (22/12/11, 17:45)
 */

if (!class_exists('RedirectionPage')) {

	//@Entity
	class RedirectionPage extends Page {

		function getContent() {
			if (page_options_get('relocation', null) === null) {
				url_redirect("");
				return;
			}
			$url = page_options_get('relocation');
			echo('<div><script type="text/javascript"><!--
			Common.addInitializer(function () {
				Service.call("system", "echo", {input: ""}, true);
				Page.navigate("' . $url . '");
			});
			Common.initPage();
			//--></script></div>');
		}

	}

}

?>