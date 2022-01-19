<?php

/*
Plugin Name:  Export-PDF-RS
Version: 1.0
Description: Enable PDF to download in your WordPress site.
Author: リンセン
Author URI: 
License: GPLv2 or later
License URI: 
Text Domain: exportpdfrs
*/


define( 'MY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MY_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'MY_HOME_URL', get_home_url() );
define( 'MY_SHORTCODES', array(
    '[publish_date]'        => '日付',
    '[publish_number]'      => '連番',
    '[mem_email]'			=>	'メールアドレス',
    '[mem_name1]'			=>	'姓',
    '[mem_name2]'			=>	'名',
    '[mem_company]'         =>  '組織名',
    '[mem_zip]'			    =>	'郵便番号',
    '[mem_pref]'		    =>	'都道府県',
    '[mem_address1]'		=>	'住所',
    '[mem_address2]'		=>	'番地',
    '[mem_address3]'	    =>	'ビル・マンション名 号室',
    '[mem_tel]'		        =>	'電話番号',
    '[item_name]'           => '商品名',
    '[item_code]'           => '商品コード',
    '[item_price]'			=>	'単価',
    '[item_count]'			=>	'数量',
    '[item_all_price]'		=>	'金額',
    '[item_tax]'		    =>	'消費税',
    '[item_all]'		    =>	'合計',
    '[item_restriction]'	=>	'購入制限数',
    '[item_usedpoint]'		=>	'ポイント利用予定',
    '[order_number]'        => '注文番号',
    '[item_unit]'           => '単位',
    '[item_paysum]'         => '支払金額',
    '[mem_division]'        => '所属・部署',
    '[item_index]'          => '番号'
));



require_once MY_PLUGIN_PATH . 'includes/functions.php';



/**
 * [add_estimation_button] returns button to go to Estimate Page.
 * @return view buttons
*/
add_shortcode( 'add_estimation_button', 'estimation_button_handler' );
function exportpdfrs_init(){
    function estimation_button_handler($atts) {
        global $wp;

        global $post;
        $attr = shortcode_atts( array(
            'link' => MY_HOME_URL . '/pdf?good=' . $post->ID,
            'id' => 'id_estimation',
            'style' => 'blue',
            'size' => '',
            'label' => '見積書はこちら',
            'target' => '_self'
        ), $atts );
        $output = '<p><a href="' . esc_url( $attr['link'] ) . '" id="' . esc_attr( $attr['id'] ) . '" class="button ' . esc_attr( $attr['style'] ) . ' ' . esc_attr( $attr['size'] ) . '" target="' . esc_attr($attr['target']) . '">' . esc_attr( $attr['label'] ) . '</a></p>';
        return $output;
    }
}
add_action('init', 'exportpdfrs_init');

// カスタムCSSを適用
function exportpdfrs_enqueue_scripts() {
global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'add_estimation_button') ) {
        wp_register_style( 'salcodes-stylesheet',  MY_PLUGIN_URL . 'css/style.css' );
            wp_enqueue_style( 'salcodes-stylesheet' );
    }
}
add_action( 'wp_enqueue_scripts', 'exportpdfrs_enqueue_scripts');

// PDFページテンプレートを適用
function catch_plugin_template($template) {
    // If tp-file.php is the set template
    if( is_page_template('pdf_download.php') )
        // Update path(must be path, use WP_PLUGIN_DIR and not WP_PLUGIN_URL) 
        $template = MY_PLUGIN_PATH . '/page-template/pdf_download.php';
    // Return
    return $template;
}
// Filter page template
add_filter('page_template', 'catch_plugin_template');



// プラグインをアクティブするときにページを作成
register_activation_hook( __FILE__ , 'my_plugin_install');
function my_plugin_install() {
    global $wpdb;

    $the_page_title = '見積書情報入力ページ';
    $the_page_name = 'pdf';

    // the menu entry...
    delete_option("my_plugin_page_title");
    add_option("my_plugin_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("my_plugin_page_name");
    add_option("my_plugin_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("my_plugin_page_id");
    add_option("my_plugin_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {
        // Create post object
        $the_page = array(
            'post_title'    => $the_page_title,
            'post_content'  => "",
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
            'post_category' => array(1),
            'menu_order'    => 1000,
            'post_name'     => $the_page_name,
        );

        $post_id = wp_insert_post( $the_page );
    } else {
        // the plugin may have been previously active and the page may just be trashed...
        $post_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $post_id = wp_update_post( $the_page );
    }
    update_post_meta( $post_id, '_wp_page_template', 'pdf_download.php' );

    delete_option( 'my_plugin_page_id' );
    add_option( 'my_plugin_page_id', $post_id );

    create_plugin_database_table();
}

/* Runs on plugin deactivation */
register_deactivation_hook( __FILE__, 'my_plugin_remove') ;
function my_plugin_remove() {
    global $wpdb;
    $the_page_title = get_option( "my_plugin_page_title" );
    $the_page_name = get_option( "my_plugin_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'my_plugin_page_id' );
    if( $the_page_id ) {
        wp_delete_post( $the_page_id ); // this will trash, not delete
    }

    delete_option("my_plugin_page_title");
    delete_option("my_plugin_page_name");
    delete_option("my_plugin_page_id");
}

function create_plugin_database_table()
{
    global $table_prefix, $wpdb;

    $tblname = 'download_log';
    $wp_track_table = $table_prefix . "$tblname";

    #Check to see if the table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
    {
        $sql = "CREATE TABLE `". $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `id_user`  int(11)   NOT NULL, ";
        $sql .= "  `id_good_code`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, ";
        $sql .= "  `download_count`  int(255) NOT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) USING BTREE"; 
        $sql .= ") ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic ; ";

        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'download_file';
    $wp_track_table = $table_prefix . "$tblname";

    #Check to see if the table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
    {
        $sql = "CREATE TABLE `". $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `file_path`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) USING BTREE"; 
        $sql .= ") ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic ; ";

        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

class ExportPDF {
    protected $submenus = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( get_bloginfo('version') < 4.3 ) return;
		add_action( 'admin_menu', array($this,'exportpdfrs_plugin_menu') );
		//call register settings function
		add_action( 'admin_init', array($this, 'exportpdfrs_register_mysettings') );
		add_filter( 'sln_subject_filter', array($this, 'csbn_subject_filter'), 10, 4 );
		add_filter( 'sln_content_filter', array($this, 'csbn_content_filter'), 11, 4 );

		$this->submenus = array(
			array(
				'title'	=>	'見積書テンプレート',
				'slug'		=>	'exportpdfrs_estimate_template',
			),
			array(
				'title'	=>	'請求書テンプレート',
				'slug'		=>	'exportpdfrs_purchase_template',
			),
			array(
				'title'	=>	'領収書テンプレート',
				'slug'		=>	'exportpdfrs_receipt_template',
			),
        );
    }
    
	function exportpdfrs_plugin_menu($page) {
        add_submenu_page(
            'usces_orderlist', 
            'WelCartのPDFテンプレート', 
            'PDFテンプレート管理', 
            'manage_options', 
            'exportpdfrs_dashboard_menu_slug', 
            array($this,'exportpdfrs_dashboard_page')
        );
        add_submenu_page(
            'usces_orderlist', 
            'WelCartのPDFファイル管理', 
            'PDFファイル履歴', 
            'manage_options', 
            'exportpdfrs_dashboard_menu_slug_file', 
            array($this,'exportpdfrs_dashboard_page')
        );
	}

	function exportpdfrs_register_mysettings() {
		//register our settings
		foreach($this->submenus as $submenu){
            register_setting('exportpdfrs-settings-group1', $submenu['slug'] . '_template_content');
        }
        register_setting('exportpdfrs-settings-group2', 'exportpdfrs_file_path');
	}

    function exportpdfrs_dashboard_page(){
        $page_slug = '';
        if(isset($_GET['page'])){
            $page_slug = $_GET['page'];
        } else {
            return;
        }

        if ($page_slug == 'exportpdfrs_dashboard_menu_slug') {
            echo'<div class="wrap csbn-html-notification-wrap">';
            echo'<h2>PDFテンプレート</h2>';
            
            echo '<h2>テンプレートのショートコード</h2>';
    
            echo'<ul style="-webkit-column-count: 3; -moz-column-count: 3; column-count: 3;>';
            foreach(MY_SHORTCODES as $k => $v){
                echo'<li class="csbn-shortcode">' .$v. '	: <span style="font-weight: bold;">' .$k. '</span></li>';
            }
            echo'</ul>';
    
            echo'<form method="post" action="options.php">';
            settings_fields( 'exportpdfrs-settings-group1');
            do_settings_sections( 'exportpdfrs_dashboard_menu_slug');
    
            echo'<table class="form-table">';
            foreach($this->submenus as $submenu){
                echo'<tr><th><h2>' . $submenu['title'] . '</h2></th></tr>';
                echo'<tr valign="top">';
                echo'<td>';
                wp_editor( get_option($submenu['slug'] . '_template_content'), $submenu['slug'] . '_template_content', '' );
                echo'</td>';
                echo'</tr>';
                echo'<tr class="csbn-sepeartion" valign="top" ></tr>';
            }
            echo'</table>';
            submit_button();
            echo'</form>';
            echo'</div>';
        } else if ($page_slug == 'exportpdfrs_dashboard_menu_slug_file') {
            echo'<div class="wrap csbn-html-notification-wrap">';
            echo'<h2>PDFファイル管理</h2>';
            echo '<h2>PDFファイル保存パス</h2>';
            echo'<form method="post" action="options.php" style="margin-bottom: 60px">';
            settings_fields( 'exportpdfrs-settings-group2');
            do_settings_sections( 'exportpdfrs_dashboard_menu_slug_file');
            echo '<input type="text" style="width: 100%" name="exportpdfrs_file_path" value="' . get_option('exportpdfrs_file_path') . '"/>' ;
            submit_button('パスを保存する');
            echo'</form>';

            echo '<h2>PDFファイル履歴</h2>';
            $wp_list_table = new FileDownloadTable();
            $wp_list_table->prepare_items();
            $wp_list_table->display();

            echo'</div>';
        }
    }
}

$exportPDF = new ExportPDF();
?>