<?php
/**
 * Adds a dashboard widget for flushing URLs from the CDN.
 */
class CDN_Integration_Dashboard_Widget {
    /**
     * Hooks in to WordPress actions
     */
    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_setup' ) );
        add_action( 'admin_print_scripts-index.php', array( $this, 'enqueue_dashboard_widget_script' ) );
        add_action( 'wp_ajax_cdn_integration_dashboard_flush', array( $this, 'ajax_callback' ) );
    }

    /**
     * Registers the CDN flush widget and re-prioritizes it so it displays in the second column of the dashboard
     */
    public function dashboard_widget_setup() {
        global $wp_meta_boxes;
        wp_add_dashboard_widget( 'cdn_integration_dashboard_flush', 'Purge URL(s) from CDN', array( $this, 'dashboard_widget' ) );

    	/*** Change the priority of the widget ***/
    	// Backup our widget
    	$the_widget = $wp_meta_boxes['dashboard']['normal']['core']['cdn_integration_dashboard_flush'];

    	// Unset the normal priority
    	unset( $wp_meta_boxes['dashboard']['normal']['core']['cdn_integration_dashboard_flush'] );

    	// Add the widget back on the side
    	$wp_meta_boxes['dashboard']['side']['core']['cdn_integration_dashboard_flush'] = $the_widget;
    }

    /**
     * The body of the dashboard widget
     * @return HTML
     */
    public function dashboard_widget() {
    	?>
    	<form>
    		<textarea name="urls-to-flush" id="urls-to-flush" class="widefat"></textarea>
       		<p>
                <input type="submit" value="Flush" class="button-primary">
    		    <img src="<?=get_admin_url();?>/images/wpspin_light.gif" class="waiting" style="display:none;">
    		</p>
    	</form>
    	<?php
    }

    /**
     * Enqueues the JavaScript required to make the dashboard widget work
     * @return [type] [description]
     */
    public function enqueue_dashboard_widget_script() {
    	wp_enqueue_script( 'cdn-integration-dashboard-widget', plugins_url( '/js/cdn-integration-dashboard-widget.js', __FILE__ ), array('jquery'), NULL, true );
    }

    /**
     * AJAX callback that calls the 'cdn_integration_flush_urls' action for the given list of URLs
     * @return [type] [description]
     */
    public function ajax_callback() {
        $urls = $_POST['urls'];
        $urls = explode( ',', $urls );
        do_action( 'cdn_integration_flush_urls', $urls );
        die();
    }
}

$cdn_integration_dashboard_widget = new CDN_Integration_Dashboard_Widget();
