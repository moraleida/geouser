<?php
/*
Plugin Name: GeoUser
Plugin URI: http://github.com/WP-Brasil/geouser
Description: Add georeference information fields to user profile. Made to be used with the <a href="#">map theme</a>.
Author: Ricardo Moraleida, Vinicius Massuchetto
Version: 1.0
Author URI: http://github.com/WP-Brasil/geouser
Text Domain: geouser
*/

 if ( !defined( 'GEOUSER_INITIAL_LAT' ) || !GEOUSER_INITIAL_LAT
    || !defined( 'GEOUSER_INITIAL_LNG' ) || !GEOUSER_INITIAL_LNG ) {
    // Brazil
    define( 'GEOUSER_INITIAL_LAT', -15 );
    define( 'GEOUSER_INITIAL_LNG', -55 );
}

add_action( 'admin_enqueue_scripts', 'geouser_scripts' );
add_action( 'add_meta_boxes', 'geouser_add_metaboxes' );
add_action( 'admin_menu', 'geouser_add_options' );
add_action( 'plugins_loaded', 'geouser_localization' );

//add_action( 'show_user_profile', 'geouser_fields' );
//add_action( 'edit_user_profile', 'geouser_fields' );
//add_action( 'personal_options_update', 'geouser_save' );
//add_action( 'edit_user_profile_update', 'geouser_save' );

function geouser_localization() {

    load_plugin_textdomain( 'geouser', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

function geouser_scripts() {

    global $pagenow;

    if ( !in_array( $pagenow, array( 'profile.php', 'user-edit.php', 'post-new.php' ) ) )
        return false;

    wp_enqueue_script( 'google-maps-v3', 'http://maps.google.com/maps/api/js?sensor=false' );
    wp_enqueue_script( 'geouser', plugins_url( '/geouser.js', __FILE__ ) );

    $params = array(
        'initial_lat' => GEOUSER_INITIAL_LAT,
        'initial_lng' => GEOUSER_INITIAL_LNG,
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    );

    wp_localize_script( 'geouser', 'geouser', $params );


}
/*
function geouser_fields( $user ) {
    $location = array( 'lat' => false, 'lng' => false );
    if ( $loc = get_user_meta( $user->ID, 'location', true ) ) {
        $location['lat'] = $loc[0];
        $location['lng'] = $loc[1];
    }
    ?>
    <h3><?php _e( 'Geolocalization', 'geouser' ); ?></h3>
    <table class="form-table">
    <tr>
    <th><label for="address"><?php _e( 'Pin your location in the map', 'geouser' ); ?></label></th>
    <td>
    <p><?php _e( 'Search address', 'geouser' ); ?>:&nbsp;<input type="text" id="geouser-search" class="regular-text" /></p>
    <div id="geouser-map" style="display:block; width:500px; height: 300px; border: 1px solid #DFDFDF;"></div>
    <input type="hidden" id="geouser-lat" name="lat" value="<?php echo $location['lat']; ?>" />
    <input type="hidden" id="geouser-lng" name="lng" value="<?php echo $location['lng']; ?>" />
    <p class="help"><?php printf( __( 'Map theme will use %s information for your baloon.<br/>Update your %s account to show a full profile.', 'geouser' ), '<a href="http://gravatar.com">Gravatar</a>', '<b>' . $user->user_email . '</b>' ); ?></p>
    </td>
    </tr>
    </table>
<?php }

function geouser_save( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    if ( !empty( $_POST['lat'] ) && floatval( $_POST['lat'] )
        && !empty( $_POST['lng'] ) && floatval( $_POST['lng'] ) ) {
        update_user_meta( $user_id, 'location', array( $_POST['lat'], $_POST['lng'] ) );
    }

}
*/
function geouser_add_options() {

    add_management_page( 'Geouser', __('Geouser options', 'geouser'), 'edit_posts', 'geouser', 'geouser_options_page' );

}

function geouser_options_page() {

    if($_POST['geouser_types'])
        $u = update_option( 'geouser_types', $_POST['geouser_types'] );

    $types = get_option( 'geouser_types', array('post') );

    echo '<h1>'. __('Geouser options', 'geouser') .'</h1>';
    echo '<p>'. __('Select which post-types should use the Geouser metabox', 'geouser') .'</p>';

    if($u)
        echo '<h4>'. __('Options saved successfully', 'geouser') .'</h4>';

    $post_types = get_post_types(array('public' => true)); 

    echo '<form action="" method="post">';

    foreach ( $post_types as $post_type ) {

        if($post_type != "attachment") {

            if(in_array($post_type, $types)) {
                $checked = 'checked';
            } else {
                $checked = '';
            }
            echo '<input type="checkbox" value="'.$post_type.'" name="geouser_types[]" '.$checked.'/> '.__($post_type).'<br />';    
        }
        

    }

    echo '<p><input type="submit" value="'.__('Save options', 'geouser').'" /></p></form>';


}

function geouser_add_metaboxes() {
    $types = get_option( 'geouser_types', array('post') );

    foreach($types as $t) {
        add_meta_box( 'geouser', __( 'Location', 'geouser' ), 'geouser_post_metabox', $t );
    }
    
}

function geouser_post_metabox() {
    get_currentuserinfo();

    $location = array( 'lat' => false, 'lng' => false );
    if ( $loc = get_user_meta( $current_user->ID, 'geouser-location', true ) ) {
        $location['lat'] = $loc[0];
        $location['lng'] = $loc[1];
    }
    ?>
    <label for="address"><?php _e( 'Pin your location in the map', 'geouser' ); ?></label>
    <?php /*
    <table id="geouser-localization">
        <tr>
            <td><?php _e( 'Address', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-route" class="regular-text" /></td>
        </tr>
        <tr>
            <td><?php _e( 'Number', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-street_number" class="regular-text" /></td>
        </tr>        
        <tr>
            <td><?php _e( 'Neighboorhood', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-neighborhood" class="regular-text" /></td>
        </tr>
        */ ?>
        <tr>
            <td><?php _e( 'Locality', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-administrative_area_level_2" class="regular-text" /></td>
        </tr>
        <tr>
            <td><?php _e( 'State', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-administrative_area_level_1" class="regular-text" /></td>
        </tr>
        <?php /*
        <tr>
            <td><?php _e( 'Country', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-country" class="regular-text" /></td>
        </tr>
        <tr>
            <td><?php _e( 'Postal Code', 'geouser' ); ?>:</td>
            <td><input type="text" id="geouser-search-postal_code" class="regular-text" /></td>
        </tr>            
    </table>
    */ ?>
    <div id="geouser-map" style="display:block; width:100%; height: 300px; border: 1px solid #DFDFDF;"></div>
    <?php /* _e( 'Latitude', 'geouser' ); ?>:&nbsp;<input type="text" id="shandora_listing_maplatitude" name="lat" value="<?php echo $location['lat']; ?>" />
    <?php  _e( 'Longitude', 'geouser' ); ?>:&nbsp;<input type="text" id="shandora_listing_maplongitude" name="lng" value="<?php echo $location['lng']; ?>" /> */ ?>
    <p><input type="text" id="geouser-search" class="regular-text" placeholder="<?php _e( 'Search address', 'geouser' ); ?>" /></p>
<?php }

add_action( 'wp_ajax_ecotemporadas_register_taxonomy', 'ecotemporadas_register_taxonomy' );
function ecotemporadas_register_taxonomy() {

    $uf = $_POST['uf'];
    $city = $_POST['city'];

    if($uf && $city) {

        $termUF = get_term_by( 'name', $uf, 'property-location' );
        $termCity = wp_insert_term( $city, 'property-location', array( 'parent' => $termUF->term_id ) );

        if(!is_wp_error( $termCity )) {
            $response = array('status' => 'ok', 'term_id' => $termCity['term_id'], 'name' => $city );
        } else {
            $response = array('status' => 'erro', 'msg' =>  $termCity->get_error_message() );
        }

    } else {
        $response = array('status' => 'erro', 'msg' => 'Dados incompletos');
    }

    header( "Content-Type: application/json" );
    echo json_encode($response);
    exit;
}