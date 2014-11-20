<?php
/*
Plugin Name: XDWC
Description: Includes XDCC page via "[xdcc]"
*/

add_shortcode('xdcc', 'get_xdcc');

function get_xdcc() {
    ob_start();
        include('core.php');
    $mail = ob_get_clean();
 
    return $mail;
}

function create_new_url_querystring()
{
    add_rewrite_rule(
        '^xdcc/search(/([^/]*))?$',
        'index.php?pagename=xdcc&xdccs=$matches[2]',
        'top'
    );
	add_rewrite_tag('%xdccs%','([^/]*)');
	flush_rewrite_rules();
}
add_action('init', 'create_new_url_querystring');

function add_xdwcmenu(){
    	add_options_page('XDWC', 'XDWC', 'manage_options', 'xdwc-settings', 'xdwc_settings');
}

if (is_admin()) {
	add_action('admin_menu', 'add_xdwcmenu');
	add_action('admin_init', 'register_xdwcsettings');
}

function register_xdwcsettings() {
	register_setting('xdwc-group', 'list_files');
	register_setting('xdwc-group', 'columns');
  //register_setting( 'myoption-group', 'some_other_option' );
  //register_setting( 'myoption-group', 'option_etc' );
}


function xdwc_settings(){ ?>
<div class="wrap">
	<h2>XDWC</h2>
	<form method="post" action="options.php">
<?php settings_fields('xdwc-group');
do_settings_sections('xdwc-group'); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="list_files">List Files</label></th>
				<td>
					<label for="list_files">One file per line. For example: /home/mybot/mybot.txt</label>
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
						$pack is an object with the following properties: botName, number, downloads, size and name.<br />
						If there is no user-functions.php present, user-functions-sample.php will be used.
					</label>
					<textarea name="columns" id="columns" class="large-text code" rows="3" placeholder="Header=Function" ><?php echo get_option('columns'); ?></textarea>
				</td>
			</tr>
		</table>
		<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
	</form>
</div>
<?php }