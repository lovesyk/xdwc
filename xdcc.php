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

add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
	add_options_page('My Options', 'xdcc', 'manage_options', 'my-plugin.php', 'my_plugin_page');
}
?>