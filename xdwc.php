<?php
/*
Plugin Name: XDWC
Description: Includes XDCC page via "[xdwc]"
*/

add_shortcode('xdwc', function () {
	ob_start();
	include 'core.php';
	return ob_get_clean();
});

function GetColumns() { // get columns as set in WordPress back-end and output array with Column objects
	if (stream_resolve_include_path('user-functions.php')) {
		include "{$userFunctionsBase}.php";
	}
	$optionColumns = preg_split('/\R/', get_option('columns'));
	$columns = array();
	foreach ($optionColumns as $optionColumn) {
		$optionColumnSplit = preg_split('/=(?!.*=)/u', $optionColumn);
		array_push($columns, new Column($optionColumnSplit[0], $optionColumnSplit[1]));
	}
	return $columns;
}

function GetListFiles() { // get list files as set in WordPress back-end and output array with list file paths
	return preg_split('/\R/', get_option('list_files'));
}

function GetQueryString() {
	global $wp_query;
	if (isset($wp_query->query_vars['xs'])) {
		return urldecode($wp_query->query_vars['xs']);
	} else {
		return '';
	}
}

define('LISTFILECACHEPERIOD', get_option('list_file_cache_period'));
define('XDWCTEMP', get_temp_dir());
define('LISTFILETIMEOUT', 3);//get_option('list_file_timeout'));

add_action('init', function () {
	add_rewrite_rule(
		'^([^/]*)/search(/([^/]*))?$',
		'index.php?pagename=$matches[1]&xs=$matches[3]',
		'top'
	);
	add_rewrite_tag('%xs%','([^/]*)');
	flush_rewrite_rules();
});

if (is_admin()) {
	add_action('admin_menu', function () {
		add_options_page('XDWC', 'XDWC', 'manage_options', 'xdwc-settings', 'xdwc_settings');
	});
	add_action('admin_init', function () {
		register_setting('xdwc-group', 'list_files');
		register_setting('xdwc-group', 'columns');
		register_setting('xdwc-group', 'list_file_cache_period');
		register_setting('xdwc-group', 'list_file_timeout');
	});
}

function xdwc_settings() { ?>
<div class="wrap">
	<h2>XDWC</h2>
	<form method="post" action="options.php">
<?php settings_fields('xdwc-group');
do_settings_sections('xdwc-group'); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="list_files">List Files</label></th>
				<td>
					<label for="list_files">One file per line. For example: /home/mybot/mybot.txt or http://mybot.org/mybot.txt</label>
					<textarea name="list_files" id="list_files" class="large-text code" rows="3" placeholder="/home/me/mybot.txt" ><?php echo get_option('list_files'); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="columns">Columns</label></th>
				<td>
					<label for="columns">
						One column per line. Format: Header=Function<br />
						Header: Title used for the column header<br />
						Function: A function inside of user-functions.php in xdwc's directory that takes $pack as input and echoes what the column should display.<br />
						$pack is an object with the following properties: botName, number, downloads, size and name.
					</label>
					<textarea name="columns" id="columns" class="large-text code" rows="6" placeholder="Header=Function" ><?php echo get_option('columns'); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="list_file_cache_period">List File Cache Period</label></th>
				<td>
					<label for="list_file_cache_period">List files from remote locations will get cached for <input name="list_file_cache_period" type="number" step="1" min="0" id="list_file_cache_period" value="<?php echo get_option('list_file_cache_period'); ?>" class="small-text" /> seconds before being fetched again.</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="list_file_timeout">List File Timeout</label></th>
				<td>
					<label for="list_file_timeout">The timeout for fetching remote list files will be <input name="list_file_timeout" type="number" step="1" min="0" id="list_file_timeout" value="<?php echo get_option('list_file_timeout'); ?>" class="small-text" /> seconds.</label>
				</td>
			</tr>
		</table>
		<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
	</form>
</div>
<?php }