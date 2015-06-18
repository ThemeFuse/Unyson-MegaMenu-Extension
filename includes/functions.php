<?php if (!defined('FW')) die('Forbidden');

/**
 * @internal
 * @param int|object $post
 * @param $key
 * @param null $default
 * @param bool $write
 * @return mixed
 */
function _fw_ext_mega_menu_meta($post, $key, $default = null, $write = false) {
	static $meta = array();

	$post_id = is_object($post) ? $post->ID : $post;

	if (!isset($meta[$post_id])) {
		$meta[$post_id] = (array) get_post_meta($post_id, 'mega-menu', true);
	}

	if ($write) {
		if (is_array($key)) {
			$meta[$post_id] = array_filter(array_merge($meta[$post_id], $key));
		}
		else {
			$meta[$post_id][$key] = $default;
			$meta[$post_id][$key] = array_filter($meta[$post_id][$key]);
		}
		fw_update_post_meta($post_id, 'mega-menu', $meta[$post_id]);
		return null;
	}

	return isset($meta[$post_id][$key]) ? $meta[$post_id][$key] : $default;
}

/**
 * @param $post
 * @param $key
 * @return string
 * @internal
 */
function _fw_ext_mega_menu_admin_input_name($post, $key) {
	$post_id = is_object($post) ? $post->ID : $post;

	return "mega-menu[$post_id][$key]";
}

/**
 * @param $post
 * @return array
 * @internal
 */
function _fw_ext_mega_menu_admin_input_POST_values($post) {
	$post_id = is_object($post) ? $post->ID : $post;

	return (array)fw_akg('mega-menu/'. $post_id, $_POST);
}
