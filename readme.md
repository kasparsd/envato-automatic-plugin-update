# Automatic Updates for CodeCanyon WordPress Plugins

This is a library for WordPress plugin developers who sell their plugins on [CodeCanyon](http://codecanyon.net/?ref=Preseto) and would like to provide automatic plugin updates to their users.

## Requirements

User must install the [Envato WordPress Toolkit](https://github.com/envato/envato-wordpress-toolkit) plugin.

## Instructions

In your plugin, attach an action to `plugins_loaded`:

	add_action( 'plugins_loaded', 'my_envato_updates_init' );

	function my_envato_updates_init() {

		include plugin_dir_path( __FILE__ ) . 'lib/envato-plugin-update.php';

		PresetoPluginUpdateEnvato::instance()->add_item( array(
				'id' => NNNNNNN,
				'basename' => plugin_basename( __FILE__ )
			) );

	}

where the `id` is the product ID of your plugin. For example, the product ID of this plugin:
	
	http://codecanyon.net/item/storage-for-contact-form-7-/7806229 

is

	7806229

## Example

This library is used by the [Storage for Contact Form 7 plugin](http://codecanyon.net/item/storage-for-contact-form-7-/7806229?ref=Preseto).