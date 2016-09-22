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
	if (!is_object($item)) {
		if ($item = get_post($item)) {
			$item = wp_setup_nav_menu_item($item);
		} else {
			return false;
		}
	}

	try {
		$mm_items = FW_Cache::get( $cache_key = fw_ext('megamenu')->get_cache_key('/mm_item') );
	} catch (FW_Cache_Not_Found_Exception $e) {
		$mm_items = array();
	}

	if (array_key_exists($item->ID, $mm_items)) {
		return $mm_items[$item->ID];
	}

	$level = 0;
	$cursor_item = array(
		'id' => $item->ID,
		'parent' => intval($item->menu_item_parent),
	);

	do {
		++$level;
		$mm_items[ $cursor_item['id'] ] = 0; // cache all parsed items to prevent posts query on next function call
	} while(
		/**
		 * Only first level parent item can have the "Use as MegaMenu" checkbox.
		 * Other level items also can have set this checkbox when they were on first level,
		 * but it is hidden and must be ignored.
		 */
		$cursor_item['parent'] !== 0
		&&
		($cursor_item = get_post($cursor_item['parent']))
		&&
		($cursor_item = array(
			'id' => $cursor_item->ID,
			'parent' => intval(get_post_meta( $cursor_item->ID, '_menu_item_menu_item_parent', true ))
		))
	);

	$mm_items[$item->ID] = (fw_ext_mega_menu_get_meta($cursor_item['id'], 'enabled') ? $level : 0);

	FW_Cache::set($cache_key, $mm_items);

	return $mm_items[$item->ID];
}

/**
 * Item Options
 * @since 1.1.0
 */
class FW_Db_Options_Model_MegaMenu extends FW_Db_Options_Model {
	protected function get_id()
	{
		return 'megamenu';
	}

	protected function get_fw_storage_params($item_id, array $extra_data = array()) {
		return array( 'megamenu-item' => $item_id );
	}

	/**
	 * @return FW_Extension_Megamenu
	 */
	private function ext() {
		return fw_ext('megamenu');
	}

	protected function _get_cache_key($key, $item_id, array $extra_data = array())
	{
		if ($key === 'options') {
			return '';
		} else {
			return parent::_get_cache_key($key, $item_id, $extra_data);
		}
	}

	protected function get_options($item_id, array $extra_data = array())
	{
		$options = array(
			'type' => array('type' => 'text') // one of the below types
		);

		foreach (array('row', 'column', 'item', 'default') as $type) {
			$options[$type] = array(
				'type' => 'multi',
				'inner-options' => $this->ext()->get_options($type),
			);
		}

		return $options;
	}

	/**
	 * Use theme in meta name
	 * so when the user will change the theme which has other options, there will be no notices/errors/conflicts
	 * @return string
	 */
	public static function get_meta_name() {
		try {
			return FW_Cache::get($cache_key = 'fw:ext:megamenu:items-options:meta-name');
		} catch (FW_Cache_Not_Found_Exception $e) {
			FW_Cache::set(
				$cache_key,
				/**
				 * Use basename() because it can be 'theme-name/theme-name-parent'
				 * then after theme update it becomes 'theme-name-parent'
				 */
				$meta_name = 'fw:ext:mm:io:' . basename(get_template())
			);

			return $meta_name;
		}
	}

	protected function get_values($item_id, array $extra_data = array())
	{
		return FW_WP_Meta::get( 'post', $item_id, self::get_meta_name(), array() );
	}

	protected function set_values($item_id, $values, array $extra_data = array())
	{
		return FW_WP_Meta::set( 'post', $item_id, self::get_meta_name(), $values );
	}

	protected function _init()
	{
		/**
		 * Get item option value from the database
		 *
		 * @param int $item
		 * @param string|null $option_id 'type/option_id' (accepts multikey). null - all options
		 * @param null|mixed $default_value If no option found in the database, this value will be returned
		 *
		 * @return mixed|null
		 */
		function fw_ext_mega_menu_get_db_item_option($item, $option_id = null, $default_value = null) {
			/*if ( ! $item ) {
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
			}*/

			return FW_Db_Options_Model::_get_instance('megamenu')->get(intval($item), $option_id, $default_value);
		}

		/**
		 * Set item option value in database
		 *
		 * @param int $item
		 * @param string|null $option_id 'type/option_id' (accepts multikey). null - all options
		 * @param $value
		 */
		function fw_ext_mega_menu_set_db_item_option( $item, $option_id = null, $value ) {
			return FW_Db_Options_Model::_get_instance('megamenu')->set(intval($item), $option_id, $value);
		}
	}
}
new FW_Db_Options_Model_MegaMenu();
