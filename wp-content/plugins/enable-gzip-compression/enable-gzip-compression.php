<?php
/*
Plugin Name: Enable Gzip Compression
Description: Offers you the power to enable and disable Gzip compression on your WordPress site.
Version: 1.0.1
Author: Moki-Moki Ios
Author URI: http://mokimoki.net/
License: GPL3
*/

/*
Copyright (C) 2017 Moki-Moki Ios http://mokimoki.net/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Enable Gzip Compression
 *
 * @version 1.0.2
 */

if (!defined('ABSPATH')) return;

require_once(__DIR__.'/compression-module.php');

add_action('init', array(EnableGzipCompression::get_instance(), 'initialize'));
add_action('admin_notices', array(EnableGzipCompression::get_instance(), 'plugin_activation_notice'));
register_activation_hook(__FILE__, array(EnableGzipCompression::get_instance(), 'setup_plugin_on_activation')); 

/**
 * Main class of the plugin.
 */
class EnableGzipCompression {
	
	const PLUGIN_NAME = "Enable Gzip Compression";
	const ADMIN_SETTINGS_URL = 'options-general.php?page=gzip-compression';
	const VERSION = '1.0.2';
	const OPTION_ON = 'on';
	const OPTION_OFF = 'off';
	const STATUS_OK = 'ok';
	const STATUS_ERROR = 'error';
	
	private static $instance;
	private static $compression;
	
	private function __construct() {}
		
	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
			self::$compression = new EnableGzipCompressionModule();
		}
		return self::$instance;
	}
	
	public function initialize() {
		add_action('admin_menu', array($this, 'create_options_menu'));
		add_action('wp_ajax_gzip_toggle', array(self::$compression, 'toggle_gzip_compression'));
	}
	
	public function create_options_menu() {
		add_submenu_page(
			'options-general.php',
			'Gzip Compression',
			'Gzip Compression',
			'manage_options',
			'gzip-compression',
			array($this, 'print_settings_page')
		);
	}
	
	public function setup_plugin_on_activation() {		
		set_transient('gzip_activation_notice', TRUE, 5);
		add_action('admin_notices', array($this, 'plugin_activation_notice'));
	}
	
	public function plugin_activation_notice() {
		if (get_transient('gzip_activation_notice')) {
			$settings_url = $settings_url = get_admin_url() . EnableGzipCompression::ADMIN_SETTINGS_URL;
			echo '<div class="notice updated"><p><strong>'.sprintf(__('Gzip Compression plugin is activated. Please set up compression at <a href="%s">settings page</a>.'), $settings_url).'</strong></p></div>';	
		}		
	}
	
	public function get_recommendation() {
		return 'Consider getting the premium version of this plugin. <a href="https://mokimoki.net/speed-up-wordpress/">Speed Up WordPress</a> takes care of configuration to gain shorter download times.';		
	}	
	
	public function print_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}		
		?>
		
		<h1><?php _e('Enable Gzip Compression'); ?></h1>
							
		<div class="gzip-settings">
		
			<?php $this->print_notifications(); ?>	
			
			<h2><span style="color: blue" class="dashicons dashicons-admin-users"></span> <?php _e('Actions'); ?></h2>		
			
			<form style="display: inline; margin-left: 1em" action="admin-ajax.php" method="post">
				<input type="hidden" name="create-sitemap" value="yes"/>
				<input type="hidden" name="action" value="gzip_toggle"/>
				<?php
					$compression_enabled = get_option('gzip_compression') !== self::OPTION_ON;
					
					if ($compression_enabled) : 
				?>				
				<input type="submit" name="submit" value="<?php _e('Enable Gzip compression'); ?>"/>
				<?php else : ?>
				<input type="submit" name="submit" value="<?php _e('Disable Gzip compression'); ?>"/>
				<?php endif; ?>
			</form>	
			
			<?php
				delete_option('pm_wp_seo_gzip_test_result');				
			?>
			
			<h2><span style="color: green" class="dashicons dashicons-image-filter"></span> <?php _e('Notice'); ?></h2>		
			
			<div class="updated notice is-dismissible"><p><?php echo $this->get_recommendation(); ?></p></div>
					
		</div>
		<?php
			delete_option('gzip_htaccess_save');
			delete_option('gzip_test_result');				
		?>
		<?php
	}
	
	private function print_notifications() {

		if (get_option('gzip_htaccess_save', FALSE) === self::STATUS_ERROR) : ?>
		<div class="notice error">
			<p><strong><?php _e('Enabling Gzip compression failed. Could not not update .htaccess file. Please check that the file is writable.'); ?></strong></p>
		</div>
		<?php endif; ?>
		
		<?php if (get_option('gzip_test_result', FALSE) === self::STATUS_ERROR) : ?>			
			<div class="notice error">
				<p><strong><?php _e('Gzip compression seems not to be working. Perhaps mod_deflate module is not active.'); ?></strong></p>
			</div>			
		<?php endif; ?>
		
		<?php 
			$htaccess_saved = get_option('gzip_htaccess_save', FALSE) === self::STATUS_OK;
			$gzip_working = get_option('gzip_test_result', FALSE) === self::STATUS_OK;
			if ($htaccess_saved && $gzip_working) : ?>
			<div class="notice updated">
				<?php if (get_option('gzip_compression') === self::OPTION_ON) : ?>
				<p><strong><?php _e('Gzip compression is now enabled and working.'); ?></strong></p>
				<?php else : ?>
				<p><strong><?php _e('Gzip compression is now disabled.'); ?></strong></p>
				<?php endif; ?>
			</div>
		<?php endif;		
	}
}
