<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 17, 2010
 * Time: 9:54:07 PM
 * The conversion algorithm is courtesy of Roozbeh Pournader and Mohammad Toossi
 * It has undergone some serious polishing, but everything is basically the same
 * @link http://www.farsiweb.info/jalali/jalali.phps
 */

/**
 * @param  $action
 * @param  $arguments
 * @return array|bool|float|int|string
 */
function calendar_jalali($action, $arguments) {
	$months = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
	$gregorian_months = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$month_names = array("Farvardin", "Ordibehesht", "Khordad", "Tir", "Mordad", "Shahrivar", "Mehr", "Aban", "Azar", "Dey", "Bahman", "Esfand");
	$weekdays = array("Shanbeh", "Yekshanbeh", "Doshanbeh", "Seshanbeh", "Chaharshanbeh", "Panjshanbeh", "Jom'eh");
	switch ($action) {
		case I18N_CALENDAR_IS_LEAP:
			return array_search($arguments['year'] % 33, array(1, 5, 9, 13, 17, 22, 26, 30)) !== false;
		case I18N_CALENDAR_MONTH_NAMES:
			return $month_names;
		case I18N_CALENDAR_WEEKDAY_NAMES:
			return $weekdays;
		case I18N_CALENDAR_PARAMETERS:
			$date = date("Y-n-j", $arguments['timestamp']);
			$date = explode('-', $date);
			$arguments = array('year' => intval($date[0]), 'month' => intval($date[1]), 'day' => intval($date[2]));
			$date = array();
			$arguments = calendar_jalali(I18N_CALENDAR_FROM_GREGORIAN, $arguments);
			$parameters = "dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZU";
			for ($i = 0; $i < strlen($parameters); $i++) {
				$date[$parameters[$i]] = date($parameters[$i]);
			}
			$date['N'] = (intval($date['N']) + 1) % 7;
			$result = array();
			for ($i = 0; $i < strlen($parameters); $i++) {
				$value = $date[$parameters[$i]];
				switch ($parameters[$i]) {
					case "d":
						$value = strval($arguments['day']);
						if (strlen($value) == 1) {
							$value = "0" . $value;
						}
						break;
					case "D":
						$value = substr($weekdays[$date['N']], 0, 3);
						break;
					case "j":
						$value = strval($arguments['day']);
						break;
					case "l":
						$value = $weekdays[$date['N']];
						break;
					case "S":
						$value = "th";
						if ($arguments['day'] < 20) {
							if ($arguments['day'] == 1) {
								$value = "st";
							} else if ($arguments['day'] == 2) {
								$value = "nd";
							} else if ($arguments['day'] == 3) {
								$value = "rd";
							}
						} else {
							if ($arguments['day'] % 10 == 1) {
								$value = "st";
							} else if ($arguments['day'] % 10 == 2) {
								$value = "nd";
							} else if ($arguments['day'] % 10 == 3) {
								$value = "rd";
							}
						}
						break;
					case "w":
						$value = $date['N'];
						break;
					case "z":
						$value = $arguments['day'];
						if ($arguments['month'] > 1) {
							for ($j = 0; $j < $arguments['month'] - 1; $j++) {
								$value += $months[$j];
							}
						}
						break;
					case "W":
						$value = floor($result['z'] / 7);
						break;
					case "F":
						$value = $month_names[$arguments['month'] - 1];
						break;
					case "m":
						$value = strval($arguments['month']);
						if (strlen($value) == 1) {
							$value = "0" . $value;
						}
						break;
					case "M":
						$value = $month_names[$arguments['month'] - 1];
						$value = substr($value, 0, 3);
						break;
					case "n":
						$value = $arguments['month'];
						break;
					case "t":
						$value = $months[$arguments['month'] - 1];
						break;
					case "L":
						$value = (array_search($arguments['year'] % 33, array(1, 5, 9, 13, 17, 22, 26, 30)) !== false) ? "1" : "0";
						break;
					case "o":
					case "Y":
						$value = strval($arguments['year']);
						break;
					case "y":
						$value = strval($arguments['year']);
						$value = substr($value, 2, 2);
						break;
					default:
						break;
				}
				$result[$parameters[$i]] = $value;
			}
			return $result;
		case I18N_CALENDAR_TO_GREGORIAN:
			$year = $arguments['year'] - 979;
			$month = $arguments['month'] - 1;
			$day = $arguments['day'] - 1;
			$jalali_days = 365 * $year + (int) ($year / 33) * 8 + (int) (($year % 33 + 3) / 4);
			for ($i = 0; $i < $month; ++$i) {
				$jalali_days += $months[$i];
			}
			$jalali_days += $day;
			$gregorian_days = $jalali_days + 79;
			$gregorian_year = 1600 + 400 * (int) ($gregorian_days / 146097);
			$gregorian_days = $gregorian_days % 146097;
			$leap = true;
			if ($gregorian_days >= 36525) {
				$gregorian_days--;
				$gregorian_year += 100 * (int) ($gregorian_days / 36524);
				$gregorian_days = $gregorian_days % 36524;
				if ($gregorian_days >= 365) {
					$gregorian_days++;
				} else {
					$leap = false;
				}
			}
			$gregorian_year += 4 * (int) ($gregorian_days / 1461);
			$gregorian_days %= 1461;
			if ($gregorian_days >= 366) {
				$leap = false;
				$gregorian_days--;
				$gregorian_year += (int) ($gregorian_days / 365);
				$gregorian_days = $gregorian_days % 365;
			}
			for ($i = 0; $gregorian_days >= $gregorian_months[$i] + ($i == 1 && $leap); $i++) {
				$gregorian_days -= $gregorian_months[$i] + ($i == 1 && $leap);
			}
			return array('year' => $gregorian_year, 'month' => $i + 1, 'day' => $gregorian_days + 1);
		case I18N_CALENDAR_FROM_GREGORIAN:
			$year = $arguments['year'] - 1600;
			$month = $arguments['month'] - 1;
			$day = $arguments['day'] - 1;
			$gregorian_days = 365 * $year + (int) (($year + 3) / 4) - (int) (($year + 99) / 100) + (int) (($year + 399) / 400);
			for ($i = 0; $i < $month; ++$i) {
				$gregorian_days += $gregorian_months[$i];
			}
			if ($month > 1 && (($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0))) {
				$gregorian_days++;
			}
			$gregorian_days += $day;
			$jalali_days = $gregorian_days - 79;
			$j_np = (int) ($jalali_days / 12053);
			$jalali_days = $jalali_days % 12053;
			$jalali_year = 979 + 33 * $j_np + 4 * (int) ($jalali_days / 1461);
			$jalali_days %= 1461;
			if ($jalali_days >= 366) {
				$jalali_year += (int) (($jalali_days - 1) / 365);
				$jalali_days = ($jalali_days - 1) % 365;
			}
			for ($i = 0; $i < 11 && $jalali_days >= $months[$i]; ++$i) {
				$jalali_days -= $months[$i];
			}
			return array('year' => $jalali_year, 'month' => $i + 1, 'day' => $jalali_days + 1);
		default:
			break;
	}
}

?>