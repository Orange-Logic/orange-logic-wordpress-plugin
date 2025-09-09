<?php
/**
 * Plugin Name:       OrangeDAM GAB Connector
 * Plugin URI:        https://marketplace.orangelogic.com/partners/wordpress
 * Description:       Adds an Orange Logic Assets tab in the media library and a settings page to configure the OrangeDAM site URL and Generic Asset Browser version.
 * Version:           1.1.0
 * Author:            Orange Logic
 * Author URI:        https://www.orangelogic.com/
 * License:           
 * License URI:       
 * Text Domain:       gab-connector
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add settings menu to admin menu
 *
 * @return void
 */
function gab_connector_add_menu() {
    add_menu_page(
        __( 'OrangeDAM Connector Settings', 'gab-connector' ),
        __( 'OrangeDAM Connector', 'gab-connector' ),
        'manage_options',
        'gab-connector',
        'gab_connector_settings_page',
        'dashicons-admin-generic',
        25
    );
}
add_action( 'admin_menu', 'gab_connector_add_menu' );

/**
 * Add Gab Media submenu under Media
 *
 * @return void
 */
function gab_connector_add_media_submenu() {
    add_submenu_page(
        'upload.php',
        __( 'OrangeDAM Media', 'gab-connector' ),
        __( 'OrangeDAM Media', 'gab-connector' ),
        'manage_options',
        'gab-media',
        'gab_media_page_callback'
    );
}
add_action( 'admin_menu', 'gab_connector_add_media_submenu' );

/**
 * Render Gab Media page content
 *
 * @return void
 */
function gab_media_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'OrangeDAM Media', 'gab-connector' ); ?></h1>
        <div id="gab_browser_container" style="display: flex; align-items: center; height: 80vh; justify-content: center; width: 100%;"></div>
    </div>
    <?php
}

/**
 * Register plugin settings
 *
 * @return void
 */
function gab_connector_register_settings() {
    register_setting( 'gab_connector_settings_group', 'gab_orangedam_url', 'sanitize_text_field' );
    register_setting( 'gab_connector_settings_group', 'gab_orangedam_script', 'sanitize_text_field' );
    register_setting( 'gab_connector_settings_group', 'gab_orangedam_style', 'sanitize_text_field' );
}
add_action( 'admin_init', 'gab_connector_register_settings' );

/**
 * Render settings page
 *
 * @return void
 */
function gab_connector_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'GAB Connector Settings', 'gab-connector' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'gab_connector_settings_group' );
            do_settings_sections( 'gab_connector_settings_group' );
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gab_orangedam_url"><?php esc_html_e( 'OrangeDAM URL', 'gab-connector' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="gab_orangedam_url" 
                               id="gab_orangedam_url" 
                               value="<?php echo esc_attr( get_option( 'gab_orangedam_url', '' ) ); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gab_orangedam_script"><?php esc_html_e( 'JavaScript URL', 'gab-connector' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="gab_orangedam_script" 
                               id="gab_orangedam_script" 
                               value="<?php echo esc_attr( get_option( 'gab_orangedam_script', '' ) ); ?>" 
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gab_orangedam_style"><?php esc_html_e( 'Stylesheet URL', 'gab-connector' ); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               name="gab_orangedam_style" 
                               id="gab_orangedam_style" 
                               value="<?php echo esc_attr( get_option( 'gab_orangedam_style', '' ) ); ?>" 
                               class="regular-text">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Add custom media tab
 *
 * @param array $tabs Existing media tabs
 * @return array Modified tabs array
 */
function gab_connector_add_media_tab( $tabs ) {
    $tabs['orange_logic_assets'] = __( 'Orange Logic Assets', 'gab-connector' );
    return $tabs;
}
add_filter( 'media_upload_tabs', 'gab_connector_add_media_tab' );

/**
 * Render media tab content
 *
 * @return void
 */
function gab_connector_media_tab_content() {
    $dam_url    = esc_url( get_option( 'gab_orangedam_url', '' ) );
    $gab_script = esc_url( get_option( 'gab_orangedam_script', '' ) );
    $gab_style  = esc_url( get_option( 'gab_orangedam_style', '' ) );

    if ( $gab_script ) {
        wp_enqueue_script( 'gab-connector-script', $gab_script, array(), null, true );
    }
    if ( $gab_style ) {
        wp_enqueue_style( 'gab-connector-style', $gab_style, array(), null );
    }
    ?>
    <div id="gab_browser_container" style="display: flex; align-items: center; height: 100vh; justify-content: center; width: 100vw;"></div>
    <script>
        jQuery(document).ready(function($) {
            OrangeDAMContentBrowser.open({
                onAssetSelected: (assets) => {
                    console.log('onAssetSelected: ',assets);
                },
                onError: function(errorMessage, error) {
                    console.error(errorMessage, error);
                },
                multiSelect: true,
                containerId: "gab_browser_container",
                baseUrl: <?php echo wp_json_encode( $dam_url ); ?>,
                onlyIIIFPrefix: true,
                displayInfo: {
                    title: true,
                    dimension: true,
                    fileSize: true,
                    tags: true
                }
            });
        });
    </script>
    <?php
}

/**
 * Handle media tab display
 *
 * @return void
 */
function gab_connector_media_tab_handler() {
    wp_iframe( 'gab_connector_media_tab_content' );
}
add_action( 'media_upload_orange_logic_assets', 'gab_connector_media_tab_handler' );

/**
 * Enqueue scripts and styles
 *
 * @param string $hook Current admin page hook
 * @return void
 */
function gab_connector_enqueue_media_library_scripts( $hook ) {
    if ( in_array( $hook, array( 'upload.php', 'media_page_gab-media' ), true ) || did_action( 'wp_enqueue_media' ) ) {
        $dam_url    = esc_url( get_option( 'gab_orangedam_url', '' ) );
        $gab_script = esc_url( get_option( 'gab_orangedam_script', '' ) );
        $gab_style  = esc_url( get_option( 'gab_orangedam_style', '' ) );

        wp_enqueue_script(
            'gab-connector-main-js',
            $gab_script,
            array( 'media-views', 'jquery' ),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'gab-connector-main-css',
            $gab_style,
            array(),
            '1.0.0'
        );

        wp_enqueue_style(
            'gab-custom-css',
            plugin_dir_url( __FILE__ ) . 'assets/css/gab-custom.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'gab-connector-media-library',
            plugin_dir_url( __FILE__ ) . 'assets/js/gab-media-library.js',
            array( 'jquery', 'media-views' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'gab-connector-media-library',
            'gabConnector',
            array(
                'siteUrl'       => esc_url_raw( get_site_url() ),
                'ajaxUrl'       => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
                'orangedam_url' => $dam_url,
                'nonce'         => wp_create_nonce( 'gab_connector_nonce' )
            )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'gab_connector_enqueue_media_library_scripts' );

/**
 * Handle image download via AJAX
 *
 * @return void
 */
function gab_connector_download_image() {
    check_ajax_referer( 'gab_connector_nonce', 'nonce' );

    $image_url  = esc_url_raw( $_POST['image_url'] ?? '' );
    $image_id   = sanitize_text_field( $_POST['image_id'] ?? '' );
    $image_data = isset( $_POST['image_data'] ) ? (array) $_POST['image_data'] : array();

    if ( empty( $image_url ) || empty( $image_id ) ) {
        wp_send_json_error( __( 'Invalid data.', 'gab-connector' ) );
    }

    $image_title   = ! empty( $image_data['image_title'] ) ? sanitize_text_field( $image_data['image_title'] ) : '';
    $image_caption = ! empty( $image_data['image_caption'] ) ? sanitize_textarea_field( $image_data['image_caption'] ) : '';
    $image_alt     = ! empty( $image_data['image_alt'] ) ? sanitize_text_field( $image_data['image_alt'] ) : '';

    $attachment_id = gab_connector_get_attachment_id_by_image_id( $image_id );

    if ( $attachment_id ) {
        $update_data = array( 'ID' => $attachment_id );
        if ( $image_title ) {
            $update_data['post_title'] = $image_title;
        }
        if ( $image_caption ) {
            $update_data['post_excerpt'] = $image_caption;
        }
        if ( ! empty( $update_data ) ) {
            wp_update_post( $update_data );
        }
        if ( $image_alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );
        }
        wp_send_json_success( array( 'attachment_id' => $attachment_id ) );
    }

    $upload_dir = wp_upload_dir();
    $extension  = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg';
    $filename   = sanitize_file_name( $image_id . '.' . $extension );

    $image_content = file_get_contents( $image_url );
    if ( false === $image_content ) {
        wp_send_json_error( __( 'Failed to download image.', 'gab-connector' ) );
    }

    $file_path = $upload_dir['path'] . '/' . $filename;
    if ( false === file_put_contents( $file_path, $image_content ) ) {
        wp_send_json_error( __( 'Failed to save image.', 'gab-connector' ) );
    }

    $filetype    = wp_check_filetype( $filename );
    $attachment  = array(
        'post_title'     => $image_title ?: sanitize_file_name( $image_id ),
        'post_excerpt'   => $image_caption,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_mime_type' => $filetype['type'],
        'guid'           => $upload_dir['url'] . '/' . $filename,
    );

    $attachment_id = wp_insert_attachment( $attachment, $file_path );
    if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
        wp_send_json_error( __( 'Failed to insert attachment.', 'gab-connector' ) );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
    wp_update_attachment_metadata( $attachment_id, $attach_data );

    if ( $image_alt ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );
    }
    update_post_meta( $attachment_id, '_orange_logic_image_id', $image_id );

    wp_send_json_success( array( 'attachment_id' => $attachment_id ) );
}
add_action( 'wp_ajax_gab_download_image', 'gab_connector_download_image' );

/**
 * Handle featured image setting via AJAX
 *
 * @return void
 */
function gab_connector_set_featured_image() {
    check_ajax_referer( 'gab_connector_nonce', 'nonce' );

    $post_id    = absint( $_POST['post_id'] ?? 0 );
    $image_url  = esc_url_raw( $_POST['image_url'] ?? '' );
    $image_id   = sanitize_text_field( $_POST['image_id'] ?? '' );
    $image_data = isset( $_POST['image_data'] ) ? (array) $_POST['image_data'] : array();

    if ( ! $post_id || ! $image_url || ! $image_id ) {
        wp_send_json_error( __( 'Invalid data.', 'gab-connector' ) );
    }

    $image_title   = ! empty( $image_data['image_title'] ) ? sanitize_text_field( $image_data['image_title'] ) : '';
    $image_caption = ! empty( $image_data['image_caption'] ) ? sanitize_textarea_field( $image_data['image_caption'] ) : '';
    $image_alt     = ! empty( $image_data['image_alt'] ) ? sanitize_text_field( $image_data['image_alt'] ) : '';

    $attachment_id = gab_connector_get_attachment_id_by_image_id( $image_id );

    $upload_dir = wp_upload_dir();
    $extension  = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg';
    $filename   = sanitize_file_name( $image_id . '.' . $extension );

    if ( $attachment_id ) {
        $update_data = array( 'ID' => $attachment_id );
        if ( $image_title ) {
            $update_data['post_title'] = $image_title;
        }
        if ( $image_caption ) {
            $update_data['post_excerpt'] = $image_caption;
        }
        if ( ! empty( $update_data ) ) {
            wp_update_post( $update_data );
        }
        if ( $image_alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );
        }
        set_post_thumbnail( $post_id, $attachment_id );
    } else {
        $image_content = file_get_contents( $image_url );
        if ( false === $image_content ) {
            wp_send_json_error( __( 'Failed to download image.', 'gab-connector' ) );
        }

        $file_path = $upload_dir['path'] . '/' . $filename;
        if ( false === file_put_contents( $file_path, $image_content ) ) {
            wp_send_json_error( __( 'Failed to save image.', 'gab-connector' ) );
        }

        $filetype    = wp_check_filetype( $filename );
        $attachment  = array(
            'post_title'     => $image_title ?: sanitize_file_name( $image_id ),
            'post_excerpt'   => $image_caption,
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_mime_type' => $filetype['type'],
            'guid'           => $upload_dir['url'] . '/' . $filename,
        );

        $attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );
        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            wp_send_json_error( __( 'Failed to insert attachment.', 'gab-connector' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        if ( $image_alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );
        }
        update_post_meta( $attachment_id, '_orange_logic_image_id', $image_id );
        set_post_thumbnail( $post_id, $attachment_id );
    }

    update_post_meta( $post_id, '_orange_logic_attachment_id', $attachment_id );
    update_post_meta( $post_id, '_orange_logic_image', $image_url );
    update_post_meta( $post_id, '_orange_logic_img_id', $image_id );

    wp_send_json_success( array( 'attachment_id' => $attachment_id ) );
}
add_action( 'wp_ajax_set_orange_logic_featured_image', 'gab_connector_set_featured_image' );

/**
 * Get attachment ID by Orange Logic image ID
 *
 * @param string $image_id Orange Logic image ID
 * @return int|null Attachment ID or null if not found
 */
function gab_connector_get_attachment_id_by_image_id( $image_id ) {
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT post_id 
         FROM {$wpdb->postmeta}
         WHERE meta_key = '_orange_logic_image_id'
         AND meta_value = %s
         LIMIT 1",
        $image_id
    );
    return $wpdb->get_var( $query );
}
?>