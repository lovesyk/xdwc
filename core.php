<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

function PackBotName($pack) {
	echo $pack->botName;
}
function PackNumber($pack) {
	echo $pack->number;
}
function PackSize($pack) {
	echo $pack->size;
}
function PackName($pack) {
	echo $pack->name;
}
function PackDownloads($pack) {
	echo $pack->downloads;
}

class Column {
	public $header, $function;
	function __construct($header, $function) {
		$this->header = $header;
		$this->function = $function;
	}
}

function GetQuery() {
	$queryString = GetQueryString();
	$queryTerms = preg_split('/\s+/', preg_quote($queryString, '/'), -1, PREG_SPLIT_NO_EMPTY); // split query string into terms at any kind of whitespace and do not keep empty ones
	foreach ($queryTerms as $key => $queryTerm) {
		$queryTerms[$key] = '(?=.*' . $queryTerm . ')'; // add "AND" operator to each query term
	}
	$preQuery = implode($queryTerms); // merge query terms back together into a single regular expression
	if ($preQuery !== '') // if there's still anything inside, there's a search going on
	{
		return "/{$preQuery}/i"; // output full regular expression to search for
	} else {
		return null; // disable search later
	}
}

function CheckFile($file) {
	return is_file($file);
}

function FetchRemoteListFile($url, $destination) { // fetch remote list file from $url and put it as $destination locally
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$temp_file = download_url($url, LISTFILETIMEOUT);
	if (!is_wp_error($temp_file)) {
		rename($temp_file, $destination);
		return $destination;
	}
	return false;
}

function PrepareListFile($listFile) { // check if specified file exists locally and if not, fetch the remote location first
	$listFileLocal = realpath($listFile);
	if (CheckFile($listFileLocal)) { // if list file exists on the local system, use it right away
		return $listFileLocal; 
	} else {
		$localTarget = XDWCTEMP . md5($listFile) . '.txt';
		if (CheckFile($localTarget) && (time() - filemtime($localTarget)) < LISTFILECACHEPERIOD) { // cache hit: cached list file exists locally and is still valid
			return $localTarget;
		} else {
			return FetchRemoteListFile($listFile, $localTarget);
		} 
	}
}

// main code

$query = GetQuery(); // get the regular expression to search for
$listFiles = GetListFiles(); // get an array of list files

// get requested packs
$packs = array();
foreach ($listFiles as $listFile) {
	if ($listFile = PrepareListFile($listFile)) {
		$handle = fopen($listFile, 'r');
		if (fgets($handle) !== false // skip first two useless lines
			&& fgets($handle) !== false
			&& ($line = fgets($handle)) !== false // 3rd line exists and contains the bot name
			&& preg_match("/\/MSG (.+?) /", $line, $match)) {
			$botName = $match[1];
			
			while (($line = fgets($handle)) !== false // continue if line exists...
				   && preg_match("/^#(\d+) +(\d+)x +\[ *(.+?)\] +(.+)$/", $line, $match)) { // ...and is another pack
				if ($query === null // if there's no search going on...
					|| preg_match($query, $match[4])) { // ...or if the file name matches what's being searched for
					array_push($packs, new Pack($botName, $match[1], $match[2], $match[3], $match[4])); // create Pack object and add it to pack array
				}
			}
			unset($botName);
		}
		fclose($handle);
	}
}
usort($packs, function($a, $b) // sort Pack objects by their name value
{
    return strcmp($a->name, $b->name); // not UTF-8 aware yet
}); 

$columns = GetColumns(); // get an array of Column objects

// output actual HTML ?>
<form role="search" method="get" class="search-form" action="<?php echo plugin_dir_url(__FILE__); ?>search-redirect.php">
	<label>
		<span class="screen-reader-text"><?php echo _x( 'Search for:', 'label' ) ?></span>
		<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search …', 'placeholder' ) ?>" value="<?php echo get_search_query() ?>" name="xdwcs" title="<?php echo esc_attr_x( 'Search for:', 'label' ) ?>" />
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