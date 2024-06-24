<?php
// 管理画面メニューの追加
function gr_abtesting_menu() {
    add_menu_page(
        'GR ABTesting Admin menu',     // ページタイトル
        'GR ABTesting',            // メニュータイトル
        'manage_options',       // 権限
        'goro-abtesting',     // スラッグ
        'gr_abtesting_page' // コールバック関数
    );
}

add_action('admin_menu', 'gr_abtesting_menu');

// Renders the GR ABTesting admin page content
// This function checks if the current user has the 'manage_options' capability
// If not, it displays an error message and prevents access to the page content
// It then queries the database for distinct post IDs from the AB test log and distinct IDs of posts with type 'gr_abtest_variation'
// It merges and filters these results to remove duplicates and then displays statistics for each post
// For each post, it shows the post title, post ID, page views, total clicks, and statistics for each AB test related to the post
// It also provides forms for data deletion and data download related to AB tests
function gr_abtesting_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    ?>
    <div class="wrap">
        <h1>Goro AB Testing Admin Page</h1>
        <p>Statistics and data download.</p>
        <h2>Usage Example</h2>
        <ol>
        <li>The custom field name of the custom post 'grab_rel_post' has the value of the post ID to be tested.</li>
        <li>The class name of the tag to insert the link is 'grab-link'.</li>
        <li>The tag to insert the link has a data attribute 'data-grab-link' to include any link ID.</li>
        </ol>
        <p>Example:  &lt;div class=&quot;grab-link&quot; data-grab-link=&quot;id-1&quot;&gt;&lt;a href=&quot;http://goro-bizaid.com&quot;&gt;GORO BIZAID&lt;/a&gt;&lt;/div&gt;</p>
        
        <?php
        global $wpdb;
        $results = array();
        $results_old = $wpdb->get_results("SELECT DISTINCT post_id FROM {$wpdb->prefix}gr_abtest_log", OBJECT);
        $test_results = $wpdb->get_results("SELECT DISTINCT ID FROM {$wpdb->prefix}posts where post_type = 'gr_abtest_variation'", OBJECT);
        foreach($test_results as $test_result){
            $post_results = $wpdb->get_results("SELECT DISTINCT meta_value FROM {$wpdb->prefix}postmeta where post_id = $test_result->ID and meta_key = 'grab_rel_post'", OBJECT);
            $results = array_merge($results, $post_results);
        }
        $results = array_unique($results, SORT_REGULAR);
        $results = array_values($results);
        if($results){
            foreach($results as $row){ //$row->meta_value は投稿ID
                echo '<div class="gr-abtest-statistics"><h2>[' . get_the_title($row->meta_value) . "]</h2>" ;//投稿のタイトルを表示
                echo "<p>Post ID : ". $row->meta_value . "</p>" ;
                
                $page_view = $wpdb->get_var( //PV数を取得
                    "SELECT COUNT(*) FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and event_type = 0");
                echo "<p>PV : ". $page_view . "</p>";
                $total_click = $wpdb->get_var( //クリック数を取得
                    "SELECT COUNT(*) FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and event_type = 1");
                echo "<p>Total Clicks : ". $total_click . "</p>";

                //テストごとの集計
                //$abtest_id = $wpdb->get_results( //テストIDを取得
                //    "SELECT DISTINCT abtest_id FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and event_type = 1", OBJECT);
                $abtests = $wpdb->get_results( //テストIDを取得
                    "SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value = $row->meta_value and meta_key = 'grab_rel_post' ORDER BY post_id ASC", OBJECT);
                
                foreach($abtests as $abtest){
                    echo "<h3>" . get_the_title($abtest->post_id) . "</h3>";
                    echo "<p>ABTest ID : " . $abtest->post_id . "</p>";//テストIDを表示
                    
                    $abtest_view = $wpdb->get_var( //テストの閲覧数を取得
                        "SELECT COUNT(*) FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and event_type = 0 and abtest_id = $abtest->post_id");
                    echo "<p>Views : ". $abtest_view . "</p>";
                    $abtest_click = $wpdb->get_var( //テストのクリック数を取得
                        "SELECT COUNT(*) FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and event_type = 1 and abtest_id = $abtest->post_id");
                    echo "<p>Clicks : ". $abtest_click . "</p>";

                    foreach($wpdb->get_results( //リンクごとのクリック数を取得
                        "SELECT abtest_link_id, COUNT(*) as count FROM {$wpdb->prefix}gr_abtest_log WHERE post_id = $row->meta_value and abtest_id = $abtest->post_id and event_type = 1 GROUP BY abtest_link_id", OBJECT) as $abtest_link){
                        echo "<p>Link ID : " . $abtest_link->abtest_link_id . " , Clicks : " . $abtest_link->count . "</p>";
                    }
                }
                
                echo "</div>"; // class="gr-abtest-statistics"
            }
        }
        ?>
        <h1>ABTest Log Data Delete</h1>
        <form class="gr-abtesting-data-delete" method="post" action="">
            <input type="hidden" name="gr_abtesting_data_delete" value="1">
            <?php submit_button('Clear Data'); ?>
        </form>
        <h1>ABTest Log Data Download</h1>
        <form method="post" action="">
            <input type="hidden" name="gr_abtesting_data_download" value="1">
            <?php submit_button('Download Data'); ?>
        </form>
    </div> <!-- // class="wrap" -->
    <?php
}
add_action( 'admin_enqueue_scripts', 'gr_abtesting_admin_script' );
// Enqueues a specific admin script for the GR ABTesting admin page
// This function first retrieves the current screen ID to determine the current admin page
// It then checks if the current page is the GR ABTesting top-level admin page
// If so, it enqueues the 'gr-abtesting-admin-script' script located in the '../assets/js/grabtest-admin.js' path
function gr_abtesting_admin_script() {
    $hook_suffix = get_current_screen()->id;

    // 特定のページのみでスクリプトを登録
    if ( 'toplevel_page_goro-abtesting' === $hook_suffix ) {
      wp_enqueue_script( 'gr-abtesting-admin-script', plugins_url( '../assets/js/grabtest-admin.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
    }
}

add_action('admin_init', 'gr_abtesting_data_delete_action');
// Handles the action to delete data from the GR ABTest log
// This function checks if the 'gr_abtesting_data_delete' POST variable is set, indicating a request to delete data
// If the request is confirmed, it uses the global $wpdb object to execute a DELETE query
// This query removes all entries from the 'gr_abtest_log' table in the WordPress database
function gr_abtesting_data_delete_action() {
    if (isset($_POST['gr_abtesting_data_delete'])) {
        // データを削除する
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}gr_abtest_log");
    }
}

add_action('admin_init', 'gr_abtesting_data_download_action');
// Initiates the download of ABTest log data as a CSV file
// This function checks if the 'gr_abtesting_data_download' POST variable is set, indicating a request for data download
// It retrieves all entries from the 'gr_abtest_log' table using the global $wpdb object
// Sets the HTTP headers to serve a plain text file named "ABTestLogData.csv"
// Iterates over the retrieved data, formatting each row as a CSV line and echoing it out
// Exits the script to prevent further output, ensuring the CSV file is cleanly served to the user
function gr_abtesting_data_download_action() {
    if (isset($_POST['gr_abtesting_data_download'])) {
        // データを取得する
        global $wpdb;
        $data = array();
        $data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gr_abtest_log", OBJECT);

        // ファイルを作成する
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="ABTestLogData.csv"');
        echo "id,event_type,post_id,abtest_id,abtest_link_id,uuid,timestamp\n";
        foreach ($data as $row) {
            echo $row->id . ","  . $row->event_type . "," . $row->post_id . "," . $row->abtest_id . "," . $row->abtest_link_id . "," . $row->uuid . "," . $row->timestamp . "\n";
        }   
        exit;
    }
}

?>
