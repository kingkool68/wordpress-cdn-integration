<?php
class CDN_Integration {
	public $plugin_dir_path = '';

	public function __construct() {
		$this->plugin_dir_path = plugin_dir_path( __FILE__ );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10, 2 );

		add_action( 'wp_async_wp_delete_file', array( $this, 'wp_delete_file' ), 20, 1 ); // $orig_path
		add_action( 'wp_async_post_updated', array( $this, 'post_updated' ), 20, 3 );
		add_action( 'wp_async_transition_comment_status', array( $this, 'transition_comment_status' ), 20, 3 ); // $new_status, $old_status, $comment
		add_action( 'wp_async_edit_attachment', array( $this, 'update_media' ), 20, 3 );
		add_action( 'wp_async_edit_terms', array( $this, 'edit_terms' ), 20, 2 );
		add_action( 'wp_async_pre_delete_term', array( $this, 'pre_delete_term' ), 20, 2 );
		add_action( 'delete_post', array( $this, 'update_post' ), 20, 2 ); // Can't do this asyncronsly because the post is already deleted before the async call is fired.
		add_action( 'delete_user', array( $this, 'delete_user' ), 20, 2 );

		// Author permalink when a user is deleted? --> https://codex.wordpress.org/Plugin_API/Action_Reference/delete_user
		// RSS? Homepage?
		// Widgets? --> https://wordpress.org/support/topic/detect-delete-and-change-order-of-widgets
		// General settings? Flush entire cache.
	}

	public function load_async_library() {
		if( !is_admin() ) {
			return;
		}
		// See https://github.com/techcrunch/wp-async-task
		include $this->plugin_dir_path . 'lib/wp-async-task/wp-async-task.php';
		include $this->plugin_dir_path . 'async-hooks/class-post-updated.php';
		include $this->plugin_dir_path . 'async-hooks/class-wp-delete-file.php';
		include $this->plugin_dir_path . 'async-hooks/class-edit-attachment.php';
		include $this->plugin_dir_path . 'async-hooks/class-transition-comment-status.php';
		include $this->plugin_dir_path . 'async-hooks/class-edit-terms.php';
		include $this->plugin_dir_path . 'async-hooks/class-pre-delete-term.php';
	}

	public function plugins_loaded() {
		$this->load_async_library();

		$options = get_cdn_integration_options();
		$cdn_provider = $options['cdn-provider'];
		if( $cdn_provider && file_exists( $this->plugin_dir_path . 'class-' . $cdn_provider . '.php' ) ) {
			include $this->plugin_dir_path. 'class-' . $cdn_provider . '.php';
		}
	}

	public function flush_urls( $urls = '' ) {
		if( !$urls ) {
			return;
		}

		if( is_string( $urls ) ) {
			$urls = array( $urls );
		}

		foreach( $urls as $i => $url ) {
			if( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
				unset( $urls[ $i ] );
			}
		}

		do_action( 'cdn_integration_flush_urls', $urls );
	}

	public function post_updated( $post_id ) {
		// error_log( '--- Post Updated ---' );
		if( $post_id = intval( $post_id ) ) {
			$this->update_post( $post_id );
		}
	}
    public function update_post( $post_id = 0 ) {
        $post_id = intval( $post_id );
        error_log( '--- Update Post ---' );
        error_log( 'post_id: ' . $post_id );
        if( !$post_id ) {
            return;
        }

        // Don't need to flush post revisions...
    	if( wp_is_post_revision( $post_id ) ) {
    		return;
    	}

        $post = get_post( $post_id );

    	//Rejiggering the $post object if the post is a draft so we can get a real permalink to flush via http://wordpress.stackexchange.com/a/42988/2744
    	//BAD: http://example.com/?post_type=foo&p=123
    	//GOOD: http://example.com/foo/2015/12/30/a-draft-post/
    	if( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', ) ) ) {
    		$post->post_status = 'published';
    		$post->post_name = sanitize_title( $post->post_name ? $post->post_name : $post->post_title, $post->ID );
    	}

    	//Get the primary domain and main RSS feed.
        // $urls = array( trailingslashit( 'http://' . ecf_get_primary_domain() ), get_bloginfo('rss2_url') );
        $urls = array();
        $urls[] = get_permalink( $post );
        error_log( 'Permalink: ' . $urls[0] );

    	$taxonomies = get_taxonomies( array( 'public' => true ) );
    	$terms = wp_get_object_terms( $post_id, $taxonomies );
    	foreach( $terms as $term ) {
    		$urls[] = get_term_link( $term );
            error_log( 'Term link: ' . get_term_link( $term ) );
    	}
    	$this->flush_urls( $urls );
    }

	public function update_media( $post_id = 0 ) {
		error_log( '--- Update Media ---' );
		error_log( 'post_id: ' . $post_id );

		if( $post_id = intval( $post_id ) ) {
            $this->update_post( $post_id );
        }
	}

	public function update_file( $orig_path ) {
		error_log( '--- Update File ---' );
		error_log( 'orig_path: ' . $orig_path );
		$uploads = wp_upload_dir();
		$path = preg_replace('/' . preg_quote($uploads['basedir'], '/') . '/i', '', $orig_path);

		// Transform it from an absolute path to a relative path.
		$urls = array( $uploads['baseurl'] . $path );
		error_log( $uploads['baseurl'] . $path );
		// ecf_flush( $urls );
	}

	public function wp_delete_file( $orig_path = '' ) {
		error_log( '--- WP Delete File ---' );
		$uploads = wp_upload_dir();
		$path = preg_replace('/' . preg_quote($uploads['basedir'], '/') . '/i', '', $orig_path);

		// Transform it from an absolute path to a relative path.
		$url = $uploads['baseurl'] . $path;

		if( $url ) {
			$this->flush_urls( $url );
		}
	}

	public function transition_comment_status( $post_id = 0 ) {
		error_log( '--- Transition Comment Status ---' );
		error_log( 'post_id: ' . $post_id );

		if( $post_id = intval( $post_id ) ) {
            $this->update_post( $post_id );
        }
	}

	public function delete_user( $user_id = 0 ) {
		if( !$user_id ) {
			return;
		}
		error_log( '--- Deleted User ---' );
		$url = get_author_posts_url( $user_id );
		error_log( 'Author URL: ' . $url );
	}

	public function edit_terms( $term_id, $taxonomy ) {
		error_log( '--- Edit Terms ---' );
		if( $url = get_term_link( $term_id, $taxonomy ) ) {
			$this->flush_urls( $url );
		}
	}

	public function pre_delete_term( $url = '' ) {
		error_log( '--- Pre Delete Term ---' );
		if( $url ) {
			$this->flush_urls( $url );
		}
	}
}

$cdn_integration = new CDN_Integration();
