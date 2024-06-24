<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

/**
 * Deletes all posts associated with a custom post type upon plugin uninstallation.
 * This function targets the 'gr_abtest_variation' custom post type and retrieves all posts
 * of this type, regardless of their post status (including 'publish', 'pending', 'draft',
 * 'auto-draft', 'future', 'private', 'inherit', 'trash'). It then iterates over each post
 * and permanently deletes them from the database using wp_delete_post.
 */
function delete_custom_post_type_data() {
    $custom_post_type = 'gr_abtest_variation'; // カスタム投稿タイプのスラッグ

    // カスタム投稿タイプのすべての投稿を取得
    $posts = get_posts( array(
        'post_type' => $custom_post_type,
        'numberposts' => -1,
        //'post_status' => 'any',
        'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
    ));

    if (empty($posts)) {
        // 投稿が見つからない場合のエラーハンドリング
        error_log('No posts found for custom post type: ' . $custom_post_type);
        return; // 処理を中断
    }

    // すべての投稿を削除
    foreach ($posts as $post) {
        $result = wp_delete_post($post->ID, true);
        if (false === $result) {
            // 投稿の削除に失敗した場合のエラーハンドリング
            error_log('Failed to delete post with ID: ' . $post->ID);
        }
    }
}

/**
 * Unregisters a custom post type.
 * This function removes the specified custom post type from the global $wp_post_types array,
 * effectively unregistering it from WordPress. It targets the custom post type with the slug
 * 'gr_abtest_variation'.
 */
function unregister_custom_post_type() {
    global $wp_post_types;
    
    $custom_post_type = 'gr_abtest_variation'; // カスタム投稿タイプのスラッグ

    if ( isset( $wp_post_types[ $custom_post_type ] ) ) {
        unset( $wp_post_types[ $custom_post_type ] );
    }
}

set_time_limit(0); // タイムアウトを無効化

delete_custom_post_type_data();
unregister_custom_post_type();

// drop a custom database table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gr_abtest_log" );