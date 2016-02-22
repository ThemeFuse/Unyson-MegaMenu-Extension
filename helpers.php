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

/** Item Options */
{
	/**
	 * @param WP_Post $item
	 * @return array
	 */
	function fw_ext_megamenu_item_options($item) {
		return apply_filters('fw:ext:megamenu:item-options', array(), $item);
	}

	/**
	 * Get item option value from the database
	 *
	 * @param WP_Post|int $item
	 * @param string|null $option_id Specific option id (accepts multikey). null - all options
	 * @param null|mixed $default_value If no option found in the database, this value will be returned
	 * @param null|bool $get_original_value Original value is that with no translations and other changes
	 *
	 * @return mixed|null
	 */
	function fw_ext_megamenu_get_db_item_option(
		$item = null,
		$option_id = null,
		$default_value = null,
		$get_original_value = null
	) {
		$meta_key = 'fw:ext:megamenu:item-options';

		if ( ! $item ) {
			/** @var WP_Post $post */
			global $post;

			if ( ! $post || $post->post_type != 'nav_menu_item' ) {
				return $default_value;
			} else {
				$item = $post;
			}
		} elseif ( ! $item instanceof WP_Post ) {
			if (
				($post = get_post($item))
				&&
				$post->post_type == 'nav_menu_item'
			) {
				$item = $post;
			} else {
				return $default_value;
			}
		}

		$options = fw_extract_only_options(fw_ext_megamenu_item_options($item));

		if ($option_id) {
			$option_id = explode('/', $option_id); // 'option_id/sub/keys'
			$_option_id = array_shift($option_id); // 'option_id'
			$sub_keys  = implode('/', $option_id); // 'sub/keys'
			$option_id = $_option_id;
			unset($_option_id);

			$value = FW_WP_Meta::get(
				'post',
				$item->ID,
				$meta_key .'/' . $option_id,
				null,
				$get_original_value
			);

			if (isset($options[$option_id])) {
				$value = fw()->backend->option_type($options[$option_id]['type'])->storage_load(
					$option_id,
					$options[$option_id],
					$value,
					array( 'post-id' => $item->ID, )
				);
			}

			if ($sub_keys) {
				return fw_akg($sub_keys, $value, $default_value);
			} else {
				return is_null($value) ? $default_value : $value;
			}
		} else {
			$value = FW_WP_Meta::get(
				'post',
				$item->ID,
				$meta_key,
				$default_value,
				$get_original_value
			);

			if (!is_array($value)) {
				$value = array();
			}

			foreach ($options as $_option_id => $_option) {
				$value[$_option_id] = fw()->backend->option_type($_option['type'])->storage_load(
					$_option_id,
					$_option,
					isset($value[$_option_id]) ? $value[$_option_id] : null,
					array( 'post-id' => $item->ID, )
				);
			}

			return $value;
		}
	}

	/**
	 * Set item option value in database
	 *
	 * @param WP_Post|int $item
	 * @param string|null $option_id Specific option id (accepts multikey). null - all options
	 * @param $value
	 */
	function fw_ext_megamenu_set_db_item_option( $item = null, $option_id = null, $value ) {
		if ( ! $item ) {
			/** @var WP_Post $post */
			global $post;

			if ( ! $post || $post->post_type != 'nav_menu_item' ) {
				return;
			} else {
				$item = $post;
			}
		} elseif ( ! $item instanceof WP_Post ) {
			if (
				($post = get_post($item))
				&&
				$post->post_type == 'nav_menu_item'
			) {
				$item = $post;
			} else {
				return;
			}
		}

		$options = fw_extract_only_options(fw_ext_megamenu_item_options($item));

		$sub_keys = null;

		if ($option_id) {
			$option_id = explode('/', $option_id); // 'option_id/sub/keys'
			$_option_id = array_shift($option_id); // 'option_id'
			$sub_keys  = implode('/', $option_id); // 'sub/keys'
			$option_id = $_option_id;
			unset($_option_id);

			$old_value = fw_ext_megamenu_set_db_item_option($item, $option_id);

			if ($sub_keys) { // update sub_key in old_value and use the entire value
				$new_value = $old_value;
				fw_aks($sub_keys, $value, $new_value);
				$value = $new_value;
				unset($new_value);

				$old_value = fw_akg($sub_keys, $old_value);
			}

			if (isset($options[$option_id])) {
				$value = fw()->backend->option_type($options[$option_id]['type'])->storage_save(
					$option_id,
					$options[$option_id],
					$value,
					array( 'post-id' => $item->ID, )
				);
			}

			FW_WP_Meta::set( 'post', $item->ID, 'fw_options/'. $option_id, $value );
		} else {
			$old_value = fw_get_db_post_option($item->ID);

			if (!is_array($value)) {
				$value = array();
			}

			foreach ($value as $_option_id => $_option_value) {
				if (isset($options[$_option_id])) {
					$value[$_option_id] = fw()->backend->option_type($options[$_option_id]['type'])->storage_save(
						$_option_id,
						$options[$_option_id],
						$_option_value,
						array( 'post-id' => $item->ID, )
					);
				}
			}

			FW_WP_Meta::set( 'post', $item->ID, 'fw_options', $value );
		}

		/**
		 * @since 1.1.0
		 */
		do_action('fw:ext:megamenu:item-options:update',
			$item->ID,
			/**
			 * Option id
			 * First level multi-key
			 *
			 * For e.g.
			 *
			 * if $option_id is 'hello/world/7'
			 * this will be 'hello'
			 */
			$option_id,
			/**
			 * The remaining sub-keys
			 *
			 * For e.g.
			 *
			 * if $option_id is 'hello/world/7'
			 * $option_id_keys will be array('world', '7')
			 *
			 * if $option_id is 'hello'
			 * $option_id_keys will be array()
			 */
			explode('/', $sub_keys),
			/**
			 * Old post option(s) value
			 * @since 2.3.3
			 */
			$old_value
		);
	}
}
