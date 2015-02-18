<?php if (!defined('FW')) die('Forbidden');


function fw_mega_menu_get_meta($post, $key, $default = null)
{
	return _fw_mega_menu_meta($post, $key, $default);
}
