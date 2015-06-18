<?php if (!defined('FW')) die('Forbidden');

/**
 * @param int|object $post
 * @param $key
 * @param null $default
 * @return mixed
 */
function fw_ext_mega_menu_get_meta($post, $key, $default = null) {
	return _fw_ext_mega_menu_meta($post, $key, $default);
}

function fw_ext_mega_menu_update_meta($post, array $array) {
	return _fw_ext_mega_menu_meta($post, $array, null, true);
}

/**
 * Check if menu item is a MegaMenu item or is inside a MegaMenu item
 * @param WP_Post $item
 * @return bool
 */
function fw_ext_mega_menu_is_mm_item($item) {
	$cache_key = fw_ext('megamenu')->get_cache_key('/mm_item');

	try {
		$mm_items = FW_Cache::get($cache_key);
	} catch (FW_Cache_Not_Found_Exception $e) {
		$mm_items = array();
	}

	if (isset($mm_items[$item->ID])) {
		return $mm_items[$item->ID];
	}

	$cursor_item = array(
		'id' => $item->ID,
		'parent' => $item->menu_item_parent,
	);

	do {
		$is_mm_item = fw_ext_mega_menu_get_meta($cursor_item['id'], 'enabled');
	} while(
		!$is_mm_item
		&&
		intval($cursor_item['parent']) !== 0
		&&
		($cursor_item = get_post($cursor_item['parent']))
		&&
		($cursor_item = array(
			'id' => $cursor_item->ID,
			'parent' => get_post_meta( $cursor_item->ID, '_menu_item_menu_item_parent', true )
		))
	);

	$mm_items[$item->ID] = (bool)$is_mm_item;

	FW_Cache::set($cache_key, $mm_items);

	return $mm_items[$item->ID];
}
