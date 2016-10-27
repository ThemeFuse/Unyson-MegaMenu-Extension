<?php if (!defined('FW')) die('Forbidden');

class FW_Extension_Megamenu extends FW_Extension
{
	public function render_str($rel, $param = array())
	{
		return $this->render_view($rel, $param);
	}

	public function render($rel, $param = array())
	{
		$this->render_view($rel, $param, false);
	}

	/**
	 * Check if menu icon is enabled (checked in Screen Options on admin Menus page)
	 * @return bool
	 */
	public function show_icon()
	{
		return !in_array('icon', (array) get_user_option('manage' . 'nav-menus' . 'columnshidden'));
	}

	/**
	 * @internal
	 */
	public function _init() {
		add_action('wp_update_nav_menu_item', array($this, '_admin_action_wp_update_nav_menu_item'), 10, 3);
		add_action('admin_enqueue_scripts', array($this, '_admin_action_admin_enqueue_scripts'));
		add_action('wp_ajax_fw_ext_megamenu_item_values', array($this, '_action_ajax_item_values'));

		add_filter('wp_edit_nav_menu_walker', array($this, '_admin_filter_wp_edit_nav_menu_walker'));
		add_filter('manage_nav-menus_columns', array($this, '_admin_filter_manage_nav_menus_columns'), 20);
	}

	/**
	 * @internal
	 */
	public function _admin_action_admin_enqueue_scripts($hook)
	{
		if ($hook != 'nav-menus.php') {
			return;
		}

		wp_enqueue_media(); // required for modal

		wp_enqueue_style(
			"fw-ext-{$this->get_name()}-admin",
			$this->get_uri('/static/css/admin.css'),
			array(),
			$this->manifest->get_version()
		);
		wp_enqueue_script(
			"fw-ext-{$this->get_name()}-admin",
			$this->get_uri('/static/js/admin.js'),
			array('fw'),
			$this->manifest->get_version()
		);

		{
			$items_options = $items_options_modal_sizes = array();

			foreach (array('row', 'column', 'item', 'default') as $type) {
				$items_options[$type] = $this->get_options($type);
				$items_options_modal_sizes[$type] = $this->get_config('item-options:popup-size:'. $type);

				// Enqueue assets for item options
				fw()->backend->enqueue_options_static($items_options[$type]);
			}
		}

		$icon_option = apply_filters('fw:ext:megamenu:icon-option', array(
			'type' => 'icon',
			'label' => __('Select Icon', 'fw'),
		));
		fw()->backend->option_type($icon_option['type'])->enqueue_static();

		wp_localize_script(
			"fw-ext-{$this->get_name()}-admin",
			'_fw_ext_mega_menu',
			array(
				'l10n' => array(
					'item_options_btn' => apply_filters('fw:ext:megamenu:label:item-options-btn', __('Settings', 'fw')),
				),
				'icon_option' => $icon_option,
				'options' => $items_options,
				'item_options_modal_sizes' => $items_options_modal_sizes,
			)
		);
	}

	/**
	 * @internal
	 */
	public function _admin_action_wp_update_nav_menu_item($menu_id, $menu_item_db_id, $args)
	{
		if (!isset($_POST['menu']) || !isset($_POST['action'])) {
			return; // this is not a form submit
		}

		// Save hardcoded options
		{
			$meta = _fw_ext_mega_menu_admin_input_POST_values($menu_item_db_id);
			$meta = array(
				'enabled' => (
					isset($meta['enabled'])
					&&
					// only first level items can have "Enable as MegaMenu" checkbox
					!intval(get_post_meta( $menu_item_db_id, '_menu_item_menu_item_parent', true ))
				),
				'title-off' => isset($meta['title-off']),
				'new-row' => isset($meta['new-row']),
				'icon' => isset($meta['icon']) ? (string)$meta['icon'] : '',
			);

			fw_ext_mega_menu_update_meta($menu_item_db_id, $meta);
		}

		// Save item custom options
		if (
			$this->get_options('row') ||
			$this->get_options('column') ||
			$this->get_options('item') ||
			$this->get_options('default')
		) {
			$item_values = fw_ext_mega_menu_get_db_item_option($menu_item_db_id);

			if (isset($_POST['fw-megamenu-items-values'])) {
				// cache to prevent json_decode() on every item save
				try {
					$decoded_values = FW_Cache::get($cache_key = 'fw:ext:megamenu:POST-items-values-decoded');
				} catch (FW_Cache_Not_Found_Exception $e) {
					FW_Cache::set(
						$cache_key,
						$decoded_values = json_decode(FW_Request::POST('fw-megamenu-items-values'), true)
					);
				}

				if (isset($decoded_values[$menu_item_db_id])) {
					$item_values = array_merge(
						$item_values,
						$decoded_values[$menu_item_db_id]
					);
				}
			}

			if ($level = fw_ext_mega_menu_is_mm_item($menu_item_db_id)) {
				if ($level === 1) {
					$item_values['type'] = 'row';
				} elseif ($level === 2) {
					$item_values['type'] = 'column';
				} else {
					$item_values['type'] = 'item';
				}
			} else {
				$item_values['type'] = 'default';
			}

			if (
				empty($decoded_values[$menu_item_db_id])
				&&
				!FW_WP_Meta::get( 'post', $menu_item_db_id, FW_Db_Options_Model_MegaMenu::get_meta_name(), false)
			) {
				// Don't create an useless meta for all menu items if they were never saved
			} else {
				fw_ext_mega_menu_set_db_item_option($menu_item_db_id, null, $item_values);
			}
		} elseif (FW_WP_Meta::get( 'post', $menu_item_db_id, FW_Db_Options_Model_MegaMenu::get_meta_name(), false)) {
			// Orphan meta that needs to be deleted because there are no item options
			delete_post_meta($menu_item_db_id, FW_Db_Options_Model_MegaMenu::get_meta_name());
		}
	}

	/**
	 * @internal
	 */
	public function _admin_filter_wp_edit_nav_menu_walker()
	{
		return 'FW_Ext_Mega_Menu_Admin_Walker';
	}

	/**
	 * @internal
	 */
	public function _admin_filter_manage_nav_menus_columns($columns)
	{
		$columns['icon'] = __('Icon', 'fw');

		return $columns;
	}

	/**
	 * {@inheritdoc}
	 */
	public function _get_link()
	{
		return self_admin_url('nav-menus.php');
	}

	public function _action_ajax_item_values() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error();
		}

		wp_send_json_success(array(
			'values' => fw_ext_mega_menu_get_db_item_option(intval($_POST['id']))
		));
	}
}
