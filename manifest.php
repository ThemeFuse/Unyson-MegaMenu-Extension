<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Mega Menu', 'fw' );
$manifest['description'] = __( 'The Mega Menu extension adds a user-friendly drop down menu that will let you easily create highly customized menu configurations.', 'fw' );
$manifest['version'] = '1.1.1';
$manifest['display'] = true;
$manifest['standalone'] = true;
$manifest['requirements'] = array(
	'framework' => array(
		'min_version' => '2.5.9', // class FW_Db_Options_Model
	),
);

$manifest['github_update'] = 'ThemeFuse/Unyson-MegaMenu-Extension';
