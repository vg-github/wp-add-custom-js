<?php
header("Content-type: application/javascript");
$custom_js = get_option('wpacc_settings2');
$custom_js = wp_kses( $custom_js['main_custom_js'], array( '\'', '\"' ) );
$custom_js = str_replace ( '&gt;' , '>' , $custom_js );
echo $custom_js;
?>