<?php
/**
 * Plugin Name: WP API Get User by Email/Username
 * Plugin URI: https://github.com/freebeans/wp-api-get-user-by-email-username
 * Description: Permite obter um usuário via API à partir do e-mail ou username
 * Version: 0.1.0
 * Author URI:
 * License: MIT
 */

 /* This plugin was based on code by dest81 at https://github.com/dest81/wp-api-get-user-by-username */

function custom_plugin_prepare_user( $user, $context = 'view' ) {
    $user_fields = array(
        'ID'          => $user->ID,
        'username'    => $user->user_login,
        'name'        => $user->display_name,
        'first_name'  => $user->first_name,
        'last_name'   => $user->last_name,
        'nickname'    => $user->nickname,
        'slug'        => $user->user_nicename,
        'URL'         => $user->user_url,
        'description' => $user->description,
    );

    $user_fields['registered'] = date( 'c', strtotime( $user->user_registered ) );

    if ( $context === 'view' || $context === 'edit' ) {
        $user_fields['roles']        = $user->roles;
        $user_fields['capabilities'] = $user->allcaps;
        $user_fields['email']        = false;
    }

    if ( $context === 'edit' ) {
        // The user's specific caps should only be needed if you're editing
        // the user, as allcaps should handle most uses
        $user_fields['email']              = $user->user_email;
        $user_fields['extra_capabilities'] = $user->caps;
    }

    return apply_filters( 'json_prepare_user', $user_fields, $user, $context );
}


function custom_plugin_get_user_by_login( $request_data ) {
    $login = (string) $request_data['login'];
    $current_user_id = get_current_user_id();

    if ( $current_user_id !== $id && ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'json_user_cannot_list', __( 'Sorry, you are not allowed to view this user.' ), array( 'status' => 403 ) );
    }

    $user = get_user_by( 'login', $login );

    if ( empty( $user->ID ) ) {
        //return new WP_Error( 'json_user_invalid_id', __( 'Invalid username.' ), array( 'status' => 400 ) );

        return [];
    }

    return custom_plugin_prepare_user( $user, $context );
}


function custom_plugin_get_user_by_email( $request_data ) {
    $email = (string) $request_data['email'];
    $current_user_id = get_current_user_id();

    if ( $current_user_id !== $id && ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'json_user_cannot_list', __( 'Sorry, you are not allowed to view this user.' ), array( 'status' => 403 ) );
    }

    $user = get_user_by( 'email', $email );

    if ( empty( $user->ID ) ) {
        //return new WP_Error( 'json_user_invalid_id', __( 'Invalid username.' ), array( 'status' => 400 ) );

        return [];
    }

    return custom_plugin_prepare_user( $user, $context );
}


add_action( 'rest_api_init', function () {
    register_rest_route( 'getuser/v1', '/users/user/(?P<login>[0-9a-zA-Z_-]+)', array(
        'methods' => 'GET',
        'callback' => 'custom_plugin_get_user_by_login'
        ) );
    
    register_rest_route( 'getuser/v1', '/users/email/(?P<email>.+)', array(
        'methods' => 'GET',
        'callback' => 'custom_plugin_get_user_by_email'
        ) );
} );