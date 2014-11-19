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
	public $name, $file;
	function __construct($name, $file) {
		$this->name = $name;
		$this->file = $file;
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
$listFiles = preg_split('/\R/', get_option('list_files'));
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

// prepare additional columns
$optionAdditionalColumns = preg_split('/\R/', get_option('additional_columns'));
$additionalColumns = array();
foreach ($optionAdditionalColumns as $optionAdditionalColumn) {
	$optionAdditionalColumnSplit = preg_split('/=/', $optionAdditionalColumn);
	array_push($additionalColumns, new Column($optionAdditionalColumnSplit[0], $optionAdditionalColumnSplit[1]));
}
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
		<th class="xdcc-row-header-botname">Bot Name</th>
		<th class="xdcc-row-header-packnumber">Pack Number</th>
		<th class="xdcc-row-header-packsize">File Size</th>
		<th class="xdcc-row-header-packname">File Name</th>
<?php foreach ($additionalColumns as $additionalColumn) { ?>
		<th class="xdcc-data-pack-<?php echo sanitize_title($additionalColumn->name); ?>"><?php echo $additionalColumn->name; ?></th>
<?php } ?>
	</tr>
<?php
foreach ($packs as $pack) { ?>
	<tr class="xdcc-row-pack" onclick="alert('/MSG <?php echo $pack->botName; ?> XDCC SEND <?php echo $pack->number; ?>');">
		<td class="xdcc-data-pack-botname"><?php echo $pack->botName; ?></td>
		<td class="xdcc-data-pack-packnumber"><?php echo $pack->number; ?></td>
		<td class="xdcc-data-pack-packsize"><?php echo $pack->size; ?></td>
		<td class="xdcc-data-pack-packname"><?php echo $pack->name; ?></td>
<?php foreach ($additionalColumns as $additionalColumn) { ?>
		<td class="xdcc-data-pack-<?php echo sanitize_title($additionalColumn->name); ?>"><?php include $additionalColumn->file; ?></td>
<?php } ?>
	</tr>
<?php } ?>
</table>
<?php }