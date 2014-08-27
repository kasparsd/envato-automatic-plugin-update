<?php

// Make sure that all dependencies are met
if ( ! class_exists( 'PresetoPluginUpdateEnvato' ) ) :

class PresetoPluginUpdateEnvato {

	private static $instance;
	private $items = array(), 
		$protected_api, 
		$options;


	private function __construct() {

		if ( ! class_exists( 'Envato_Protected_API' ) )
			return;

		add_action( 'admin_init', array( $this, 'admin_init' ) );

	}


	static function instance() {

		if ( ! self::$instance )
			self::$instance = new self();

		return self::$instance;

	}


	public function add_item( $item ) {

		if ( is_array( $item ) && isset( $item['id'] ) && isset( $item['basename'] ) )
			$this->items[] = $item;

	}


	function admin_init() {

		if ( ! defined( 'EWPT_PLUGIN_SLUG' ) )
			return;

		// Make sure that any items have been registered
		if ( empty( $this->items ) )
			return;

		$this->options = wp_parse_args(
				get_option( EWPT_PLUGIN_SLUG ),
				array(
					'user_name' => null,
					'api_key' => null
				)
			);

		$this->protected_api = new Envato_Protected_API( 
				$this->options['user_name'], 
				$this->options['api_key'] 
			);

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_plugin_updates' ) );

		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );

	}


	function wp_list_plugins( $allow_cache = true, $timeout = 300 ) {

		return $this->protected_api->private_user_data( 
				'wp-list-plugins', 
				$this->options['user_name'], 
				'', 
				$allow_cache, 
				$timeout 
			);

	}


	function get_items_by( $key = 'id' ) {

		$assoc = array();

		foreach ( $this->items as $item )
			if ( isset( $item[ $key ] ) )
				$assoc[ $item[ $key ] ] = $item;

		return $assoc;

	}


	function check_plugin_updates( $plugins ) {

		if ( ! isset( $plugins->checked ) )
			return $plugins;

		$user_plugins = $this->wp_list_plugins();
		$items = $this->get_items_by();

		if ( empty( $user_plugins ) )
			return $plugins;

		foreach ( $user_plugins as $user_plugin ) {

			// Check if this item has been registered for automatic updates
			if ( ! isset( $items[ $user_plugin->item_id ] ) )
				continue;

			$item_basename = $items[ $user_plugin->item_id ]['basename'];

			// Check if user has purchased this item
			if ( array_key_exists( $user_plugin->item_id, $items ) && isset( $plugins->checked[ $item_basename ] ) ) {

				// Check if an update is available
				if ( version_compare( $user_plugin->version, $plugins->checked[ $item_basename ], '>' ) ) {

					// Get the update zip file
					$update_zip = $this->protected_api->wp_download( $user_plugin->item_id );

					if ( ! $update_zip || empty( $update_zip ) )
						continue;

					$plugins->response[ $item_basename ] = (object) array(
							'id' => 'envato-' . $user_plugin->item_id,
							'slug' => 'envato-' . $user_plugin->item_id,
							'plugin' => $item_basename,
							'new_version' => $user_plugin->version,
							'upgrade_notice' => null,
							'url' => null,
							'package' => $update_zip
						);
			
				}
			
			}
		
		}

		return $plugins;

	}


	function plugins_api( $res, $action, $args ) {

		$items = $this->get_items_by();

		if ( 'plugin_information' == $action && isset( $args->slug ) && stristr( $args->slug, 'envato-' ) ) {

			// Get item ID out of "envato-NNNN"
			$item_id = str_replace( 'envato-', '', $args->slug );

			foreach ( $items as $item ) {

				if ( $item['id'] == $item_id ) {

					$item_details = $this->protected_api->item_details( $item_id );

					if ( ! $item_details )
						return new WP_Error( 
								'plugins_api_envato_failed', 
								__( 'Failed to retreive plugin details from the Envato API.' ) 
							);

					return (object) array(
							'name' => $item_details->item,
							'sections' => array(
								'changelog' => sprintf(
									'<p>%s</p>',
									sprintf( 
										__( 'New version of <strong>%s</strong> is available.' ),
										esc_html( $item_details->item )
									)
								),
							),
							'version' => null,
							'author' => $item_details->user,
							'requires' => null,
							'tested' => null,
							'homepage' => $item_details->url,
							'downloaded' => $item_details->sales,
							'slug' => 'envato-' . $item_id,
							'last_updated' => $item_details->last_update
						);

				}
			}

		}

		return $res;

	}

}

endif; // class_exists

