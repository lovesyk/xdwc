<?php require '../../../wp-load.php'; // better ugly code than ugly use experience
header('Location: ' . home_url() . '/xdcc/search/' . esc_attr(wp_unslash($_GET['xdwcs']))); ?>