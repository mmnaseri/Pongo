<?php
/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 20:02)
 */

include("kernel/loader.php");

$string = db_create_string("mysql", "localhost", "8080", "root", "mn32205", "pongo", array('characterEncoding' => "utf8"));
db_connect($string);
state_initialize();
i18n_defaults_regions_location(url_local("contents/regions"));
i18n_defaults_root(url_base_local());
i18n_initialize();
kernel_locale_init();
i18n_region_set_by_name($_GET[':locale']);
//service_unload_all();
if (true || cache_get('init:service') == null) {
	service_discover_all();
}
$definition = service_definition($_GET[':service'], $_GET[':gateway']);
$arguments = array();
for ($i = 0; $i < count($definition['arguments']); $i++) {
	$argument = $definition['arguments'][$i];
	if (!$argument['optional'] && !array_key_exists($argument['name'], $_REQUEST)) {
		throw new ServiceException("Required parameter %1 is missing", null, array($argument['name']));
	}
	array_push($arguments, json_decode(array_get($_REQUEST, $argument['name'], null)));
}
ob_start();
$result = "";
try {
	$result = service_callv($_GET[':service'], $_GET[':gateway'], $arguments);
} catch (Exception $e) {
	log_error($e->getFile() . ":" . $e->getLine() . ": " . $e->getMessage(), 'service');
	log_error($e->getFile() . ":" . $e->getLine() . ": " . $e->getMessage(), 'service.details');
	log_error($e->getTraceAsString(), 'service.details');
	ob_clean();
	$arguments = array();
	if (is_a($e, 'KernelException')) {
		/** @noinspection PhpUndefinedMethodInspection */
		$argument = $e->getArguments();
	}
	$e = array(
		'code' => $e->getCode(),
		'file' => $e->getFile(),
		'line' => $e->getLine(),
		'message' => __($e->getMessage(), $arguments), 'trace' => $e->getTraceAsString(),
		'isException' => true
	);
	ob_start();
	$definition['encode'] = false;
	echo(json_encode($e));
}
$buffer = ob_get_clean();
if (!empty($buffer)) {
	$result = $buffer;
}
if ($definition['encode'] && !(boolval(array_get($_REQUEST, ':no-json', false)))) {
	$result = json_encode($result);
}
echo($result);
i18n_finalize();
if ($definition['stateful']) {
	state_commit();
}
db_disconnect();
?>