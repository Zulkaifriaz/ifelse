<?php
/**
 * Uninstall file for IfElse Pages plugin.
 *
 * Runs when the plugin is deleted from WordPress admin (Plugins → Delete).
 * This file is called by WordPress directly; it must never be called from
 * within the plugin itself.
 *
 * @package IfElsePages
 */

// Bail if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ── Single-site cleanup ───────────────────────────────────────────────────────

/**
 * Remove all options stored by the plugin.
 */
delete_option( 'ifelse_pages_settings' );

/**
 * Clear any object cache entries the plugin may have set.
 */
wp_cache_delete( 'ifelse_pages_settings', 'ifelse-pages' );

// ── Multisite cleanup ─────────────────────────────────────────────────────────

if ( is_multisite() ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	$ifelsepages_blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

	if ( ! empty( $ifelsepages_blog_ids ) ) {
		foreach ( $ifelsepages_blog_ids as $ifelsepages_blog_id ) {
			switch_to_blog( (int) $ifelsepages_blog_id );

			delete_option( 'ifelse_pages_settings' );
			wp_cache_delete( 'ifelse_pages_settings', 'ifelse-pages' );

			restore_current_blog();
		}
	}
}
