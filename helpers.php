<?php if (!defined('FW')) die('Forbidden');

function fw_ext_mega_menu_get_meta($post, $key, $default = null) {
	return _fw_ext_mega_menu_meta($post, $key, $default);
}

function fw_ext_mega_menu_update_meta($post, array $array) {
	return _fw_ext_mega_menu_meta($post, $array, null, true);
}
