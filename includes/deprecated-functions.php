<?php if (!defined('FW')) die('Forbidden');

/**
 * @deprecated
 * @param $post
 * @param $key
 * @param null $default
 * @return mixed
 */
function fw_mega_menu_get_meta($post, $key, $default = null) {
	return fw_ext_mega_menu_get_meta($post, $key, $default);
}

/**
 * @deprecated
 * @param $post
 * @param array $array
 * @return mixed
 */
function fw_mega_menu_update_meta($post, array $array) {
	return fw_ext_mega_menu_update_meta($post, $array);
}

/**
 * @deprecated
 * @param $post
 * @param $key
 * @return string
 */
function fw_mega_menu_name_meta($post, $key) {
	return _fw_ext_mega_menu_admin_input_name($post, $key);
}

/**
 * @deprecated
 * @param $post
 * @return array
 */
function fw_mega_menu_request_meta($post) {
	return _fw_ext_mega_menu_admin_input_POST_values($post);
}

/**
 * @deprecated
 * @param $post
 * @param $key
 * @param null $default
 * @param bool $write
 * @return mixed
 */
function _fw_mega_menu_meta($post, $key, $default = null, $write = false) {
	return _fw_ext_mega_menu_meta($post, $key, $default, $write);
}