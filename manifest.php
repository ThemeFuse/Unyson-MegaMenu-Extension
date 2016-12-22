<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']        = __( 'Mega Menu', 'fw' );
$manifest['description'] = __( 'The Mega Menu extension adds a user-friendly drop down menu that will let you easily create highly customized menu configurations.', 'fw' );
$manifest['version'] = '1.1.3';
$manifest['display'] = true;
$manifest['github_repo'] = 'https://github.com/ThemeFuse/Unyson-MegaMenu-Extension';
$manifest['uri'] = 'http://manual.unyson.io/en/latest/extension/megamenu/index.html#content';
$manifest['author'] = 'ThemeFuse';
$manifest['author_uri'] = 'http://themefuse.com/';
$manifest['standalone'] = true;
$manifest['requirements'] = array(
	'framework' => array(
		'min_version' => '2.5.9', // class FW_Db_Options_Model
	),
);

$manifest['github_update'] = 'ThemeFuse/Unyson-MegaMenu-Extension';
