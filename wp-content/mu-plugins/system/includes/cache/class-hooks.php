<?php

namespace WAPaaS\MWP\Cache;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Hooks {

	/**
	 * Array of hooks that trigger a cache flush.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $flush_hooks = [
		'_core_updated_successfully' => [ 'object', 'http', 'opcode', 'transient' ],
		'activated_plugin'           => [ 'object', 'http', 'opcode', 'transient' ],
		'customize_save'             => [ 'object', 'http' ],
		'deactivated_plugin'         => [ 'object', 'http', 'opcode', 'transient' ],
		'deleted_plugin'             => [ 'object', 'opcode', 'transient' ],
		'pre_uninstall_plugin'       => [ 'object', 'opcode', 'transient' ],
		'switch_theme'               => [ 'object', 'http', 'opcode', 'transient' ],
		'upgrader_process_complete'  => [ 'object', 'http', 'opcode', 'transient' ],
		'wp_delete_nav_menu'         => [ 'object', 'http' ],
		'wp_update_nav_menu'         => [ 'object', 'http' ],
	];

	/**
	 * Array of options that trigger a cache flush.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $flush_options = [
		'avatar_default'               => [ 'object', 'http' ],
		'avatar_rating'                => [ 'object', 'http' ],
		'blog_public'                  => [ 'object', 'http' ],
		'blogdescription'              => [ 'object', 'http', 'transient' ],
		'blogname'                     => [ 'object', 'http', 'transient' ],
		'category_base'                => [ 'object', 'http', 'transient' ],
		'category_children'            => [ 'object', 'http' ],
		'close_comments_days_old'      => [ 'object', 'http' ],
		'close_comments_for_old_posts' => [ 'object', 'http' ],
		'comment_order'                => [ 'object', 'http' ],
		'comment_registration'         => [ 'object', 'http' ],
		'comments_per_page'            => [ 'object', 'http', 'transient' ],
		'date_format'                  => [ 'object', 'http', 'transient' ],
		'default_comments_page'        => [ 'object', 'http' ],
		'gmt_offset'                   => [ 'object', 'http', 'transient' ],
		'hack_file'                    => [ 'object', 'http', 'opcode' ],
		'link_manager_enabled'         => [ 'object', 'http' ],
		'links_updated_date_format'    => [ 'object', 'http' ],
		'page_comments'                => [ 'object', 'http' ],
		'page_for_posts'               => [ 'object', 'http' ],
		'page_on_front'                => [ 'object', 'http' ],
		'permalink_structure'          => [ 'object', 'http', 'transient' ],
		'posts_per_page'               => [ 'object', 'http', 'transient' ],
		'posts_per_rss'                => [ 'object', 'http' ],
		'recently_edited'              => [ 'object', 'http', 'opcode' ],
		'require_name_email'           => [ 'object', 'http' ],
		'rewrite_rules'                => [ 'object', 'http', 'transient' ],
		'rss_use_excerpt'              => [ 'object', 'http' ],
		'show_avatars'                 => [ 'object', 'http' ],
		'show_on_front'                => [ 'object', 'http' ],
		'sidebars_widgets'             => [ 'object', 'http' ],
		'site_icon'                    => [ 'object', 'http' ],
		'start_of_week'                => [ 'object', 'http' ],
		'sticky_posts'                 => [ 'object', 'http' ],
		'tag_base'                     => [ 'object', 'http', 'transient' ],
		'thread_comments'              => [ 'object', 'http' ],
		'thread_comments_depth'        => [ 'object', 'http' ],
		'time_format'                  => [ 'object', 'http', 'transient' ],
		'timezone_string'              => [ 'object', 'http', 'transient' ],
		'use_smilies'                  => [ 'object', 'http' ],
		'users_can_register'           => [ 'object', 'http' ],
		'wp_user_roles'                => [ 'object', 'http' ],
		'WPLANG'                       => [ 'object', 'http', 'transient' ],
	];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		foreach ( $this->flush_hooks as $hook => $types ) {

			add_action( $hook, function () use ( $types ) {

				Control::flush( $types );

			}, -999, 0 );

		}

		add_action( 'update_option',       [ $this, 'flush_options' ], 999, 3 );
		add_action( 'clean_comment_cache', [ $this, 'purge_comment_urls' ], -999 );
		add_action( 'clean_post_cache',    [ $this, 'purge_post_urls' ], -999, 2 );

	}

	/**
	 * Process a full cache flush when certain options change.
	 *
	 * @action update_option - 999
	 * @since  1.0.0
	 *
	 * @param string $option
	 * @param mixed  $old_value
	 * @param mixed  $new_value
	 */
	public function flush_options( $option, $old_value, $new_value ) {

		if ( $old_value === $new_value ) {

			return;

		}

		if ( isset( $this->flush_options[ $option ] ) ) {

			Control::flush( $this->flush_options[ $option ] );

			return;

		}

		// Wildcard match for special option name prefixes.
		if ( 0 === strpos( $option, 'widget_' ) || 0 === strpos( $option, 'theme_mods_' ) ) {

			Control::flush( [ 'object', 'http' ] );

		}

	}

	/**
	 * Purge URLs for a comment.
	 *
	 * @action clean_comment_cache
	 * @since  1.0.0
	 *
	 * @param  int $comment_id
	 */
	public function purge_comment_urls( $comment_id ) {

		$comment = get_comment( $comment_id );

		$urls = isset( $comment->comment_post_ID ) ? Control::get_urls_for_post( $comment->comment_post_ID ) : [];

		if ( $urls ) {

			Control::purge( $urls );

		}

	}

	/**
	 * Purge URLs for a post.
	 *
	 * @action clean_post_cache
	 * @since 1.0.0
	 *
	 * @param  int     $post_id
	 * @param  WP_Post $post
	 */
	public function purge_post_urls( $post_id, $post ) {

		$urls = Control::get_urls_for_post( $post );

		if ( $urls ) {

			Control::flush( [ 'object' ] );

			Control::purge( $urls );

		}

	}

}
