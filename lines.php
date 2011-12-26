<?php
/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (11/12/11, 19:38)
 */

/**
 * @param $path
 * @return array
 */
function get_lines($path) {
	$dir = opendir($path);
	$sum = array('php' => 0, 'css' => 0, 'js' => 0, 'xml' => 0,);
	while ($file = readdir($dir)) {
		if ($file[0] == ".") {
			continue;
		}
		if ($file == "library") {
			continue;
		}
		if (is_file($path . "/" . $file)) {
			$info = pathinfo($path . "/" . $file);
			if ($info['extension'] != "php" && $info['extension'] != "xml" && $info['extension'] != "css" && $info['extension'] != "js") {
				continue;
			}
			$sum[$info['extension']] += count(explode("\n", file_get_contents($path . "/" . $file)));
		} else if (is_dir($path . "/" . $file)) {
			$current = get_lines($path . "/" . $file);
			foreach ($sum as $case => $lines) {
				$sum[$case] += $current[$case];
			}
		}
	}
	closedir($dir);
	$total = 0;
	foreach ($sum as $case => $lines) {
		$total += $sum[$case];
	}
	$sum['total'] = $total;
	return $sum;
}

$lines = get_lines(dirname(__FILE__));

echo("<ul>
	<li><strong>PHP:</strong>$lines[php]</li>
	<li><strong>JavaScript:</strong>$lines[js]</li>
	<li><strong>CSS:</strong>$lines[css]</li>
	<li><strong>XML:</strong>$lines[xml]</li>
	<li><strong>Total:</strong>$lines[total]</li>
</ul>");

?>
 
