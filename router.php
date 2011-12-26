<?php
/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (15/11/11, 12:04)
 */

include("kernel/loader.php");
$locale = array_get($_GET, ':locale', 'en_US');
if (empty($locale)) {
	$locale = "en_US";
}
db_connect(db_create_string("mysql", "localhost", "8080", "root", "mn32205", "pongo", array('characterEncoding' => "utf8")));
kernel_option_load();
if (cache_get('init:service') == null) {
	service_discover_all();
}
state_initialize();
page_options_set('static', boolval(state_get('page.rendering.static', DEFAULT_BEHAVIOUR_STATIC)));
users_login_from_cookie();
i18n_defaults_regions_location(url_local("contents/regions"));
i18n_defaults_root(url_base_local());
i18n_initialize();
kernel_locale_init();
i18n_region_set_by_name($locale);
widget_register("/modules/system/widgets/.mainmenu.php");
widget_register("/modules/system/widgets/.langmenu.php");
if (page_options_get('static', false)) {
	$page = service_call('page', 'get', $_GET[':url'], null);
	theme_render('default', $page);
} else {
	theme_render('default');
}
i18n_finalize();
kernel_option_save();
state_commit();
db_disconnect();
?>