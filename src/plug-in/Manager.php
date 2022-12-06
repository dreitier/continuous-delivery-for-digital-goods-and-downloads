<?php

namespace Dreitier\WordPress\ContinuousDelivery;

use Dreitier\WordPress\ContinuousDelivery\Event\PublishRequest;

class Manager
{
	public function __construct()
	{
	}

	public function register()
	{
		add_filter('application_password_is_api_request', [$this, 'isApiRequest'], 10, 1);
		add_action('rest_api_init', [$this, 'registerRoutes']);
		add_filter(PlugIn::hook('authorize'), [$this, 'authorize_by_permission'], 10, 1);
		add_filter(PlugIn::hook('authorize'), [$this, 'authorize_by_role'], 11, 1);
		add_filter(PlugIn::hook('authorize'), [$this, 'authorize_by_username'], 11, 1);
	}

	public function isApiRequest($api_request)
	{
		if (empty($api_request)) {
			$r = strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false;

			return $r;
		}

		return $api_request;
	}

	public function registerRoutes()
	{
		register_rest_route(PlugIn::REST_API_NAMESPACE . '/v1', '/products/(?P<id>\d+)/release', array(
			'methods' => 'POST',
			'callback' => [$this, 'on_publish_request'],
			'permission_callback' => function () {
				return apply_filters(PlugIn::hook('authorize'), false);
			},
		));

		// update meta information of version release, e.g. when readme should be updated
		/*
        register_rest_route(PlugIn::REST_API_NAMESPACE . '/v1', '/products/(?P<id>\d+)/release/(?<version>(\w|\.|\_\-)+)/(?<key>(\w|\.|\_)+)', array(
            'methods' => 'POST',
            'callback' => [$this, 'on_update_release_property_request'],
            'permission_callback' => function () {
                return apply_filters(PlugIn::hook('authorize'), false);
            },
        ));
		*/
	}

	public function on_publish_request(\WP_REST_Request $request)
	{
		try {
			$createRelease = PublishRequest::fromRequest($request);

			$product = get_post($createRelease->product);

			$releaseCreated = apply_filters(PlugIn::hook('handle_create_release_request'), null, $product, $createRelease);

			if (!$releaseCreated) {
				return new \WP_Error('unknown_download_plugin', 'Can not release a version, no handler for type "' . $product->post_type . '" is registered', array('status' => 403));
			}
		} catch (ReleaseException $e) {
			return $e->toWpError();
		}

		$response = new \WP_REST_Response($releaseCreated);
		$response->set_status(200);

		return $response;
	}

	/**
	 * Grant access for users with 'manage_downloads' permission. That permission is coming from Download Monitor.
	 * @param bool $previousResult
	 * @return bool
	 */
	public function authorize_by_permission(bool $previousResult = false)
	{
		if ($previousResult) {
			return $previousResult;
		};

		return current_user_can('manage_downloads') || current_user_can('deploy');
	}

	/**
	 * Grant access for 'administrator' and 'deployer' role
	 * @param bool $previousResult
	 * @return bool
	 */
	public function authorize_by_role(bool $previousResult = false)
	{
		if ($previousResult) {
			return $previousResult;
		}

		$user = \wp_get_current_user();

		if ($user !== null) {
			$allowRoles = ['administrator', 'deployer'];
			$roles = (array)$user->roles;

			foreach ($roles as $role) {
				if (in_array($role, $allowRoles)) {
					return true;
				}
			}

		}

		return false;
	}

	/**
	 * Grant access for 'admin' and 'administrator' users
	 * @param bool $previousResult
	 * @return bool
	 */
	public function authorize_by_username(bool $previousResult = false)
	{
		if ($previousResult) {
			return $previousResult;
		}

		$user = \wp_get_current_user();

		if ($user !== null && in_array($user->user_login, ['admin', 'administrator', 'deployer'])) {
			return true;
		}

		return false;
	}
}