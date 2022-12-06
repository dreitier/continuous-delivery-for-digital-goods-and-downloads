<?php

namespace Dreitier\WordPress\ContinuousDelivery\Ui;

use Dreitier\WordPress\ContinuousDelivery\PlugIn;use Dreitier\WordPress\ContinuousDelivery\Vendor\Carbon_Fields\Field\Text_Field;

class AdminPage
{
	private $options;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}

	public function add_plugin_page()
	{
		add_menu_page(
				'Continuous Delivery', // page_title
				'Continuous Delivery', // menu_title
				'manage_options', // capability
				'continuous_delivery', // menu_slug
				array($this, 'create_admin_page'), // function
				'dashicons-admin-generic', // icon_url
				100 // position
		);
	}

	public function create_admin_page()
	{
		?>

		<div class="wrap">
			<h2><?php echo PlugIn::NAME ?></h2>
			<h3>Integration</h3>
			<p>
			<ul>
				<li>Easy Digital
					Downloads: <?php echo \Dreitier\WordPress\ContinuousDelivery\Integration\EasyDigitalDownloads::hasRuntimeDependenciesAvailable() ? '&#9745;' : '&#9744;' ?></li>
				<li>Download
					Monitor: <?php echo \Dreitier\WordPress\ContinuousDelivery\Integration\DownloadMonitor::hasRuntimeDependenciesAvailable() ? '&#9745;' : '&#9744;' ?></li>
			</ul>
			</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				\settings_fields('continuous_delivery_option_group');
				\do_settings_sections('continuous_delivery-admin');
				\submit_button();
				?>
			</form>
		</div>
	<?php }

	public function page_init()
	{
		\register_setting(
				'continuous_delivery_option_group', // option_group
				'continuous_delivery', // option_name
				array($this, 'sanitize') // sanitize_callback
		);

		$defaultBucket = [
				'is_new' => true,
				'region' => 'eu-central-1',
				'bucket_name' => '',
				'access_key' => '',
				'secret_access_key' => ''
		];

		$this->options = \get_option('continuous_delivery');
		$useBuckets = array_merge(isset($this->options['buckets']) ? $this->options['buckets'] : [], [$defaultBucket]);

		for ($i = 0; $i < sizeof($useBuckets); $i++) {
			$bucket = $useBuckets[$i];

			\add_settings_section(
					'continuous_delivery_setting_section_' . $i, // id
					isset($bucket['is_new']) ? 'Add new bucket' : 'Bucket #' . ($i + 1), // title
					array($this, 'section_info'), // callback
					'continuous_delivery-admin' // page
			);

			\add_settings_field(
					'continuous_delivery_bucket_name_' . $i, // id
					'Bucket Name', // title
					function () use ($bucket, $i) {
						$this->bucket_name_callback($bucket, $i);
					},
					'continuous_delivery-admin', // page
					'continuous_delivery_setting_section_' . $i // section
			);

			\add_settings_field(
					'continuous_delivery_region_' . $i, // id
					'AWS Region', // title
					function () use ($bucket, $i) {
						$this->region_callback($bucket, $i);
					},
					'continuous_delivery-admin', // page
					'continuous_delivery_setting_section_' . $i // section
			);

			\add_settings_field(
					'continuous_delivery_access_key_' . $i, // id
					'Access Key', // title
					function () use ($bucket, $i) {
						$this->access_key_callback($bucket, $i);
					},
					'continuous_delivery-admin', // page
					'continuous_delivery_setting_section_' . $i // section
			);

			\add_settings_field(
					'continuous_delivery_secret_access_key_' . $i, // id
					'Secret Access Key', // title
					function () use ($bucket, $i) {
						$this->secret_access_key_callback($bucket, $i);
					},
					'continuous_delivery-admin', // page
					'continuous_delivery_setting_section_' . $i // section
			);
		}
	}

	private function bucket_configuration_value($idx, $key, $default = '')
	{
		if (!$this->options) {
			$this->options = \get_option('continuous_delivery');
		}

		if (is_array($this->options['buckets']) && isset($this->options['buckets'][$idx][$key])) {
			return $this->options['buckets'][$idx][$key];
		}

		return $default;
	}

	public function sanitize($input)
	{
		$sanitary_values = array();

		$sanitary_values['buckets'] = [];

		if (isset($input['buckets'])) {
			$bucket = $input['buckets'];

			foreach ($input['buckets'] as $idx => $bucket) {
				$storeBucket = [];
				$storeBucket['bucket_name'] = $bucket['bucket_name'];
				$storeBucket['region'] = $bucket['region'];
				$storeBucket['access_key'] = !empty($bucket['access_key']) ? $bucket['access_key'] : $this->bucket_configuration_value($idx, 'access_key');
				$storeBucket['secret_access_key'] = !empty($bucket['secret_access_key']) ? $bucket['secret_access_key'] : $this->bucket_configuration_value($idx, 'secret_access_key');

				if (!empty($storeBucket['bucket_name']) && !empty($storeBucket['region'])) {
					$sanitary_values['buckets'][] = $storeBucket;
				}
			}
		}

		return $sanitary_values;
	}

	public function section_info()
	{

	}

	public function bucket_name_callback($bucket, $idx)
	{
		printf(
				'<input class="regular-text" type="text" name="continuous_delivery[buckets][%d][bucket_name]" id="continuous_delivery_bucket_name_%d"  value="%s">',
				$idx, $idx, isset($bucket['bucket_name']) ? \esc_attr($bucket['bucket_name']) : ''
		);
	}

	public function region_callback($bucket, $idx)
	{
		printf(
				'<input class="regular-text" type="text" name="continuous_delivery[buckets][%d][region]" id="continuous_delivery_region_%d" value="%s">',
				$idx, $idx, isset($bucket['region']) ? \esc_attr($bucket['region']) : ''
		);
	}

	public function access_key_callback($bucket, $idx)
	{
		printf(
				'<input class="regular-text" type="text" name="continuous_delivery[buckets][%d][access_key]" id="continuous_delivery_access_key_%d" placeholder="%s">',
				$idx, $idx, 'Access key will not be shown'
		);
	}

	public function secret_access_key_callback($bucket, $idx)
	{
		printf(
				'<input class="regular-text" type="password" name="continuous_delivery[buckets][%d][secret_access_key]" id="continuous_delivery_secret_access_key_%d" placeholder="%s">',
				$idx, $idx, 'Secret access key will not be shown'
		);
	}
}