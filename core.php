<?php
class Pack {
	public $botName, $number, $downloads, $size, $name;
	function __construct(&$botName, $number, $downloads, $size, $name) {
		$this->botName = &$botName;
		$this->number = $number;
		$this->downloads = $downloads;
		$this->size = $size;
		$this->name = $name;
	}
}
class Column {
	public $header, $function;
	function __construct($header, $function) {
		$this->header = $header;
		$this->function = $function;
	}
}

// prepare search query
global $wp_query;
if ($wp_query->query_vars['xdccs'] !== '') {
	$queryTerms = explode(' ', preg_quote(urldecode($wp_query->query_vars['xdccs']), '/'));
	foreach ($queryTerms as $key => $queryTerm) {
		$queryTerms[$key] = '(?=.*' . $queryTerm . ')';
	}
	$query = '/' . implode($queryTerms) . '/i';
}

// prepare requested bot packs
$listFiles = PrepareListFiles();
$packs = array();
foreach ($listFiles as $listFile) {
	$handle = fopen($listFile, 'r');
	if ($handle) {
		$botName = '';
		if (fgets($handle) !== false // skip first two useless lines
			&& fgets($handle) !== false
			&& ($line = fgets($handle)) !== false // 3rd line exists and contains the bot name
			&& preg_match("/\/MSG (.+?) /", $line, $match)) {
			$botName = $match[1];
			
			while (($line = fgets($handle)) !== false // continue if line exists...
				   && preg_match("/^#(\d+) +(\d+)x +\[ *(.+?)\] +(.+)$/", $line, $match)) { // ...and is another pack
				if (!isset($query) // if there's no search going on...
					|| preg_match($query, $match[4])) { // ...or if the file name matches what's being searched for
					array_push($packs, new Pack($botName, $match[1], $match[2], $match[3], $match[4])); // create pack object and add it to pack array
				}
			}
		}
	}
	fclose($handle);
}
usort($packs, function($a, $b) // sort pack objects by their name value
{
    return strcmp($a->name, $b->name); // not UTF-8 aware yet
}); 

$columns = PrepareColumns();
?>

<form role="search" method="get" class="search-form" action="<?php echo plugin_dir_url(__FILE__); ?>search-redirect.php">
	<label>
		<span class="screen-reader-text"><?php echo _x( 'Search for:', 'label' ) ?></span>
		<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search …', 'placeholder' ) ?>" value="<?php echo get_search_query() ?>" name="xdccs" title="<?php echo esc_attr_x( 'Search for:', 'label' ) ?>" />
	</label>
	<input type="submit" class="search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button' ) ?>" />
</form>

<?php if (!empty($packs)) { // output requested bot packs ?>
<table class="xdcc-table">
	<tr class="xdcc-row-header">
<?php foreach ($columns as $column) { ?>
		<th class="xdcc-row-header-<?php echo sanitize_title($column->header); ?>"><?php echo $column->header; ?></th>
<?php } ?>
	</tr>
<?php
foreach ($packs as $pack) { ?>
	<tr class="xdcc-row-pack" onclick="alert('/MSG <?php echo $pack->botName; ?> XDCC SEND <?php echo $pack->number; ?>');">
<?php foreach ($columns as $column) { ?>
		<td class="xdcc-data-pack-<?php echo sanitize_title($column->header); ?>"><?php call_user_func($column->function, $pack); ?></td>
<?php } ?>
	</tr>
<?php } ?>
</table>
<?php }

function PrepareColumns() { // get columns as set in WordPress back-end and output array with Column objects
	$userFunctionsBase = 'user-functions';
	if (stream_resolve_include_path("{$userFunctionsBase}.php")) {
		include "{$userFunctionsBase}.php";
	} else {
		include "{$userFunctionsBase}-sample.php";
	}
	$optionColumns = preg_split('/\R/', get_option('columns'));
	$columns = array();
	foreach ($optionColumns as $optionColumn) {
		$optionColumnSplit = preg_split('/=/', $optionColumn);
		array_push($columns, new Column($optionColumnSplit[0], $optionColumnSplit[1]));
	}
	return $columns;
}

function PrepareListFiles() { // get list files as set in WordPress back-end and output array with list file paths
	return preg_split('/\R/', get_option('list_files'));
}