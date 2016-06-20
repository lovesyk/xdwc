<?php
/*
Plugin Name: XDWC
Description: Includes XDCC page via "[xdwc]"
*/

add_shortcode( 'xdwc',
	function () {
		//global $post;
		//echo    $post_slug=$post->post_name;;
		ob_start();
		include 'core.php';

		return ob_get_clean();
	} );

function xdwc_get_columns() { // get columns as set in WordPress back-end, sanitize them and output array of Column objects
	if ( stream_resolve_include_path( 'user-functions.php' ) ) {
		include 'user-functions.php';
	}

	$columns = array();
	if ( preg_match_all( '/^\s*(\S.*?)?\s*=\s*([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*$/m',
		/* ^\s* matches any amount of whitespace in the beginning
		   (\S.*?)?\s*= optionally matches the column header which is everything up to the last whitespace(s) before =
		   ([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*$ matches a valid php function name and trims the whitespaces at the end
		 */
		get_option( 'columns' ), $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			array_push( $columns, new Column( $match[1], $match[2] ) );
		}
	}

	return $columns;
}

function xdwc_get_list_files() { // get list files as set in WordPress back-end, sanitize them  and output array of list file paths
	return preg_split( '/\s*(^|$)\s*/m', get_option( 'list_files' ), null, PREG_SPLIT_NO_EMPTY );
	/* bit ugly but works
       Split the list files string on either beginning or end of a line with any amount of whitespace around it
	 */
}

function xdwc_get_query_string() {
	global $wp_query;
	if ( isset( $wp_query->query_vars['xs'] ) ) {
		$query = urldecode( $wp_query->query_vars['xs'] );
		$query_sanitized = preg_replace('/^\s*(\S.*?)\s*$/', '$1', $query, 1, $count);
		/* ^\s* matches any amount of whitespace in the beginning and will get trimmed as it's not part of the capture group
		   \S.*? matches at least one non-whitespace character which is the query we want and are capturing as $1
		   \s*$ once again trims whitespace, but at the end now
		*/
		if ($count) { // if preg_replace didn't match, the search query was empty
			return $query_sanitized;
		}
	}

	return false; // either no search was done or the sanitized query was empty
}

define( 'XDWC_LIST_FILE_CACHE_PERIOD', get_option( 'list_file_cache_period' ) );
define( 'XDWC_TEMP', get_temp_dir() );
define( 'XDWC_LIST_FILE_TIMEOUT', get_option( 'list_file_timeout' ) );
define( 'XDWC_PAGE_SLUG', get_option( 'list_file_timeout' ) );

add_action( 'init', function () {
	add_rewrite_rule(
		'^([^/]*)/search(/([^/]*))?$',
		'index.php?pagename=$matches[1]&xs=$matches[3]',
		'top'
	);
	add_rewrite_tag( '%xs%', '([^/]*)' );
	flush_rewrite_rules();
} );

if ( is_admin() ) {
	add_action( 'admin_menu', function () {
		add_options_page( 'XDWC', 'XDWC', 'manage_options', 'xdwc-settings', 'xdwc_settings' );
	} );
	add_action( 'admin_init', function () {
		register_setting( 'xdwc-group', 'list_files' );
		register_setting( 'xdwc-group', 'columns' );
		register_setting( 'xdwc-group', 'list_file_cache_period' );
		register_setting( 'xdwc-group', 'list_file_timeout' );
	} );
}

function xdwc_settings() {
	?>
	<div class="wrap">
		<h2>XDWC</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'xdwc-group' );
			do_settings_sections( 'xdwc-group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="list_files">List Files</label></th>
					<td>
						<label for="list_files">One file per line. For example: /home/mybot/mybot.txt or
							http://mybot.org/mybot.txt</label>
						<textarea name="list_files" id="list_files" class="large-text code" rows="3"
						          placeholder="/home/me/mybot.txt"><?php echo get_option( 'list_files' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="columns">Columns</label></th>
					<td>
						<label for="columns">
							One column per line. Format: Header=Function<br/>
							Header: Title used for the column header<br/>
							Function: A function inside of user-functions.php in xdwc's directory that takes $pack as
							input and echoes what the column should display.<br/>
							$pack is an object with the following properties: botName, number, downloads, size and name.
						</label>
						<textarea name="columns" id="columns" class="large-text code" rows="6"
						          placeholder="Header=Function"><?php echo get_option( 'columns' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="list_file_cache_period">List File Cache Period</label></th>
					<td>
						<label for="list_file_cache_period">List files from remote locations will get cached for <input
								name="list_file_cache_period" type="number" step="1" min="0" id="list_file_cache_period"
								value="<?php echo get_option( 'list_file_cache_period' ); ?>" class="small-text"/>
							seconds before being fetched again.</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="list_file_timeout">List File Timeout</label></th>
					<td>
						<label for="list_file_timeout">The timeout for fetching remote list files will be <input
								name="list_file_timeout" type="number" step="1" min="0" id="list_file_timeout"
								value="<?php echo get_option( 'list_file_timeout' ); ?>" class="small-text"/>
							seconds.</label>
					</td>
				</tr>
			</table>
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
		</form>
	</div>
<?php }