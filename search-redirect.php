<?php require_once '../../../wp-load.php';
header('Location: ' . home_url() . '/xdcc/search/' . esc_attr(wp_unslash($_GET['xdccs']))); ?>