<?php
/*
Plugin Name: Ads Into Post
Plugin URI: https://b.eax.jp/wp/wp-plugin/15939/
Description:Plugin for putting Ads in H tags and P tags inside articles and top of article and bottom of article. Also supports AMP.
Author: eaxjp
Version: 1.1
Author URI: https://b.eax.jp/
License: GPLv2
Text Domain: ads-into-post-3
Domain Path: /languages
*/
add_action('admin_menu','aip3_admin_menu');
add_filter('the_content', 'aip3_main');
add_action( 'plugins_loaded', 'aip3_textdomain' );//
const AIP3_D = 'ads-into-post-3';
function aip3_textdomain() {
      load_plugin_textdomain( AIP3_D, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

//moreタグにも対応したので国際化対応で1.0--<1.1
//変更点　記事上・記事下にもたいおう
//　　　　広告挿入のバグを修正
//　　　　moreなど広告挿入コードに対応


function aip3_main($content) {
      
      //『STOP_ADS_INTO_POST』の文字列が記事内にある場合は、広告を入れない
      $aip3_tmp=strpos ( $content , "STOP_ADS_INTO_POST");
      if($aip3_tmp !== false){
            return $content;
      }

      //データの取得 Ads into post3 CORE at 20171103 
      $aip3_inser_af_byte=get_option('aip3_inser_af_byte','800');   //前後との必要なコンテンツ量(def=800byte:0-10000)
      $aip3_noads_af_pub=get_option('aip3_noads_af_pub','7');       //記事公開から広告を表示しない日数(def=0day:0-1000)
      $aip3_ssp=get_option('aip3_ssp','50');                        //タグの検索を開始するポイント、％(def=50%:0-100)
      $aip3_noads_page=get_option('aip3_noads_page','0');           //固定ページに広告を入れない(def=0)      
      $aip3_enable_inad=get_option('aip3_enable_inad','1');           //記事内広告の有効・無効(def=1)  
      $aip3_enable_topad=get_option('aip3_enable_topad','0');           //記事上広告の有効・無効(def=0)   
      $aip3_enable_btad=get_option('aip3_enable_btad','0');           //記事下広告の有効・無効(def=0)   
      $aip3_enable_adit=get_option('aip3_enable_adit','0');           //記事挿入タグ(ADS-into-HERE,more)の有効化 (def=0)   
      
      //記事内広告コード
      $aip3_ads_m=get_option('aip3_ads_m','');                      //モバイル用ADSコード
      $aip3_ads_d=get_option('aip3_ads_d','');                      //デスクトップ用ADSコード
      $aip3_ads_a=get_option('aip3_ads_a','');                      //AMP用ADSコード
      //記事上広告コード
      $aip3_ads_mt=get_option('aip3_ads_mt','');                      //モバイル用ADSコード
      $aip3_ads_dt=get_option('aip3_ads_dt','');                      //デスクトップ用ADSコード
      $aip3_ads_at=get_option('aip3_ads_at','');                      //AMP用ADSコード
      //記事下広告コード
      $aip3_ads_mb=get_option('aip3_ads_mb','');                      //モバイル用ADSコード
      $aip3_ads_db=get_option('aip3_ads_db','');                      //デスクトップ用ADSコード
      $aip3_ads_ab=get_option('aip3_ads_ab','');                      //AMP用ADSコード

      //スマホvsPC＆タブレットの振り分け(スマホの場合$aip3_mflag = 1)
      $aip3_mobile_ua = array(
      'iPhone', // iPhone
      'iPod', // iPod touch
      'Android.*Mobile', // 1.5+ Android *** Only mobile
      'Windows.*Phone', // *** Windows Phone
      'dream', // Pre 1.5 Android
      'CUPCAKE', // 1.5+ Android
      'blackberry9500', // Storm
      'blackberry9530', // Storm
      'blackberry9520', // Storm v2
      'blackberry9550', // Storm v2
      'blackberry9800', // Torch
      'webOS', // Palm Pre Experimental
      'incognito', // Other iPhone browser
      'webmate' // Other iPhone browser 
      );
      $aip3_mp = '/'.implode('|', $aip3_mobile_ua).'/i';
      if(preg_match($aip3_mp, $_SERVER['HTTP_USER_AGENT'])){
            $aip3_mflag = 1;
      }else{
            $aip3_mflag = 0;
      }

      //記事投稿日からの経過日$aip3_kijiを計算
      $aip3_today = date_i18n('U');
      $aip3_entry = get_the_time('U');
      $aip3_kiji = date('U',($aip3_today - $aip3_entry)) / 86400 ;

      //コンテンツの検索開始地点を計算
      if($aip3_ssp <= 0){$aip3_ssp = 50;}
      $aip3_i = 0;
      $aip3_content_dsize = strlen( $content );
      $aip3_center_p = $aip3_content_dsize / (100 / $aip3_ssp);

      //<hを検索し、検出位置とコンテンツ始終との間が小さい(def:800byte)場合は、aip3_iに0を代入
      $aip3_i=strpos ( $content , "<h", $aip3_center_p);
      $aip3_tmp = $aip3_content_dsize - $aip3_i;
      if($aip3_tmp < $aip3_inser_af_byte or $aip3_i < $aip3_inser_af_byte){
            $aip3_i = 0;	
      }

      //<p>を検索し、検出位置とコンテンツ始終との間が小さい(def:800byte)場合は、aip3_iに0を代入
      if($aip3_i ==0 or $aip3_i == false){ 
            $aip3_i=strpos ( $content , "<p>", $aip3_center_p);
            $aip3_tmp = $aip3_content_dsize - $aip3_i;
            if($aip3_tmp < $aip3_inser_af_byte or $aip3_i < $aip3_inser_af_byte){
                  $aip3_i = 0;
            }
      }
      
      //記事内広告にて、挿入タグを有効化する
      
      if($aip3_enable_adit == 1){
            //MOREタグ
            $aip3_tmp=strpos ($content,"<!--more-->",0);
            if($aip3_tmp !== false){
                  $aip3_tmp=strpos ( $content , ">",$aip3_tmp);
                  if($aip3_tmp !== false){
                  $aip3_i = $aip3_tmp + 2;
                  }         
            }
            //ADS_into_HERE
            $aip3_tmp=strpos ($content,"<!-- ADS_into_HERE",0);
            if($aip3_tmp !== false){
                  $aip3_tmp=strpos ( $content , ">",$aip3_tmp);
                  if($aip3_tmp !== false){
                  $aip3_i = $aip3_tmp + 1;
                  }         
            }
      }

      //公開日よりaip3_noads_af_pub日以上経過していない記事には、aip3_iに0を代入
      if($aip3_noads_af_pub > $aip3_kiji){
            $aip3_i = 0;	
      }
      
      //固定ページで、設定が有効になっている場合は、aip3_iに0を代入
      if($aip3_noads_page ==1 and is_page()){
            $aip3_i = 0;	
      }

      //記事内広告設定$aip3_enable_inadが無効=0の場合は、aip3_iに0を代入
      if($aip3_enable_inad == 0){
            $aip3_i = 0;	
      }

      //aip3_iが0でない場合、コンテンツ内に広告を入れる
      if($aip3_i > 1){  
            $aip3_i2 = $aip3_content_dsize - $aip3_i;
            $aip3_c1 = substr($content,0,$aip3_i);
            $aip3_c2 = substr($content,$aip3_i,$aip3_i2);  //挿入位置の調整20171113
            if($aip3_mflag == 1){
                  if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                        $content = $aip3_c1.$aip3_ads_a.$aip3_c2;
                  }else{
                        $content = $aip3_c1.$aip3_ads_m.$aip3_c2;
                  }	
            }else{
                  $content = $aip3_c1.$aip3_ads_d.$aip3_c2;
            }
      }





      //記事上
      if($aip3_enable_topad==1){
            $aip3_i = 1;
            if($aip3_noads_page ==1 and is_page()){
                  $aip3_i = 0;
            }
            if($aip3_noads_af_pub > $aip3_kiji){
                  $aip3_i = 0;
            }
            
            if($aip3_i == 1){
                  if($aip3_mflag == 1){
                        if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                              $content = $aip3_ads_at.$content;
                        }else{
                              $content = $aip3_ads_mt.$content;
                        }
                  }else{
                        $content = $aip3_ads_dt.$content;
                  }
            }
      }

      //記事下
      if($aip3_enable_btad==1){
            $aip3_i = 1;
            if($aip3_noads_page ==1 and is_page()){
                  $aip3_i = 0;
            }
            if($aip3_noads_af_pub > $aip3_kiji){
                  $aip3_i = 0;
            }
            
            if($aip3_i == 1){
                  if($aip3_mflag == 1){
                        if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                              $content = $content.$aip3_ads_ab;
                        }else{
                              $content = $content.$aip3_ads_mb;
                        }
                  }else{
                        $content = $content.$aip3_ads_db;
                  }
            }
      }

      return $content;
}


function aip3_admin_menu(){
      add_options_page(
      'Ads Into Post',
      'Ads Into Post',
      'administrator',
      'aip3_menu',
      'aip3_menu_setting'
      );
}






function aip3_menu_setting(){
$tab = isset($_GET['tab']) ? sanitize_key( $_GET['tab'] ) : 'general';
?>
          <style>
          td {
          padding-bottom: 8px;
          }
          </style>

      <form action="options.php" method="post">
      <?php wp_nonce_field('update-options'); ?>
	
      <div class="wrap">
      <h1>Ads Into Post</h1>
      [<a href="<?php _e('https://b.eax.jp/wp/wp-plugin/15939#en',AIP3_D) ?>"> Ads Into Post</a> <?php _e('Plugin page',AIP3_D) ?>]

      <h2 class="nav-tab-wrapper">
      <a class="nav-tab <?php if ($tab == 'general'){echo "nav-tab-active";} ?>" href="?page=aip3_menu&tab=general"><?php _e('General Configuration',AIP3_D) ?></a>
      <a class="nav-tab <?php if ($tab == 'top'){echo "nav-tab-active";} ?>" href="?page=aip3_menu&tab=top"><?php _e('Ads in top of Article',AIP3_D) ?></a>
      <a class="nav-tab <?php if ($tab == 'in'){echo "nav-tab-active";} ?>" href="?page=aip3_menu&tab=in"><?php _e('Ads inside of Article',AIP3_D) ?></a>
      <a class="nav-tab <?php if ($tab == 'bottom'){echo "nav-tab-active";} ?>" href="?page=aip3_menu&tab=bottom"><?php _e('Ads in bottom of Article',AIP3_D) ?></a>
      </h2>

      <?php if ($tab == 'general'): ?>
      <!-- 一般設定 -->
            <div>
            <table border=0>
            <tr>
            <td>
            <?php _e('Days not showing ads from post',AIP3_D) ?>
            </td>
            <td>
            <input type="number" name="aip3_noads_af_pub" min="0" max="1000" value="<?php echo get_option('aip3_noads_af_pub', '0'); ?>" style="background-color:#dcd6d9;width:80px;" />
            </td>
            <td>
            <?php _e('days',AIP3_D) ?>
            </td>
            <tr>
            <td>
            <?php _e('Do not show ads on Page(Only post)',AIP3_D) ?>
            </td>
            <td>
            <input type="checkbox" name="aip3_noads_page" value="1" <?php if(get_option('aip3_noads_page', '0') == 1){ echo 'checked="checked"'; } ?> /> 
            </td>
            </tr>
            </table>
            </div>
            <div>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="aip3_noads_af_pub,aip3_noads_page" />
            </div>
      <?php endif; ?>

      <?php if ($tab == 'top'): ?>
      <!-- 記事上広告 -->
            <h3><?php _e('Ads in top of Article',AIP3_D) ?></h3>
            <table border=0>
            <tr>
            <td>
            <?php _e('Enable',AIP3_D) ?>
            </td>
            <td>
            <input type="checkbox" name="aip3_enable_topad" value="1" <?php if(get_option('aip3_enable_topad', '0') == 1){ echo 'checked="checked"'; } ?> /> 
            </td>
            </tr>
            </table>
            <h3><?php _e('ADS code',AIP3_D) ?></h3>
            <h4><?php _e('Desktop code',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_dt" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_dt', ''); ?></textarea>
            </div> 
            <h4><?php _e('Mobile',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_mt" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_mt', ''); ?></textarea>
            </div>
            <h4><?php _e('AMP',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_at" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_at', ''); ?></textarea>
            </div>
            
            <div>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="aip3_enable_topad,aip3_ads_mt,aip3_ads_dt,aip3_ads_at" />
      <?php endif; ?>

      <?php if ($tab == 'in'): ?>
      <!-- 記事内広告 -->
            <h3><?php _e('Ads inside of Article',AIP3_D) ?></h3>
            <table border=0>
            <tr>
            <td>
            <?php _e('Enable',AIP3_D) ?>
            </td>
            <td>
            <input type="checkbox" name="aip3_enable_inad" value="1" <?php if(get_option('aip3_enable_inad', '1') == 1){ echo 'checked="checked"'; } ?>> 
            </td>
            </tr>
            <tr>
            <td>
            <?php _e('Required content amount before and after',AIP3_D) ?>
            </td>
            <td>
            <input type="number" name="aip3_inser_af_byte"  min="0" max="10000" value="<?php echo get_option('aip3_inser_af_byte', '800'); ?>" style="background-color:#dcd6d9;width:80px;" />
            </td>
            <td>
            <?php _e('byte',AIP3_D) ?>
            </td>
            </tr>
            <tr>
            <td>
            <?php _e('Start point of searching tags',AIP3_D) ?>
            </td>
            <td>
            <input type="number" name="aip3_ssp" min="0" max="100" value="<?php echo get_option('aip3_ssp', '50'); ?>" style="background-color:#dcd6d9;width:80px;" />
            </td>
            <td>
            <?php _e('%',AIP3_D) ?>
            </td>
            <tr>
            <tr>
            <td>
            <?php _e('Enable Ads insert tag',AIP3_D) ?>
            </td>
            <td>
            <input type="checkbox" name="aip3_enable_adit" value="1" <?php if(get_option('aip3_enable_adit', '0') == 1){ echo 'checked="checked"'; } ?>> 
            </td>
            </tr>
            
            </table>
            <h3><?php _e('ADS code',AIP3_D) ?></h3>
            <h4><?php _e('Desktop code',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_d" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_d', ''); ?></textarea>
            </div> 
            <h4><?php _e('Mobile',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_m" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_m', ''); ?></textarea>
            </div>
            <h4><?php _e('AMP',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_a" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_a', ''); ?></textarea>
            </div>
            
            <div>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="aip3_enable_inad,aip3_inser_af_byte,aip3_ssp,aip3_ads_m,aip3_ads_d,aip3_ads_a,aip3_enable_adit" />
            </div>

<?php endif; ?>

<?php if ($tab == 'bottom'): ?>
      <!-- 記事下広告 -->
            <h3><?php _e('Ads in bottom of Article',AIP3_D) ?></h3>
            <table border=0>
            <tr>
            <td>
            <?php _e('Enable',AIP3_D) ?>
            </td>
            <td>
            <input type="checkbox" name="aip3_enable_btad" value="1" <?php if(get_option('aip3_enable_btad', '0') == 1){ echo 'checked="checked"'; } ?>> 
            </td>
            </tr>
            </table>
            <h3><?php _e('ADS code',AIP3_D) ?></h3>
            <h4><?php _e('Desktop code',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_db" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_db', ''); ?></textarea>
            </div> 
            <h4><?php _e('Mobile',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_mb" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_mb', ''); ?></textarea>
            </div>
            <h4><?php _e('AMP',AIP3_D) ?></h4>
            <div>
            <textarea name="aip3_ads_ab" cols=90 rows=8 style="background-color:#dcd6d9;"><?php echo get_option('aip3_ads_ab', ''); ?></textarea>
            </div>
            
            <div>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="aip3_enable_btad,aip3_ads_mb,aip3_ads_db,aip3_ads_ab" />
      </div>
<?php endif; ?>  

<?php if ($tab == 'clear_options_aip3'): ?>
    <?php 
      delete_option('aip3_inser_af_byte');
      delete_option('aip3_noads_af_pub');
      delete_option('aip3_ssp');
      delete_option('aip3_noads_page');
      delete_option('aip3_enable_inad');
      delete_option('aip3_enable_topad');
      delete_option('aip3_enable_btad');
      delete_option('aip3_ads_m');
      delete_option('aip3_ads_d');
      delete_option('aip3_ads_a');
      delete_option('aip3_ads_mt');
      delete_option('aip3_ads_dt');
      delete_option('aip3_ads_at');
      delete_option('aip3_ads_mb');
      delete_option('aip3_ads_db');
      delete_option('aip3_ads_ab');    
    ?>
            <div>
     cleared
      </div>
<?php endif; ?>  


      
   

<?php } ?>
