<?php
/**
 * Plugin Name:  GR AB Testing
 * Description: A plugin to perform AB testing on posts.
 * Version: 1.0
 * Author: Goro
 */
/**
 * １．カスタム投稿のカスタムフィールド名　'grab_rel_post'　の　値がテストを行う投稿のID
 * ２．リンクを入れるタグの class名は　'grab-link'　とする　
 * ３．リンクを入れるタグの　data-grab-link　にはリンクの任意のIDを入れる 
 * 例　<div class="grab-link" data-grab-link="id-1"><a href="http://www.google.co.jp">google</a></div>
 */

require_once('includes/grab-admin-menu.php');


/**
 * Creates the AB test log table.
 *
 * This function is responsible for creating the database table that will be used to store the log data for AB testing.
 * It should be called during the plugin activation process.
 */
function create_gr_abtest_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gr_abtest_log';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            event_type int(1) NOT NULL,
            post_id INT(11) NOT NULL,
            abtest_id INT(11) NOT NULL,
            abtest_link_id VARCHAR(50) NOT NULL,
            uuid VARCHAR(36) NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'create_gr_abtest_log_table');

/**
 * Creates a custom post type for AB testing.
 *
 * This function is responsible for creating a custom post type called "AB Test" for managing AB testing in WordPress.
 * It should be called during the plugin activation process.
 */

function create_gr_abtest_post_type() {
    register_post_type('gr_abtest_variation',
        array(
            'labels' => array(
                'name' => __('GR AB Test Variations'),
                'singular_name' => __('GR AB Test Variation')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
        )
    );
}
add_action('init', 'create_gr_abtest_post_type');

/**
 * Generates a unique UUID (Universally Unique Identifier).
 *
 * @return string The generated UUID.
 */
function generate_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // バージョン4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // IETF variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Modifies the content to display A/B test content.
 *
 * This function is responsible for modifying the content to display A/B test content.
 * It takes the original content as input and returns the modified content.
 *
 * @param string $content The original content.
 * @return string The modified content.
 */

$abtest_localize_data = array();

function display_gr_abtest_content() {
    global $post;
    global $abtest_localize_data;
    $args = array(
        'post_type' => 'gr_abtest_variation',
        'meta_query' => array(
            array(
                'key' => 'grab_rel_post',
                'value' => $post->ID,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args); // 投稿から grab_rel_postに$post->IDが入っているものを取得

    if ($query->have_posts() && is_single()) {
        $variations = array();
        while ($query->have_posts()) {
            $query->the_post();
            $variations[] = get_the_content();
        }
        wp_reset_postdata();

        if (!empty($variations)) {
            $keys = array_keys($variations);
            $random_index = $keys[mt_rand(0, count($keys) - 1)];
            $content = $variations[$random_index];
            $post_id = $post->ID;
            $abtest_id = $query->posts[$random_index]->ID;
            $uuid = generate_uuid();
            log_gr_abtest_display($post_id, $abtest_id, $uuid);
            // ローカライズするデータを設定
            $abtest_localize_data = array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'grabPostNonce' => wp_create_nonce('gr-abtest-post-nonce'),
                'postId' => $post_id,
                'abtestId' => $abtest_id,
                'uuid' => $uuid,
            );
        }
    } else {
        $content = get_the_content();}
    return $content;
}
add_filter('the_content', 'display_gr_abtest_content');

/**
 * Enqueues the necessary scripts for the AB testing plugin.
 *
 * This function is responsible for enqueueing the required scripts for the AB testing plugin to function properly.
 * It should be called in the appropriate hook to ensure the scripts are loaded at the right time.
 */
function gr_abtest_enqueue_scripts() {
    global $abtest_localize_data;
    wp_enqueue_script('gr-abtest-script', plugin_dir_url(__FILE__) . 'assets/js/' . 'grabtest.js', array('jquery'), null, true);
    $inline_script = 'var grabLocalizeData = ' . json_encode($abtest_localize_data) . ';';
    wp_add_inline_script('gr-abtest-script', $inline_script);
}
add_action('wp_footer', 'gr_abtest_enqueue_scripts');

/*
 * Logs the display of an A/B test.
 *
 * This function is responsible for logging the display of an A/B test. It takes the post ID, A/B test ID, and UUID as parameters.
 *
 * @param int    $post_id   The ID of the post where the A/B test is being displayed.
 * @param int    $abtest_id The ID of the A/B test.
 * @param string $uuid      The UUID (Universally Unique Identifier) associated with the user.
 */
function log_gr_abtest_display($post_id, $abtest_id, $uuid) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gr_abtest_log';
    $link_id = ""; //値は入らない
    $timestamp = current_time('mysql');
    $data = array(
        'event_type' => 0, //ページの読み込み
        'post_id' => $post_id,
        'abtest_id' => $abtest_id,
        'abtest_link_id' => $link_id,
        'uuid' => $uuid,
        'timestamp' => $timestamp,
    );
    $result = $wpdb->insert($table_name, $data);
    if ($result === false) {
        // エラーメッセージを取得します
        $error_message = $wpdb->last_error;
        // デバッグモードの場合はエラーメッセージをログに記録します
        log_error_message("Failed to insert data into $table_name: $error_message");
        exit;
    }
}

/**
 * Logs the click event for the A/B testing.
 */
function log_gr_abtest_click() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gr_abtest_log';
    $nonce = $_POST['grAbtestPostNonce'];
    // nonce の検証
    if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'gr-abtest-post-nonce' ) ) {
        log_error_message( 'Nonce value cannot be verified.' );
        exit;
    }
    $post_id = filter_input(INPUT_POST, 'postId', FILTER_VALIDATE_INT);
    if ( $post_id === false ) {
        log_error_message( 'Post ID is invalid.' );
        exit;
    }
    $abtest_id = filter_input(INPUT_POST, 'abtestId', FILTER_VALIDATE_INT);
    if ( $abtest_id === false ) {
        log_error_message( 'AB Test ID is invalid.' );
        exit;
    }
    $link_id = sanitize_text_field($_POST['linkId']);
    if ( $link_id === false ) {
        log_error_message( 'Link ID is invalid.' );
        exit;
    }   
    $uuid = sanitize_text_field($_POST['uuid']);
    if ( $uuid === false ) {
        log_error_message( 'UUID is invalid.' );
        exit;
    }
    $timestamp = sanitize_text_field($_POST['timeStamp']);
    if ( $timestamp === false ) {
        log_error_message( 'Timestamp is invalid.' );
        exit;
    }   
    $data = array(
        'event_type' => 1, //リンクのクリック
        'post_id' => $post_id,
        'abtest_id' => $abtest_id,
        'abtest_link_id' => $link_id,
        'uuid' => $uuid,
        'timestamp' => $timestamp,
    );
    $result = $wpdb->insert($table_name, $data);
    if ($result === false) {
        // エラーメッセージを取得します
        $error_message = $wpdb->last_error;
        // デバッグモードの場合はエラーメッセージをログに記録します
        log_error_message("Failed to insert data into $table_name: $error_message");
        exit;
    }
}

add_action('wp_ajax_gr_abtest_post', 'log_gr_abtest_click');
add_action('wp_ajax_nopriv_gr_abtest_post', 'log_gr_abtest_click');

/**
 * Logs an error message.
 *
 * @param string $error_message The error message to be logged.
 */
function log_error_message($error_message){
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log($error_message);
    } elseif(defined('WP_DEBUG') && WP_DEBUG ){
        wp_die($error_message);
    }
}