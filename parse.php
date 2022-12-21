<?
$download_result = false;

$file_content = file_get_contents("wo_for_parse.html");
$search_fields = array("Tracking Number", "PO#", "Scheduled", "Customer", "Trade", "NTE", "Store ID", "Address", "Phone");

$append_filter = array("Scheduled" => array("datetime" => "Y-m-d H:i"),
	"Address" => array("match" => '/\W*([\w| ]*)\s+([\w| ]*?)\s([a-z]{2})\s+([0-9]{5})/msi', 'name' => array("Street", "City", "State", "Zip")),
	"Phone" => array("delete" => "/[^\d]+/msi"),
	"NTE" => array("delete" => "/[^\d.]+/msi")
);
$header_csv_file = '';
$value_string = '';

if ($download_result) {
	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=file.csv");
}

foreach ($search_fields as $field) {

	if (preg_match_all('/<[^>]*>\s*(' . $field . '[^<]*)<\/[^>]*>\s*<h[^>]*>(.*?)<\/[^>]*>/ism', $file_content, $match)) {
		if (!isset($append_filter[$field]['match'])) {
			$header_csv_file .= $field . ';';
		}
		$prepare_value = '';

		if (count($match[1]) > 1) {
			$min_index = 0;
			$min_length = PHP_INT_MAX;
			foreach ($match[1] as $key => $item) {
				if (strlen($item) > $min_length) {
					$min_length = strlen($item);
					$min_index = $key;
				}
			}
			$prepare_value = strip_tags($match[2][$min_index]);
		} else {
			$prepare_value = strip_tags($match[2][0]);
		}

		if (isset($append_filter[$field])) {

			if (isset($append_filter[$field]['delete'])) {
				$value_string .= preg_replace($append_filter[$field]['delete'], '', $prepare_value) . ';';
			} elseif (isset($append_filter[$field]['datetime'])) {
				$prepare_value = preg_replace('/[\s]+/ism', ' ', $prepare_value);
				$value_string .= date($append_filter[$field]['datetime'], strtotime($prepare_value)) . ';';
			} elseif (isset($append_filter[$field]['match'])) {
				if (preg_match($append_filter[$field]['match'], $prepare_value, $match_details)) {
					foreach ($append_filter[$field]['name'] as $num => $name) {
						$value_string .= $match_details[$num + 1] . ';';
						$header_csv_file .= $name . ';';
					}
				}
			}

		} else {
			$value_string .= trim($prepare_value) . ';';
		};
	}
}
echo $header_csv_file . ($download_result ? "\r\n" : '<br>');
echo $value_string;

?>