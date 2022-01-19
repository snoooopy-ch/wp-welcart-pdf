<?php /* Template Name: データページ */ ?>

<?php
$active_sidebar = get_active_sidebar();
get_header();

global $_GET;
$good_post_id = '';
if (isset($_GET['good'])) {
    $good_post_id = $_GET['good'];
}

if ($good_post_id == '') {
    exit;
}
    
?>

<main class="l-main">
<?php
get_template_part( 'template-parts/page-header' );
get_template_part( 'template-parts/breadcrumb' );

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		if ( $active_sidebar ) :
?>
	<div class="l-inner l-2columns">
<?php
		endif;
?>
		<article class="p-entry p-entry-page <?php echo $active_sidebar ? 'l-primary' : 'l-inner'; ?>">
<?php
		if ( has_post_thumbnail() ) :
			echo "\t\t\t<div class=\"p-entry__thumbnail\">";
			the_post_thumbnail( 'size5' );
			echo "</div>\n";
		endif;
?>
			<div class="p-entry__body p-entry-page__body">
<?php
// コンテンツ！

        global $usces;
        global $usces_states;
        $mid = $usces->current_member['id'];
        $html = '';
        $mem_email = '';
        $mem_name1 = '';
        $mem_name2 = '';
        $mem_zip = '';
        $mem_pref = '';
        $mem_address1 = '';
        $mem_address2 = '';
        $mem_address3 = '';
        $mem_tel = '';
        $mem_company = '';
        $mem_point = 0;
        $mem_division = '';


        if ( $mid != 0) {
            $usces_user = $usces->get_member_info($mid);

            $mem_email = $usces_user['mem_email'];
            $mem_name1 = $usces_user['mem_name1'];
            $mem_name2 = $usces_user['mem_name2'];
            $mem_zip = $usces_user['mem_zip'];
            $mem_pref = $usces_user['mem_pref'];
            $mem_address1 = $usces_user['mem_address1'];
            $mem_address2 = $usces_user['mem_address2'];
            $mem_address3 = $usces_user['mem_address3'];
            $mem_tel = $usces_user['mem_tel'];
            $mem_point = $usces_user['mem_point'];
            $mem_company = $usces_user['csmb_mem_company']?? '';
            $mem_division = $usces_user['csmb_mem_division']?? '';
        } else {

        }

        $skus = $usces->get_skus($good_post_id);
        if (count($skus) == 1) {
            $item_price = $skus[0]['price'];
        }

        $html = '<div class="p-wc p-wc-mypage"><form action="#" method="POST" target="_blank"><input name="mid" type="text" value="' . $mid . '" hidden/>';
        $html .= '<table class="history_head"><thead><tr class="order_head_label">';
        $html .= '<th class="historyrow order_number">商品コード</th>';
        $html .= '<th class="historyrow purchase_date">商品名</th>';
        $html .= '<th class="historyrow purchase_price">単価</th>';
        $html .= '<th class="historyrow discount">購入制限数</th>';
        $html .= '<th class="historyrow discount">ポイント率</th>';
        $html .= '<th class="historyrow discount">数量</th>';
        $html .= '<th class="historyrow discount">利用予定ポイント</th></tr></thead>';
        $html .= '<tbody><tr>';
        $html .= '<td class="historyrow order_number">' . get_post_meta($good_post_id, '_itemCode', true). '</td>';
        $html .= '<td class="historyrow purchase_date">'. get_post_meta($good_post_id, '_itemName', true). '</td>';
        $html .= '<td class="historyrow purchase_price">' . $item_price . '</td>';
        $html .= '<td class="historyrow discount">' . get_post_meta($good_post_id, '_itemRestriction', true). '</td>';
        $html .= '<td class="historyrow discount">'. get_post_meta($good_post_id, '_itemPointrate', true). '</td>';
        $html .= '<td class="historyrow discount"><input name="item_count" id="item_count" type="text" value="1" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active"></td>';
        $html .= '<td class="historyrow discount"><input name="mem_point" id="mem_point" type="text" value="0" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active"></td>';
        $html .= '</tr></tbody></table>';
        
        $html .= '<table class="p-wc-customer_form"><tbody>';
        $html .= '<tr><th scope="row">メールアドレス</th><td colspan="2"><input name="mem_email" id="mailaddress1" type="text" value="' . $mem_email . '"></td></tr>';
        $html .= '<tr id="name_row" class="inp1"><th width="127" scope="row"><em>＊</em>お名前</th><td class="name_td">姓<input name="mem_name1" id="name1" type="text" value="' . $mem_name1. '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active"></td><td class="name_td">名<input name="mem_name2" id="name2" type="text" value="' . $mem_name2 . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active"></td></tr>';
        $html .= '<tr id="zipcode_row"><th scope="row">郵便番号</th><td colspan="2"><input name="mem_zip" id="zipcode" type="text" value="' . $mem_zip . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive">例：123-4567</td></tr>';
        $html .= '<tr id="company_row"><th scope="row">組織名</th><td colspan="2"><input name="mem_company" id="mem_company" type="text" value="' . $mem_company . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive">例：***株式会社</td></tr>';
        $html .= '<tr id="company_row"><th scope="row">所属・部署</th><td colspan="2"><input name="mem_division" id="mem_division" type="text" value="' . $mem_division . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive"></td></tr>';
        $html .= '<tr id="states_row"><th scope="row">都道府県</th><td colspan="2"><select name="mem_pref" id="member_pref" class="pref">';
        $html .= '<option value="--選択--">--選択--</option>';
        foreach($usces_states['JP'] as $key => $province) {
            if ($province == $mem_pref)
                $html .= '<option value="' . $province . '" selected>' . $province . '</option>';
            else
                $html .= '<option value="' . $province . '">' . $province . '</option>';
        }
        $html .= '</select></td></tr>';
        $html .= '<tr id="address1_row" class="inp2"><th scope="row">市区郡町村</th><td colspan="2"><input name="mem_address1" id="address1" type="text" value="' . $mem_address1 . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active">例：大阪市中央区</td></tr>';
        $html .= '<tr id="address2_row"><th scope="row">番地</th><td colspan="2"><input name="mem_address2" id="address2" type="text" value="' . $mem_address2 . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active">例：3-24-555</td></tr>';
        $html .= '<tr id="address3_row"><th scope="row">ビル・マンション名 号室</th><td colspan="2"><input name="mem_address3" id="address3" type="text" value="' . $mem_address3 .'" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: active">例：通販ビル12F 1234号室</td></tr>';
        $html .= '<tr id="tel_row"><th scope="row">電話番号</th><td colspan="2"><input name="mem_tel" id="tel" type="text" value="' . $mem_tel . '" onkeydown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive">例：06-0000-0000</td></tr>';
        $html .= '</table>';
        $html .= '<input type="hidden" id="export_estimate_pdf" name="export_estimate_pdf" value="' . $good_post_id .'" /><input class="button button-primary user_export_button p-button" type="submit" value="見積書PDF" /></form></div>';
        echo $html;
        
?>
			</div>
		</article>
<?php
	endwhile;

	if ( $active_sidebar ) :
		get_sidebar();
?>
	</div>
<?php
	endif;
endif;
?>
</main>
<script>


</script>

<?php 
get_footer(); ?>
