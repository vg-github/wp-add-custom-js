<?php
/*
Plugin Name: WP Add Custom JS
Plugin URI: https://github.com/vladimirghetau/
Description: Add custom JS to the whole website and to specific posts, pages and custom post types.
Version: 1.0.0
Author: Vladimir Ghetau
Author URI: https://www.gibdata.com/
Text Domain: wp-add-custom-js
Domain Path: /languages/
License: GPL2
*/

/*


Based on Daniele De Santis WP Add Custom CSS, by Vladimir Ghetau (vladimir.ghetau@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('ABSPATH')) die ('No direct access allowed');

if(!class_exists('Wpacc2'))
{
    class Wpacc2
    {
		private $options;
		
		public function __construct() {
      add_action('admin_menu', array($this, 'add_menu'));
    	add_action( 'admin_init', array( $this, 'init_settings' ) );
			add_action( 'add_meta_boxes', array($this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'single_save' ) );
			add_action('init', array($this, 'init'));
			add_filter('query_vars', array($this, 'add_wp_var'));
			add_action( 'wp_enqueue_scripts', array($this, 'add_custom_js'), 999 );
			add_action('wp_head', array($this, 'single_custom_js'));
		}			
		
		public function init() {
			load_plugin_textdomain( 'wp-add-custom-js', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
		
		public static function uninstall2() {
			self::delete_options();	
			self::delete_custom_meta();
    }
		
		public function add_meta_box( $post_type ) {
			$this->options = get_option( 'wpacc_settings2' );			
			$post_types = array('post', 'page');			
			if ( isset($this->options['selected_post_types']) ) {
				$post_types = array_merge( $post_types, $this->options['selected_post_types'] );
			}			
    	if ( in_array( $post_type, $post_types )) {
				add_meta_box('wp_add_custom_js', __( 'Custom JS', 'wp-add-custom-js' ), array( $this, 'render_meta_box_content' ), $post_type, 'advanced', 'high');
			}
		}
		
		public function single_save( $post_id ) {
			if ( ! isset( $_POST['wp_add_custom_js_box_nonce'] ) || ! wp_verify_nonce( $_POST['wp_add_custom_js_box_nonce'], 'single_add_custom_js_box' ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { 
				return;
			}
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) )
					return;		
			} else {	
				if ( ! current_user_can( 'edit_post', $post_id ) )
					return;
			}

			$single_custom_js = wp_kses( $_POST['single_custom_js'], array( '\'', '\"' ) );
			update_post_meta( $post_id, '_single_add_custom_js', $single_custom_js );
		}
		
		public function render_meta_box_content( $post ) {
			wp_nonce_field( 'single_add_custom_js_box', 'wp_add_custom_js_box_nonce' );
	  	$single_custom_js = get_post_meta( $post->ID, '_single_add_custom_js', true );
			echo '<p>'.  sprintf( __( 'Add custom JS rules for this %s', 'wp-add-custom-js' ), $post->post_type ). '</p> ';
			echo '<textarea id="single_custom_js" name="single_custom_js" style="width:100%; min-height:200px;">' . esc_attr( $single_custom_js ) . '</textarea>';
		}
		
		public function add_menu() {
			global $wpacc_settings_page2;
			$wpacc_settings_page2 = add_menu_page( __('Wordpress Add Custom JS', 'wp-add-custom-js'), __('Add Custom JS', 'wp-add-custom-js'), 'manage_options', 'wp-add-custom-js_settings', array($this, 'create_settings_page'), plugin_dir_url( __FILE__ ) . '/images/icon.png');
		}
		
		public function create_settings_page() {
			$this->options = get_option( 'wpacc_settings2' );
			?>
			<div class="wrap">
      	<h2><?php echo __('Wordpress Add Custom JS', 'wp-add-custom-js'); ?></h2>
        <form id="worpress_custom_js_form" method="post" action="options.php">
        <?php settings_fields( 'wpacc_group2' ); ?>
        <?php do_settings_sections( 'wp-add-custom-js_settings' ); ?>
				<?php submit_button( __('Save', 'wp-add-custom-js') ); ?>
				</form>
				<h3><?php echo __('Credits', 'wp-add-custom-js'); ?></h3>
				<ul>
					<li><?php echo __('"WP Add Custom JS" is a plugin by', 'wp-add-custom-js'); ?> <a href="https://github.com/vladimirghetau" target="_blank" title="Vladimir Ghertau">Vladimir Ghetau</a></li>
				</ul>
			</div>
      <?php
		}
		
		public function print_section_info() {
			echo __('Write here the JS scripts you want to apply to the whole website.', 'wp-add-custom-js');
    }
		
		public function main_js_input() {
    	$custom_rules = isset( $this->options['main_custom_js'] ) ? esc_attr( $this->options['main_custom_js'] ) : '';
			echo '<textarea name="wpacc_settings2[main_custom_js]" style="width:100%; min-height:300px;">' . $custom_rules . '</textarea>';
    }
		
		public function print_section_2_info() {
			echo __('Enable page specific JS for the post types below.', 'wp-add-custom-js');
    }
		
		public function post_types_checkboxes() {
			$available_post_types = get_post_types( array('public' => true, '_builtin' => false), 'objects' );
			foreach ( $available_post_types as $post_type ) {
				if ( isset( $this->options['selected_post_types'] ) ) {
					$checked = in_array( $post_type->name, $this->options['selected_post_types'] ) ? ' checked' : '';
				} else {
					$checked = '';
				}
				echo '<div style="margin-bottom:10px"><input type="checkbox" name="wpacc_settings2[selected_post_types][]" value="' . $post_type->name . '"' . $checked . '>' . $post_type->label . '</div>'; // output checkbox
			}
    }
		
		public function init_settings() {
			register_setting(
				'wpacc_group2',
				'wpacc_settings2'
			);	
			add_settings_section(
					'wpacc_main_js',
					__('Main JS', 'wp-add-custom-js'),
					array( $this, 'print_section_info' ),
					'wp-add-custom-js_settings'
			);
			add_settings_field(
					'main_custom_js',
					__('JS Code', 'wp-add-custom-js'),
					array( $this, 'main_js_input' ),
					'wp-add-custom-js_settings',
					'wpacc_main_js'          
			);
			add_settings_section(
					'wpacc_post_types2',
					__('Post types', 'wp-add-custom-js'),
					array( $this, 'print_section_2_info' ),
					'wp-add-custom-js_settings'
			);
			add_settings_field(
					'selected_post_types',
					__('Available post types', 'wp-add-custom-js'),
					array( $this, 'post_types_checkboxes' ),
					'wp-add-custom-js_settings',
					'wpacc_post_types2'          
			);
		}
		
		public function delete_options() {
			unregister_setting(
				'wpacc_group2',
				'wpacc_settings2'
			);
			delete_option('wpacc_settings2');	
		}
		
		public function delete_custom_meta() {
			delete_post_meta_by_key('_single_add_custom_js');			
		}
		
		public static function add_wp_var($public_query_vars) {
    	$public_query_vars[] = 'display_custom_js';
    	return $public_query_vars;
		}
		
		public static function display_custom_js(){
    	$display_js = get_query_var('display_custom_js');
    	if ($display_js == 'js'){
				include_once (plugin_dir_path( __FILE__ ) . '/js/custom-js.php');
      	exit;
    	}
		}
		
		public function add_custom_js() {
			$this->options = get_option( 'wpacc_settings2' );

			if ( isset($this->options['main_custom_js']) && $this->options['main_custom_js'] != '') {
				if ( function_exists('icl_object_id') ) {
					$js_base_url = site_url();
					if ( is_ssl() ) {
						$js_base_url = site_url('/', 'https');
					}
				} else {
					$js_base_url = get_bloginfo('url');
					if ( is_ssl() ) {
						$js_base_url = str_replace('http://', 'https://', $js_base_url);
					}
				}

				wp_enqueue_script( 
						'wp-add-custom-js', 
						$js_base_url . '?display_custom_js=js', 
						[], 
						false, 
						true 
					);	
			}
		}
		
		public function single_custom_js() {
			if ( is_single() || is_page() ) {
				global $post;				
				$enabled_post_types = array('post', 'page');				
				$this->options = get_option( 'wpacc_settings2' );
				if ( isset($this->options['selected_post_types']) ) {
					$enabled_post_types = array_merge( $enabled_post_types, $this->options['selected_post_types'] );
				}
				if ( ! in_array( $post->post_type, $enabled_post_types )) {
					return;
				}				
				$single_custom_js = get_post_meta( $post->ID, '_single_add_custom_js', true );
				if ( $single_custom_js !== '' ) {
					$single_custom_js = str_replace ( '&gt;' , '>' , $single_custom_js );

					$output = "<script type=\"text/javascript\">\n" . $single_custom_js . "\n</script>\n";
					echo $output;
				}
			}
		}
		
		
    }
}

if(class_exists('Wpacc2')) {
	add_action('template_redirect', array('Wpacc2', 'display_custom_js'));
	register_uninstall_hook(__FILE__, array('Wpacc2', 'uninstall2'));
	$wpacc2 = new Wpacc2();
}

if(isset($wpacc2)) {	
    function wpacc_settings_link2($links) {
        $settings_link = '<a href="admin.php?page=wp-add-custom-js_settings">' . __('Settings', 'wp-add-custom-js') . '</a>';
        array_unshift($links, $settings_link);
        return $links; 
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpacc_settings_link2');
}
?>