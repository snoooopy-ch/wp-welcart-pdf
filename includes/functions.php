<?php
require_once MY_PLUGIN_PATH . '/vendor/autoload.php';
require_once MY_PLUGIN_PATH . '/filetable.class.php';

use \Dompdf\Dompdf;
use \Dompdf\Options;

function wbiyoka_style_and_scripts() {
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', array(), null, true);
    wp_enqueue_script( 'ajaxform', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js', array(), null, true );
    wp_enqueue_script('index_script', MY_PLUGIN_URL . '/js/exportpdfrs-index.js', array(), '' );
	wp_enqueue_style('admin-styles', MY_PLUGIN_URL . '/css/style.css');
}
add_action( 'admin_enqueue_scripts' , 'wbiyoka_style_and_scripts' );

function print_log( $filename = "", $functionname = "", $tagname = "", $message = 'default') {
	global $wpdb;

	ob_start();
	var_dump($message);
	$result = ob_get_clean();

	$wpdb->insert(
		'wp_debug',
		array(
			'file' 		=> $filename,
			'tag' 		=> $functionname,
			'name' 		=> $tagname,
			'message' 	=> $result,
		)
	);
}

add_action('init', 'show_gen_invoice');
function show_gen_invoice() {
    global $usces;
    $item = array();

    $pdf_page = false;

    ob_clean();
    $html_head = '<!DOCTYPE html>
    <html class="loading" lang="jp" data-textdirection="ltr">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        @font-face {
            font-family: "ipag";
            font-style: normal;
            font-weight: normal;
            src: url(MY_PLUGIN_URL . "/fonts/ipag.ttf");
        }
        @font-face{
            font-family: "ipag";
            font-style: bold;
            font-weight: bold;
            src:url(MY_PLUGIN_URL . "/fonts/ipag.ttf");
        }
        body {
            font-family: "ipag", !important;
            font-size: 16px;
        }
        table, th, td {
            border: 1px solid black;
            padding: 2px;
            word-break: break-all;
            word-wrap: break-word;
        }
        td {
            padding-left: 5px;
            padding-right: 5px;
        }
        h2 {
            margin-top: 0.3rem;
            margin-bottom: 0.3rem;
        }
        .styled-table {
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 0.9em;
            font-family: sans-serif;
            min-width: 400px;
            width: 100%;
            table-layout: fixed;
            width: 100%;
            border: none;
        }
    </style>
    </head>
    <body>
    <p>';
        
    $html_foot = '</p>
    </body></html>';

    $template = '';

    //Check if invoice is set and get value = the path of the invoice
    if (!empty($_POST['export_estimate_pdf'])) {
        $pdf_page = true;
        $good_post_id = $_POST['export_estimate_pdf'];

        $item['item_code'] = get_post_meta($good_post_id, '_itemCode', true);
        $item['item_name'] = get_post_meta($good_post_id, '_itemName', true);
        $item['item_restriction'] = get_post_meta($good_post_id, '_itemRestriction', true);
        $item['item_pointrate'] = get_post_meta($good_post_id, '_itemPointrate', true);
        $item['item_GpNum1'] = get_post_meta($good_post_id, '_itemGpNum1', true);
        $item['item_GpNum2'] = get_post_meta($good_post_id, '_itemGpNum2', true);
        $item['item_GpNum3'] = get_post_meta($good_post_id, '_itemGpNum3', true);
        $item['item_GpDis1'] = get_post_meta($good_post_id, '_itemGpDis1', true);
        $item['item_GpDis2'] = get_post_meta($good_post_id, '_itemGpDis2', true);
        $item['item_GpDis3'] = get_post_meta($good_post_id, '_itemGpDis3', true);

        $item['mem_email'] = $_POST['mem_email'];
        $item['mem_name1'] = $_POST['mem_name1'];
        $item['mem_name2'] = $_POST['mem_name2'];
        $item['mem_zip'] = $_POST['mem_zip'];
        $item['mem_pref'] = $_POST['mem_pref'];
        $item['mem_address1'] = $_POST['mem_address1'];
        $item['mem_address2'] = $_POST['mem_address2'];
        $item['mem_address3'] = $_POST['mem_address3'];
        $item['mem_company'] = $_POST['mem_company'];
        $item['mem_tel'] = $_POST['mem_tel'];
        $item['item_count'] = $_POST['item_count'];
        $item['order_number'] = '';
        $item['mem_point'] = $_POST['mem_point'];
        $item['mem_division'] = $_POST['mem_division'];

        $skus = $usces->get_skus($good_post_id);
        if (count($skus) == 1) {
            $item['item_price'] = $skus[0]['price'];
            $item['item_unit'] = $skus[0]['unit'];
        }

        $item['item_all_price'] = (int)$item['item_price'] * (int)$item['item_count'];
        $item['item_paysum'] = $item['item_all_price'];
        $item['item_usedpoint'] = $item['mem_point'];

        if ($item['item_paysum'] <= $item['item_usedpoint']) {
            $item['item_usedpoint'] = $item['item_paysum'];
        }
        $item['item_all'] = $item['item_paysum'] - $item['item_usedpoint'];
        $item['item_tax'] = (int)($item['item_all'] / 11);

        $item['item_all_price'] = number_format($item['item_all_price']/ 11 * 10);
        $item['item_paysum'] = number_format($item['item_paysum']/ 11 * 10);
        $item['item_tax'] = number_format($item['item_tax']);
        $item['item_all'] = number_format($item['item_all']);
        $item['item_price'] = number_format($item['item_price']/ 11 * 10);
        $item['item_usedpoint'] = number_format($item['item_usedpoint']);

        date_default_timezone_set('Asia/Tokyo');
        $date = date('Ymd', time());

        $serial_number = serial_number_estimate_pdf($_POST['mid'], $item['item_code']);
        $item['publish_date'] = $date;
        $item['publish_number'] = 'M-' .  $item['item_code'] .'-' . $item['publish_date'] . '-' . $serial_number;

        $template = get_option('exportpdfrs_estimate_template_template_content');
        $template = $html_head . $template . $html_foot;
        $search = array_keys(MY_SHORTCODES);
        $replace = array();
		foreach($search as $s){
            $s = str_replace(array('[', ']'), '', $s);
			$replace[] = $item[$s];
		}
        $template  = str_replace($search, $replace, $template);
    }

    $orders = array();
    if (!empty($_POST['order_id'])) {
        $pdf_page = true;
        $order_id = $_POST['order_id'];

        $usces_members = $usces->get_member();
        $history = $usces->get_member_history($usces_members['ID']);
        $usces_member_history = apply_filters( 'usces_filter_get_member_history', $history, $usces_members['ID'] );

        foreach ( $usces_member_history as $umhs ) {
            if ($umhs['ID'] == $order_id) {
                $good_price = $umhs['total_items_price'];
                $item['item_paysum'] = number_format($good_price/ 11 * 10);
                $item['order_id'] = $order_id;

                $total_price = $umhs['total_items_price']-$umhs['usedpoint']+$umhs['discount']+$umhs['shipping_charge']+$umhs['cod_fee']+$umhs['tax'];
                if ($total_price < 0) $total_price = 0;
                $condition = $umhs['condition'];
                $tax_mode = ( isset( $condition['tax_mode'] ) ) ? $condition['tax_mode'] : usces_get_tax_mode();
                $tax = usces_order_history_tax( $umhs, $tax_mode );
                $tax = str_replace(',', '', $tax);
                preg_match_all('/\b\d+\b/', $tax, $matches);
                $item['item_tax'] = number_format($matches[0][0]);

                foreach($umhs['cart'] as $index => $cart) {
                    $orders[$index]['item_index'] = $index + 1;
                    $orders[$index]['item_all_price'] = number_format($cart['price']/ 11 * 10);
                    $orders[$index]['order_number']   = $cart['order_id'];
                    $orders[$index]['item_code']      = $cart['item_code'];
                    $orders[$index]['item_name']      = $cart['item_name'];
                    $orders[$index]['item_price']     = number_format($cart['price']/ 11 * 10);
                    $orders[$index]['item_unit']      = $cart['unit'];
                    $orders[$index]['item_count']     = $cart['quantity'];
                    $orders[$index]['item_restriction'] = '';
                }

                $item['item_all'] = number_format($total_price);
                $item['item_usedpoint'] = $umhs['usedpoint'];
                break;
            }
        }
        $mid = $usces->get_member()['ID'];

        if ( $mid != 0) {
            $usces_user = $usces->get_member();
            $item['mem_email'] = $usces_user['mailaddress1'];
            $item['mem_name1'] = $usces_user['name1'];
            $item['mem_name2'] = $usces_user['name2'];
            $item['mem_zip'] = $usces_user['zipcode'];
            $item['mem_pref'] = $usces_user['pref'];
            $item['mem_address1'] = $usces_user['address1'];
            $item['mem_address2'] = $usces_user['address2'];
            $item['mem_address3'] = $usces_user['address3'];
            $item['mem_company']    = $usces_user['custom_member']['mem_company']?? '';
            $item['mem_division']    = $usces_user['custom_member']['mem_division']?? '';
            $item['mem_tel'] = $usces_user['tel'];
            // $item['mem_point'] = $usces_user['mem_point'];
        }

        date_default_timezone_set('Asia/Tokyo');
        $date = date('Ymd', time());

        $mode = 0;
        $item['publish_date'] = $date;

        if (!empty($_POST['export_purchase_pdf'])) {
            $mode = 1;
            $item['publish_number'] = 'S-' .  $item['order_id'] .'-' . $item['publish_date'] . '-' . (string)$order_id;
            $template = get_option('exportpdfrs_purchase_template_template_content');
        }
        if (!empty($_POST['export_receipt_pdf'])) {
            $mode = 0;
            $item['publish_number'] = 'R-' .  $item['order_id'] .'-' . $item['publish_date'] . '-' . (string)$order_id;
            $template = get_option('exportpdfrs_receipt_template_template_content');
        }

        preg_match('/<tr class=\"goods\">((?s).*?)<\/tr>/i', $template, $matches);
        $template_low = $matches[0];


        $low = '';
        foreach ($orders as $index => $order) {
            $order_keys = array_keys($order);
            $order_replace = array();
            foreach ($order_keys as $key) {
                $order_replace[] = $order[$key];
            }
            $low .= str_replace(array('[', ']'), '', str_replace($order_keys, $order_replace, $template_low));
        }
        $template = preg_replace('/<tr class=\"goods\">((?s).*?)<\/tr>/i', $low, $template);

        $template = $html_head . $template . $html_foot;
        $search = array_keys(MY_SHORTCODES);
        $replace = array();
		foreach($search as $s){
            $s = str_replace(array('[', ']'), '', $s);
			$replace[] = $item[$s];
		}
        $template  = str_replace($search, $replace, $template);
    }

    if ($pdf_page == true) {
        $template = preg_replace_callback('/src="([^"]*)"/i', function($matches){
            $path = $matches[1];
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return 'src="' . $base64 . '"';
        }, $template);

        $options = new Options();
        $options->set('A4','potrait');
        $options->set('enable_css_float',true);
        $options->set('isHtml5ParserEnabled', true);
    
        $dompdf = new DOMPDF($options);
        $dompdf->loadHtml($template);
        $dompdf->render();

        $dir = get_option('exportpdfrs_file_path');
        if (!file_exists($dir)) {
            mkdir($dir, 0700);
        }

        $output = $dompdf->output();
        $full_path = $dir . DIRECTORY_SEPARATOR . $item['publish_number'] . '.pdf';
        file_put_contents($full_path, $output);
        insert_file_path($full_path);
    
        $dompdf->stream($item['publish_number'] . '.pdf');
        ob_flush();
        exit(1);
    }
}

function serial_number_estimate_pdf($mid, $good_code) {
    global $wpdb;
    $result = $wpdb->get_results("Select count(*) as count FROM wp_download_log WHERE id_good_code like '" . $good_code . "';");

    $count = 0;
    if ($result && is_array($result) && (sizeof($result) == 1)) {
        $count = $result[0]->count;
    }

    $count = $count + 1;

    $wpdb->insert('wp_download_log', array(
        'id_good_code'      => $good_code,
        'id_user'           => $mid,
        'download_count'    => $count,
    ));

    $count = str_pad($count, 4, '0', STR_PAD_LEFT); 
    return $count;
}

function insert_file_path($file_path) {
    global $wpdb;
    $result = $wpdb->get_results("Select count(*) as count FROM wp_download_file WHERE file_path like '" . $file_path . "';");

    $count = 0;
    if ($result && is_array($result) && (sizeof($result) == 1)) {
        $count = $result[0]->count;
    }

    if ($count == 0) {
        $wpdb->insert('wp_download_file', array(
            'file_path'      => $file_path,
        ));
    }
}

function messaging_post() {
}

add_action( 'wp_ajax_nopriv_messaging_post', 'messaging_post' );add_action( 'admin_init', 'show_gen_invoice' );
add_action( 'wp_ajax_messaging_post', 'messaging_post' );

add_filter('usces_filter_member_history_header', 'usces_filter_member_history_header_handler', 1000, 2);
function usces_filter_member_history_header_handler( $a, $b ) {
    $a = '<tr><td class="retail" colspan="8"><div style="display: flex;">';
    $a .= '<form action="#" method="POST" target="_blank">';
    $a .= '<input type="hidden" id="export_purchase_pdf" name="export_purchase_pdf" value="1" /><input type="hidden" name="order_id" value="' . $b['ID'] . '" /><input class="button button-primary user_export_button p-button" type="submit" value="請求書PDF" />';
    $a .= '</form>&nbsp;&nbsp;&nbsp;';

    if ($b['order_status'] == 'receipted') {
        $a .= '<form action="#" method="POST" target="_blank">';
        $a .= '<input type="hidden" id="export_receipt_pdf" name="export_receipt_pdf" value="1" /><input type="hidden" name="order_id" value="' . $b['ID'] . '" /><input class="button button-primary user_export_button p-button" type="submit" value="領収書PDF" />';
        $a .= '</form>';
    }
    
    $a .= '</div></td><td class="retail"></td></tr>';
    return $a;
}
?>