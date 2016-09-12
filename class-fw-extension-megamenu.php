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

		// Enqueue all the necessary files for Icon dialog
		$options = array(
			'icon' => apply_filters('fw:ext:megamenu:icon-option', array(
				'type' => 'icon',
				'label' => __('Select Icon', 'fw'),
			)),
		);
		fw()->backend->enqueue_options_static($options);

		wp_enqueue_media();
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
		wp_localize_script(
			"fw-ext-{$this->get_name()}-admin",
			'_fw_ext_mega_menu',
			array(
				'icon_option' => $options['icon']
			)
		);

		/**
		 * Enqueue assets for item options
		 */
		{
			global $nav_menu_selected_id;

			if ($items = wp_get_nav_menu_items($nav_menu_selected_id)) {
				fw()->backend->enqueue_options_static(array(
					'container' => array('type' => 'popup', 'options' => array('fake' => array('type' => 'text'))),
				));

				foreach ($items as $item) {
					if ($options = fw_ext_mega_menu_item_options($item)) {
						fw()->backend->enqueue_options_static($options);
					}
				}
			}
		}
	}

	/**
	 * @internal
	 */
	public function _admin_action_wp_update_nav_menu_item($menu_id, $menu_item_db_id, $args)
	{
		// Save hardcoded options
		{
			$meta = _fw_ext_mega_menu_admin_input_POST_values($menu_item_db_id);

			fw_ext_mega_menu_update_meta($menu_item_db_id, array(
				'enabled' => isset($meta['enabled']),
				'title-off' => isset($meta['title-off']),
				'new-row' => isset($meta['new-row']),
				'icon' => $meta['icon'],
			));
		}

		// Save custom options
		{
			$item = get_post($menu_item_db_id);
			$values = fw_get_options_values_from_input(
				fw_ext_mega_menu_item_options($item),
				FW_Request::POST('fw_ext_mega_menu/items/'. $item->ID)
			);

			fw_ext_mega_menu_set_db_item_option($item, null, $values);
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
}
