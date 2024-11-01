<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
function SAFW_recursive_sanitize_text_field($array) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = SAFW_recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}
$message = '';
// print_r(__FILE__);
// echo "<br>";
// print_r(realpath(dirname(__FILE__) . '/..'));
// print_r(plugin_dir_url(dirname(__FILE__)));
// die();
// JS
wp_register_script('prefix_bootstrap', plugin_dir_url(dirname(__FILE__)).'/js/bootstrap.min.js');
wp_enqueue_script('prefix_bootstrap');

// CSS
wp_register_style('prefix_bootstrap', plugin_dir_url(dirname(__FILE__)).'/css/bootstrap.min.css');
wp_enqueue_style('prefix_bootstrap');

global $woocommerce;

$optn = '';
$methods = $woocommerce->shipping->get_shipping_methods();
$general_settings = get_option('shipment_addon_main_settings');
// echo "<pre>";
// print_r($general_settings);
// die();

foreach ($methods as $method => $val) {
    $optn .= "<option value=" . $method . ">" . $val->method_title . "</option>";
}

if (isset($_POST['shipment_addon_trk_num'])) {
    $general_settings = array();
    $general_settings['shipment_addon_track_url'] = SAFW_recursive_sanitize_text_field($_POST['shipment_addon_trk_num']);
    
    if(isset($_POST['shipment_addon_added_carr'])){
        $general_settings['shipment_addon_added_carr'] = SAFW_recursive_sanitize_text_field($_POST['shipment_addon_added_carr']);
        $general_settings['shipment_addon_added_track_url'] = SAFW_recursive_sanitize_text_field($_POST['shipment_addon_added_trk_url']);
    }

    if (update_option('shipment_addon_main_settings', $general_settings)) {
        $message = 'Settings Saved Successfully.';
    }
    // echo '<pre>';
    // print_r($general_settings);
// die();
    
}
$general_settings = get_option('shipment_addon_main_settings');
// print_r($_POST);
// die();
?>

<h3>Configuration Page</h3>

<form method="post">
    <div class="container" style="margin-top: 40px;">
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-sm-1">
                <h4> No. </h4>
            </div>
            <div class="col-sm-2">
                <h4> Carrier </h4>
            </div>
            <div class="col-sm-4">
                <h4> Tracking URL </h4>
            </div>
            <div class="col-sm-5">

            </div>
        </div>
        <?php
        $count = 1;
        $added_carr = '';
        if(isset($general_settings['shipment_addon_added_carr']) && $general_settings['shipment_addon_added_carr'] != ''){
                $carrier_count = sizeof($methods);
                $added_carr = $general_settings['shipment_addon_added_carr'];
               
        }
        foreach ($methods as $method => $val) {
            $trk_value = '';
            if (isset($general_settings['shipment_addon_track_url'][$method]) && $general_settings['shipment_addon_track_url'][$method] != '') {
                $trk_value = $general_settings['shipment_addon_track_url'][$method];
            }
            // print_r($trk_value);
            // die();
            echo '<div class="row form-group apnd">
        <div class="col-sm-1 count">' .
                $count .
                '.</div>
        <div class="col-sm-2">' .
                $val->method_title
                . '</div>
        <div class="col-sm-4">
            <div>
                <input class="form-control" type="text" name="shipment_addon_trk_num[' . $method . ']" value="' . $trk_value . '" placeholder="https://www.sampletrack.com/?track=@">
            </div>

        </div>
        <div class="col-sm-5">

        </div>
    </div>';
            $count++;
        }
        if(!empty($added_carr)){
            foreach($added_carr as $k=>$car_name){
                // echo "SSS";
                // print_r($general_settings['shipment_addon_added_track_url']);
                // die();
            echo '<div class="row form-group apnd added">
            <div class="col-sm-1 count">' .
                    ($carrier_count+1) .
                    '.</div>
            <div class="col-sm-2"><input type="hidden" name="shipment_addon_added_carr[]" value="'.$car_name.'">' .
                    $car_name
                    . '</div>
            <div class="col-sm-4">
                <div>
                    <input class="form-control" type="text" name="shipment_addon_added_trk_url[]" value="' . $general_settings['shipment_addon_added_track_url'][$k] . '" placeholder="https://www.sampletrack.com/?track=@">
                </div>
    
            </div>
            <div class="col-sm-5">
    
            </div>
        </div>';
        $carrier_count++;
                    }
        }
        ?>
        <div class="row" style="text-align: center;margin-top:30px;">
            <div class="col-sm-3">

            </div>
            <div class="col-sm-2" style="">
                <!-- <i class="dashicons-before dashicons-plus-alt"> Add</i> -->
                <button id="ship_add_carr" class="dashicons-before dashicons-plus-alt btn btn-sm btn-secondary">Add</button>
                <button id="ship_rmv_carr" class="dashicons-before dashicons-minus btn btn-sm btn-secondary" style="margin-left: 4px;">Remove</button>
            </div>

        </div>
        <div class="row" style="text-align: center;">
            <div class="col-sm-7">
                <p class="primary" style="margin: 35px 35px 35px 62px;border: 1px solid #fff582;max-width:500px;padding:5px;background-color:#fff582"><strong>Note!</strong> Please replace with @ in place of tracking numbers for perfect redirections.</p>
                <input type="submit" class="btn btn-primary form-control">
            </div>
        </div>
        <?php

        if (isset($message) && $message != '') {
            echo '<div class="row" style="text-align: center;">
            <div class="col-sm-7">
                <p class="" style="margin: 35px 35px 35px 62px;border: 1px solid #34e169;max-width:500px;padding:5px;background-color:#34e169">' . $message . '</p>
            </div>
        </div>';
        }
        ?>
    </div>
</form>

<script>
    jQuery("#ship_add_carr").click(function() {
        var count = parseInt(jQuery('.count').length) + 1;
        var apnd = '<div class="row form-group apnd added">' +
            '<div class="col-sm-1 count">' +
            count + '.</div><div class="col-sm-2">' +
            '<input type="text" name="shipment_addon_added_carr[]" class="form-control carr">' +
            '</div><div class="col-sm-4"><div>' +
            '<input class="form-control apnd-url" type="text" name="shipment_addon_added_trk_url[]" value="" placeholder="https://www.sampletrack.com/?track=@" required></div>' +
            '</div><div class="col-sm-5"></div></div>';
        // console.log();
        jQuery(apnd).insertAfter(jQuery('.apnd').last());
        console.log(jQuery("#ship_add_carr"));
        return false;
    });
   
// jQuery("input.carr:text").change(function(){
//     alert("SS");
//     console.log("SSS");
// });

    
    // jQuery( '.carr' ).keypress(function() {
    //     var inputs = jQuery(".carr");
    // for(var i = 0; i < inputs.length; i++){
    // alert(jQuery(inputs[i]).val());
    // }
    // });

    jQuery("#ship_rmv_carr").click(function() {
        (jQuery( ".added" ).last()).remove();
        return false;
    });
</script>