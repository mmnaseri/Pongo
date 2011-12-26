<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Feb 2, 2011
 * Time: 12:36:38 PM
 */

define("YQL_RETURN_TYPE_JSON", "json");
define("YQL_RETURN_TYPE_XML", "xml");
define("YQL_STORE_OPEN_TABLES", "store://datatables.org/alltableswithkeys");

function yql_url($query, $return_type = YQL_RETURN_TYPE_JSON, $store = null) {
	$url = "http://query.yahooapis.com/v1/public/yql";
	$url = $url . "?q=" . urlencode($query) . "&format=" . $return_type;
	if ($store !== null) {
		$url .= "&env=" . urlencode($store);
	}
	return $url;
}

function yql_query($query, $return_type = YQL_RETURN_TYPE_JSON, $store = null) {
	$url = yql_url($query, $return_type, $store);
	return yql_query_with_url($url);
}

function yql_query_with_url($url) {
	$session = curl_init($url);
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($session);
	curl_close($session);
	return $result;
}

function yql_execute($query, $return_type = YQL_RETURN_TYPE_JSON, $store = null) {
	$result = yql_query($query, $return_type, $store);
	if ($return_type == YQL_RETURN_TYPE_JSON) {
		return json_decode($result);
	} else {
		return xml_from_string($result);
	}
}

?>