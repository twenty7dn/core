<?php
require ( plugin_dir_path( __FILE__ ) . 'updates/plugin-update-checker.php' );
use YahnisElsts\PluginUpdateChecker\v5\PucFactory; // Plugin update class
 
/**
 * Update plugin
 */
$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/davidmatthewcoleman/core/',
	realpath( __DIR__ . DIRECTORY_SEPARATOR . '../davidmc-core.php' ),
	'davidmc-core',
	1
);
$myUpdateChecker->setAuthentication( GITHUB_ACCESS_TOKEN );
$myUpdateChecker->setBranch('main');

$myUpdateChecker->addResultFilter( function( $info, $response = null ) {
    // Add icons for Dashboard > Updates screen.
    $info->icons = array(
        '1x' => plugin_dir_url( __FILE__ ) . '../assets/img/icon-128x128.png',
        '2x' => plugin_dir_url( __FILE__ ) . '../assets/img/icon-256x256.png',
    );

	// Add banners for Dashboard > Updates screen.
	$info->banners = array(
        'low' => plugin_dir_url( __FILE__ ) . '../assets/img/banner-772x250.png',
        'high' => plugin_dir_url( __FILE__ ) . '../assets/img/banner-1544x500.png',
    );

	// Add description for Dashboard > Updates screen.
	$info->sections = array(
		'description' => 'Adds core functionality to the Twenty7 Degrees North headless theme.'
	);

	return $info;
});