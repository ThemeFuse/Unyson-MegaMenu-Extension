<?php if (!defined('FW')) die('Forbidden');

/**
 * @deprecated
 * @param $post
 * @param $key
 * @return string
 */
function fw_mega_menu_name_meta($post, $key) {
	return _fw_mega_menu_admin_input_name($post, $key);
}

/**
 * @deprecated
 * @param $post
 * @return array
 */
function fw_mega_menu_request_meta($post) {
	return _fw_mega_menu_admin_input_POST_values($post);
}
