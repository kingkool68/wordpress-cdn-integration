<?php
class Separate_Admin_URL {

	public $original_site_url = '';
	public $site_path = '';
	public $unmapped_domain = '';
	public $unmapped_url = '';
	public $admin_domain = '';
	public $is_admin_ssl = false;
	public $public_domain = '';
	public $is_public_ssl = false;
	public $current_domain = '';
	public $is_wp_login_request = false;
	public $is_admin_ajax_request = false;
	public $is_async_upload_request = false;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10, 2 );
	}

	public function admin_menu() {
		add_submenu_page( 'options-general.php', 'CDN Integration', 'CDN Integration', 'manage_options', 'cdn-integration', array( $this, 'submenu_page' ) );
	}

	public function submenu_page() {
		$options = $this->get_options();
		if( isset( $_GET['update'] ) &&
			isset( $_POST['_wpnonce'] ) &&
			wp_verify_nonce( $_POST['_wpnonce'], 'cdn-integration-settings' )
		) {
			$options['public-domain'] = $this->validate_domain( $_POST['public-domain'] );
			$options['public-protocol'] = $this->validate_protocol( $_POST['public-protocol'] );
			$options['admin-domain'] = $this->validate_domain( $_POST['admin-domain'] );
			$options['admin-protocol'] = $this->validate_protocol( $_POST['admin-protocol'] );
			$options['cdn-provider'] = $this->validate_cdn_provider( $_POST['cdn-provider'] );
			$options['keycdn-api-secret'] = sanitize_text_field( $_POST['keycdn-api-secret'] );
			$options['keycdn-zone-id'] = sanitize_text_field( $_POST['keycdn-zone-id'] );
			$options['keycdn-zone-url'] = sanitize_text_field( $_POST['keycdn-zone-url'] );

			update_option( 'cdn-integration-settings', $options );
		}
	?>
	<div class="wrap">
		<h2>CDN Integration Settings</h2>

		<form method="post" action="?page=cdn-integration&update=true">
			<?php settings_fields( 'cdn-integration' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="cdn-integration-public-domain">Public Domain</label></th>
					<td>
						<select name="public-protocol">
							<option value="http" <?php selected( $options['public-protocol'], 'http' ); ?>>http</option>
							<option value="https" <?php selected( $options['public-protocol'], 'https' ); ?>>https</option>
						</select>
						<input type="text" name="public-domain" id="cdn-integration-public-domain" value="<?php echo esc_attr( $options['public-domain'] ); ?>" />
						<p class="description">The domain for the public facing side of the website that will be run through a CDN.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cdn-integration-admin-domain">Admin Domain</label></th>
					<td>
						<select name="admin-protocol">
							<option value="http" <?php selected( $options['admin-protocol'], 'http' ); ?>>http</option>
							<option value="https" <?php selected( $options['admin-protocol'], 'https' ); ?>>https</option>
						</select>
						<input type="text" name="admin-domain" id="cdn-integration-admin-domain" value="<?php echo esc_attr( $options['admin-domain'] ); ?>" />
						<p class="description">The domain for the admin side of the website.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="cdn-integration-cdn-provider">CDN Provider</label></th>
					<td>
						<select name="cdn-provider" id="cdn-integration-cdn-provider">
							<option value="-1">None</option>
							<option value="keycdn" <?php selected( $options['cdn-provider'], 'keycdn' ); ?>>KeyCDN</option>
						</select>
					</td>
				</tr>
				<?php
					$show_keycdn_settings = 'style="display:none;"';
					if( $options['cdn-provider'] == 'keycdn' ) {
						$show_keycdn_settings = '';
					}
				?>
				<tr class="keycdn-settings" valign="top" <?php echo $show_keycdn_settings; ?>>
					<th scope="row"><label for="keycdn-api-secret">KeyCDN API Secret</label></th>
					<td>
						<input type="text" name="keycdn-api-secret" id="keycdn-api-secret" value="<?php echo esc_attr( $options['keycdn-api-secret'] ); ?>" size="36" />
						<p class="description">32 character API secret from your <a href="https://app.keycdn.com/users/authSettings" target="_blank">Authentication Settings</a>.</p>
					</td>
				</tr>
				<tr class="keycdn-settings" valign="top" <?php echo $show_keycdn_settings; ?>>
					<th scope="row"><label for="keycdn-zone-id">KeyCDN Zone ID</label></th>
					<td>
						<input type="text" name="keycdn-zone-id" id="keycdn-zone-id" value="<?php echo esc_attr( $options['keycdn-zone-id'] ); ?>" size="5">
						<p class="description">The zone id for the pull zone to be flushed. This can be found among <a href="https://app.keycdn.com/zones/index" target="_blank">Zone Settings</a>.</p>

					</td>
				</tr>
				<tr class="keycdn-settings" valign="top" <?php echo $show_keycdn_settings; ?>>
					<th scope="row"><label for="keycdn-zone-url">KeyCDN Zone URL</label></th>
					<td>
						<input type="text" name="keycdn-zone-url" id="keycdn-zone-url" value="<?php echo esc_attr( $options['keycdn-zone-url'] ); ?>" size="36">
						<p class="description">The zone URL (xxxxxxx-xxxx.kxcdn.com). This can be found among <a href="https://app.keycdn.com/zones/index" target="_blank">Zone Settings</a>.</p>

					</td>
				</tr>
			</table>

			<?php wp_nonce_field( 'cdn-integration-settings' ); ?>

			<p class="submit">
				<input type="submit" class="button-primary" value="Save Options" />
			</p>

		</form>
	</div>
<?php
	}

	public function validate_protocol( $protocol ) {
		$protocol = strtolower( $protocol );
		if( in_array( $protocol, array( 'http', 'https' ) ) ) {
			return $protocol;
		}
		return 'http';
	}

	public function validate_domain( $url ) {
		return sanitize_text_field( $url );
	}

	public function validate_cdn_provider( $provider ) {
		$provider = strtolower( $provider );
		if( in_array( $provider, array( 'keycdn' ) ) ) {
			return $provider;
		}

		return '';
	}

	public function get_options() {
		$data = get_option( 'cdn-integration-settings' );
		if( !$data || !is_array( $data ) ) {
			$data = array();
		}
		$whitelist = array( 'public-domain', 'public-protocol', 'admin-domain', 'admin-protocol', 'cdn-provider', 'keycdn-api-secret', 'keycdn-zone-id', 'keycdn-zone-url', );
		foreach( $whitelist as $item ) {
			if( !isset( $data[ $item ] ) ) {
				$data[ $item ] = '';
			}
		}

		return $data;
	}

	public function plugins_loaded() {
		$url = get_site_url();
		$url = str_replace( 'https://', '', $url );
		$url = str_replace( 'http://', '', $url );
		$this->original_site_url = $url;

		$options = $this->get_options();
		$this->public_domain = $options['public-domain'];
		$this->admin_domain = $options['admin-domain'];
		if( !$this->public_domain || !$this->admin_domain ) {
			return;
		}

		if( $options['public-protocol'] == 'https' ) {
			$this->is_public_ssl = true;
		}
		if( $options['admin-protocol'] == 'https' || FORCE_SSL_ADMIN ) {
			$this->is_admin_ssl = true;
		}
		$this->current_domain = $_SERVER['HTTP_HOST'];
		if( is_multisite() ) {
			$site_details = get_blog_details();
			$this->unmapped_domain = $site_details->domain;
			$this->site_path = untrailingslashit( $site_details->path );
			$this->unmapped_url = $site_details->domain . untrailingslashit( $site_details->path );
		} else {
			$this->unmapped_domain == 'false';
			$this->unmapped_url = $this->original_site_url;
		}

		if( strstr( $_SERVER['REQUEST_URI'], 'wp-login.php' ) ) {
			$this->is_wp_login_request = true;
		}

		if( strstr( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) ) {
			$this->is_admin_ajax_request = true;
		}
		if( strstr( $_SERVER['REQUEST_URI'], '/wp-admin/async-upload.php' ) ) {
			$this->is_async_upload_request = true;
		}

		if( $this->current_domain == $this->unmapped_domain && !$this->is_admin_ajax_request && !$this->is_async_upload_request ) {
			$domain = 'http://' . $this->public_domain;
			if( $this->is_public_ssl ) {
				$domain = set_url_scheme( $domain, 'https' );
			}
			if( is_admin() ) {
				$domain = 'http://' . $this->admin_domain;
				if( $this->is_admin_ssl ) {
					$domain = set_url_scheme( $domain, 'https' );
				}
			}

			$redirect_url = $domain . $this->get_request_uri();
			wp_redirect( $redirect_url, 301 );
			die();
		}

		if( $this->current_domain == $this->admin_domain && !$this->is_admin_ajax_request && !$this->is_async_upload_request && $this->site_path && strstr( $_SERVER['REQUEST_URI'], $this->site_path ) ) {
			$domain = 'http://' . $this->admin_domain;
			if( $this->is_admin_ssl ) {
				$domain = set_url_scheme( $domain, 'https' );
			}

			$redirect_url = $domain . $this->get_request_uri();
			wp_redirect( $redirect_url, 301 );
			die();
		}

		if( $this->current_domain == $this->admin_domain && !is_user_logged_in() && !$this->is_wp_login_request ) {
			$domain = 'http://' . $this->public_domain;
			if( $this->is_public_ssl ) {
				$domain = set_url_scheme( $domain, 'https' );
			}

			$redirect_url = $domain . $this->get_request_uri();
			wp_redirect( $redirect_url, 301 );
			die();
		}

		if( $this->current_domain == $this->public_domain && $this->is_wp_login_request ) {
			$domain = 'http://' . $this->admin_domain;
			if( $this->is_admin_ssl ) {
				$domain = set_url_scheme( $domain, 'https' );
			}
			$redirect_url = $domain . '/wp-login.php';
			wp_redirect( $redirect_url, 301 );
			die();
		}

		add_filter( 'site_url', array( $this, 'site_url_rewrite' ), 10, 1 );
		add_filter( 'home_url', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'stylesheet_uri', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'stylesheet_directory_uri', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'template_directory_uri', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'plugins_url', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'theme_root_uri', array( $this, 'public_url_rewrite' ), 10, 1 );
		add_filter( 'wp_get_attachment_url', array( $this, 'public_url_rewrite' ), 10, 1 );

		add_filter( 'upload_dir', array( $this, 'upload_dir' ), 10, 1 );

		add_filter( 'admin_url', array( $this, 'admin_url_rewrite' ), 10, 1 );
		add_filter( 'login_url', array( $this, 'admin_url_rewrite' ), 10, 1 );
		add_filter( 'logout_url', array( $this, 'admin_url_rewrite' ), 10, 1 );
		add_filter( 'login_redirect', array( $this, 'admin_url_rewrite' ), 100, 1 );
		add_filter( 'preview_post_link', array( $this, 'admin_url_rewrite' ), 10, 1 );
		add_action( 'wp_login', array( $this, 'wp_login' ), 10, 2 );
	}

	public function site_url_rewrite( $url ) {
		if( in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {
			return $this->switch_public_to_admin( $url );
		}

		return $this->switch_admin_to_public( $url );
	}

	public function public_url_rewrite( $url ) {
		return $this->switch_admin_to_public( $url );
	}

	public function admin_url_rewrite( $url ) {
		return $this->switch_public_to_admin( $url );
	}

	public function upload_dir( $data ) {
		$data['url'] = $this->switch_admin_to_public( $data['url'] );
		$data['baseurl'] = $this->switch_admin_to_public( $data['baseurl'] );

		return $data;
	}

	public function wp_login() {
		if( $this->current_domain == $this->public_domain ) {
			$domain = 'http://' . $this->admin_domain;
			if( $this->is_admin_ssl ) {
				$domain = set_url_scheme( $domain, 'https' );
			}

			$redirect_url = $domain . $this->get_request_uri();
			wp_redirect( $redirect_url, 301 );
			die();
		}
	}

	public function get_request_uri( $request_uri = false ) {
		if( !$request_uri ) {
			$request_uri = $_SERVER['REQUEST_URI'];
		}

		if( $this->site_path ) {
			$request_uri = str_replace( $this->site_path, '', $request_uri );
		}

		return $request_uri;
	}

	public function switch_admin_to_public( $url ) {
		$url = str_replace( '://' . $this->current_domain, '://' . $this->public_domain, $url );
		$url = str_replace( '://' . $this->unmapped_url, '://' . $this->public_domain, $url );
		$url = str_replace( '://' . $this->admin_domain, '://' . $this->public_domain, $url );
		$url = str_replace( $this->public_domain . $this->site_path, $this->public_domain, $url );
		if( $this->is_public_ssl ) {
			set_url_scheme( $url, 'https' );
		}

		return $url;
	}

	public function switch_public_to_admin( $url ) {
		$url = str_replace( '://' . $this->current_domain, '://' . $this->admin_domain, $url );
		$url = str_replace( '://' . $this->unmapped_url, '://' . $this->admin_domain, $url );
		$url = str_replace( '://' . $this->public_domain, '://' . $this->admin_domain, $url );
		$url = str_replace( $this->admin_domain . $this->site_path, $this->admin_domain, $url );
		if( $this->is_admin_ssl ) {
			set_url_scheme( $url, 'https' );
		}

		return $url;
	}
}
global $separate_admin_url;
$separate_admin_url = new Separate_Admin_URL();

/* Helpers */
function get_cdn_integration_options() {
	global $separate_admin_url;
	return $separate_admin_url->get_options();
}
