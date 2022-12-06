<?php
// required to use S3 client - AWS S3 has too much dependencies
define('AKEEBAENGINE', true);

$vendorDir = __DIR__ . '/vendor-repackaged';

if (!is_dir($vendorDir)) {
	die("Vendor directory has not been packaged, please download a proper NADI build or run composer install");
}

// find re-packaged dependencies
require_once $vendorDir . "/autoload.php";

// mapping of our namespaces.
// using composer's autoload.psr-4 feature is not possible as we would interfere with the dependencies
$mapNamespacesToSourceDirectories = [
	"Dreitier\\WordPress\\ContinuousDelivery\\" => __DIR__ . '/src/plug-in',
	"Dreitier\\" => __DIR__ . '/src/shared',
];

// register our own namespaces.
// @see https://stackoverflow.com/a/39774973/2545275
foreach ($mapNamespacesToSourceDirectories as $namespace => $sourceDirectory) {
	spl_autoload_register(function ($classname) use ($namespace, $sourceDirectory) {
		// Check if the namespace matches the class we are looking for
		if (preg_match("#^" . preg_quote($namespace) . "#", $classname)) {
			// Remove the namespace from the file path since it's psr4
			$classname = str_replace($namespace, "", $classname);
			$filename = preg_replace("#\\\\#", "/", $classname) . ".php";
			$fullpath = $sourceDirectory . "/$filename";

			if (file_exists($fullpath)) {
				include_once $fullpath;
			}
		}
	});
}