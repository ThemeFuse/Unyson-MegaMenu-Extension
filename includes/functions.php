<?php if (!defined('FW')) die('Forbidden');

/**
 * @internal
 */
function _fw_mega_menu_meta($post, $key, $default = null, $write = false)
{
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

function fw_mega_menu_name_meta($post, $key)
{
	$post_id = is_object($post) ? $post->ID : $post;

	return "mega-menu[$post_id][$key]";
}

function fw_mega_menu_request_meta($post)
{
	$post_id = is_object($post) ? $post->ID : $post;

	return (array) @$_POST['mega-menu'][$post_id];
}

function fw_mega_menu_get_meta($post, $key, $default = null)
{
	return _fw_mega_menu_meta($post, $key, $default);
}

function fw_mega_menu_update_meta($post, array $array)
{
	return _fw_mega_menu_meta($post, $array, null, true);
}
