<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 17, 2010
 * Time: 9:54:07 PM
 */

/**
 * @param $action
 * @param $arguments
 * @return array|bool
 */
function calendar_gregorian($action, $arguments) {
	$months = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	switch ($action) {
		case I18N_CALENDAR_IS_LEAP:
			$value = $arguments['year'] % 33;
			return ($value % 4 == 0 && $value % 100 != 0) || ($value % 400 == 0);
		case I18N_CALENDAR_MONTH_NAMES:
			return array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		case I18N_CALENDAR_WEEKDAY_NAMES:
			return array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
		case I18N_CALENDAR_PARAMETERS:
			$parameters = "dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZU";
			$result = array();
			for ($i = 0; $i < strlen($parameters); $i++) {
				$result[$parameters[$i]] = date($parameters[$i], $arguments['timestamp']);
			}
			return $result;
		case I18N_CALENDAR_TO_GREGORIAN:
			return $arguments;
		case I18N_CALENDAR_FROM_GREGORIAN:
			return $arguments;
		default:
			break;
	}
}

?>