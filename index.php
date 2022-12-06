<?php
/**
 * Plugin Name: Continuous Delivery for Digital Goods and Downloads
 * Plugin URI: https://dreitier.com
 * Description: Continuous Delivery for Downloads expands your download portal to a fully-fledged Continuous Delivery pipeline.
 * Version: REPLACE_VERSION_BY_CI
 * Author: dreitier GmbH
 * Author URI: https://dreitier.com
 * License: The MIT License (MIT)
 * License URI: https://opensource.org/licenses/MIT
 */

use Dreitier\WordPress\ContinuousDelivery\Ui\AdminPage;

// required to use S3 client - AWS S3 has too much dependencies
define('AKEEBAENGINE', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor-repackaged/autoload.php';

add_action("plugins_loaded", function () {
	(new \Dreitier\WordPress\ContinuousDelivery\PlugIn())->init();
});

add_action('after_setup_theme', function () {
	if (is_admin()) {
		$ui = new AdminPage();
	}
});