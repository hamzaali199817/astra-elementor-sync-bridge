<?php
/**
 * Plugin Name: Astra & Elementor Sync Bridge
 * Plugin URI: https://alihamza.work/astra-elementor-sync/
 * Description: A simple and effective plugin to synchronize your Astra Theme settings with Elementor Global Styles.
 * Version: 40.0.0
 * Author: Ali Hamza
 * Author URI: https://alihamza.work
 * Text Domain: astra-elementor-sync
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main plugin class.
 */
class AHElementorAstraSync {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_post_ah_sync_colors', [ $this, 'handle_colors_sync_action' ] );
        add_action( 'admin_post_ah_sync_typography', [ $this, 'handle_typography_sync_action' ] );
        add_action( 'wp_head', [ $this, 'inject_sync_styles' ], 999 );
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
        add_options_page(
            'Astra & Elementor Sync',
            'Astra & Elementor Sync',
            'manage_options',
            'astra-elementor-sync',
            [ $this, 'create_admin_page' ]
        );
    }

    /**
     * Create the admin page.
     */
    public function create_admin_page() {
        $ah_sync_status = isset( $_GET['ah_sync_status'] ) ? sanitize_key( wp_unslash( $_GET['ah_sync_status'] ) ) : '';
        $ah_sync_nonce  = isset( $_GET['ah_sync_nonce'] ) ? sanitize_key( wp_unslash( $_GET['ah_sync_nonce'] ) ) : '';
        $is_valid_nonce = wp_verify_nonce( $ah_sync_nonce, 'ah_sync_action' );

        ?>
        <div class="wrap">
            <h2>Astra & Elementor Sync</h2>
            <p>Select which Elementor Global Styles you wish to synchronize with your Astra Theme settings.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
                <h3>Colors</h3>
                <input type="hidden" name="action" value="ah_sync_colors">
                <?php wp_nonce_field( 'ah_sync_colors_nonce_action', 'ah_sync_colors_nonce' ); ?>
                <?php submit_button( 'Sync Colors', 'primary', 'submit', false ); ?>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-left: 20px;">
                <h3>Typography</h3>
                <input type="hidden" name="action" value="ah_sync_typography">
                <?php wp_nonce_field( 'ah_sync_typography_nonce_action', 'ah_sync_typography_nonce' ); ?>
                <?php submit_button( 'Sync Typography', 'secondary', 'submit', false ); ?>
            </form>
            <?php
            if ( $is_valid_nonce && 'success' === $ah_sync_status ) {
                $message = 'Synchronization successful! You can check your Elementor Global Styles.';
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            } elseif ( $is_valid_nonce && 'fail' === $ah_sync_status ) {
                $error_message = 'Synchronization failed. Please ensure Elementor and Astra are active and have a Global Color Palette and Typography configured.';
                if ( isset( $_GET['error_code'] ) ) {
                    $error_code = sanitize_key( wp_unslash( $_GET['error_code'] ) );
                    switch ( $error_code ) {
                        case 'elementor_not_active':
                            $error_message = 'Synchronization failed. Elementor is not active.';
                            break;
                        case 'astra_not_active':
                            $error_message = 'Synchronization failed. Astra theme is not active.';
                            break;
                        case 'kit_not_found':
                            $error_message = 'Synchronization failed. Elementor\'s active kit could not be found.';
                            break;
                    }
                }
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Handle the colors synchronization.
     */
    public function handle_colors_sync_action() {
        check_admin_referer( 'ah_sync_colors_nonce_action', 'ah_sync_colors_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to perform this action.' );
        }

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'elementor_not_active',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        if ( ! function_exists( 'astra_get_option' ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'astra_not_active',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        $kit_manager = \Elementor\Plugin::instance()->kits_manager;
        $kit_document = $kit_manager->get_active_kit();

        if ( ! $kit_document ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'kit_not_found',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }
        
        // Use Astra's dedicated function to get options reliably.
        $heading_base_color = astra_get_option( 'heading-base-color' );
        $link_color = astra_get_option( 'link-color' );
        $text_color = astra_get_option( 'text-color' );
        $accent_color = astra_get_option( 'theme-color' );

        $elementor_data_to_sync = [];

        // Updated color mapping based on the user's specific request and correct Astra keys.
        $elementor_data_to_sync['system_colors'] = [
            [ '_id' => 'primary', 'title' => 'Primary', 'name' => 'Primary', 'color' => $heading_base_color ?? '#000000' ],
            [ '_id' => 'secondary', 'title' => 'Secondary', 'name' => 'Secondary', 'color' => $link_color ?? '#000000' ],
            [ '_id' => 'text', 'title' => 'Text', 'name' => 'Text', 'color' => $text_color ?? '#000000' ],
            [ '_id' => 'accent', 'title' => 'Accent', 'name' => 'Accent', 'color' => $accent_color ?? '#000000' ],
        ];


        if ( ! empty( $elementor_data_to_sync ) ) {
            $kit_document->update_settings( $elementor_data_to_sync );
        }

        \Elementor\Plugin::instance()->files_manager->clear_cache();

        $redirect_args = array(
            'ah_sync_status' => 'success',
            'sync_type'      => 'colors',
            'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
        );
        wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
        exit;
    }

    /**
     * Handle the typography synchronization.
     */
    public function handle_typography_sync_action() {
        check_admin_referer( 'ah_sync_typography_nonce_action', 'ah_sync_typography_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to perform this action.' );
        }

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'elementor_not_active',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        if ( ! function_exists( 'astra_get_option' ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'astra_not_active',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        $kit_manager = \Elementor\Plugin::instance()->kits_manager;
        $kit_document = $kit_manager->get_active_kit();

        if ( ! $kit_document ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
                'error_code'     => 'kit_not_found',
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        $astra_settings = get_option( 'astra-settings' );
        if ( ! is_array( $astra_settings ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        $extract_font_name = function( $font_string ) {
            if ( is_string( $font_string ) ) {
                $font_name = preg_replace( "/^'(.+?)'|^\"(.+?)\"|,.*/", '$1$2', $font_string );
                return trim( $font_name );
            }
            return '';
        };

        $kit_settings = $kit_document->get_settings();
        if ( ! isset( $kit_settings['system_typography'] ) || ! is_array( $kit_settings['system_typography'] ) ) {
            $redirect_args = array(
                'ah_sync_status' => 'fail',
                'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
            );
            wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
            exit;
        }

        $font_mapping = [
            'primary'   => [
                'family'  => 'font-family-h1',
                'size'    => 'font-size-h1',
                'weight'  => 'font-weight-h1',
                'extras'  => 'font-extras-h1'
            ],
            'secondary' => [
                'family'  => 'font-family-h2',
                'size'    => 'font-size-h2',
                'weight'  => 'font-weight-h2',
                'extras'  => 'font-extras-h2'
            ],
            'text'      => [
                'family'  => 'body-font-family',
                'size'    => 'font-size-body',
                'weight'  => 'body-font-weight',
                'extras'  => 'body-font-extras'
            ],
            'accent'    => [
                'family'  => 'font-family-h3',
                'size'    => 'font-size-h3',
                'weight'  => 'font-weight-h3',
                'extras'  => 'font-extras-h3'
            ],
        ];

        $new_typography_settings = [];
        foreach ( $kit_settings['system_typography'] as $font_setting ) {
            $elementor_id = $font_setting['_id'];
            $mapping_keys = $font_mapping[ $elementor_id ] ?? [];

            // Get Astra's font-extras, falling back to a global setting or empty array.
            $astra_extras = [];
            if ( 'text' === $elementor_id ) {
                $astra_extras = $astra_settings['body-font-extras'] ?? [];
            } else {
                $astra_extras = $astra_settings[ $mapping_keys['extras'] ] ?? $astra_settings['headings-font-extras'] ?? [];
            }
            
            // Sync Font Family.
            $astra_font_family = $astra_settings[ $mapping_keys['family'] ] ?? $astra_settings['headings-font-family'] ?? '';
            if ( ! empty( $astra_font_family ) ) {
                $font_name = $extract_font_name( $astra_font_family );
                if ( ! empty( $font_name ) ) {
                    $font_setting['typography_font_family'] = $font_name;
                    $font_setting['typography_typography'] = 'custom';
                }
            }

            // Sync Font Size.
            $font_size_data = $astra_settings[ $mapping_keys['size'] ] ?? [];
            if ( ! empty( $font_size_data ) && is_array( $font_size_data ) ) {
                $font_setting['typography_font_size'] = [
                    'unit' => $font_size_data['desktop-unit'] ?? 'px',
                    'size' => (int) ($font_size_data['desktop'] ?? 0),
                ];
                $font_setting['typography_font_size_tablet'] = [
                    'unit' => $font_size_data['tablet-unit'] ?? 'px',
                    'size' => (int) ($font_size_data['tablet'] ?? 0),
                ];
                $font_setting['typography_font_size_mobile'] = [
                    'unit' => $font_size_data['mobile-unit'] ?? 'px',
                    'size' => (int) ($font_size_data['mobile'] ?? 0),
                ];
            }

            // Sync Font Weight.
            $astra_font_weight = $astra_settings[ $mapping_keys['weight'] ] ?? $astra_settings['headings-font-weight'] ?? 'inherit';
            if ( 'inherit' !== $astra_font_weight ) {
                $font_setting['typography_font_weight'] = $astra_font_weight;
            }
            
            // Sync 'font-extras' properties (line height, letter spacing, transform, decoration).
            if ( ! empty( $astra_extras ) && is_array( $astra_extras ) ) {
                // Line Height.
                if ( isset( $astra_extras['line-height'] ) && '' !== $astra_extras['line-height'] ) {
                    $font_setting['typography_line_height'] = [
                        'unit' => $astra_extras['line-height-unit'] ?? 'em',
                        'size' => (float) ($astra_extras['line-height'] ?? 0),
                    ];
                } else {
                    unset( $font_setting['typography_line_height'] );
                }

                // Letter Spacing.
                if ( isset( $astra_extras['letter-spacing'] ) && '' !== $astra_extras['letter-spacing'] ) {
                    $font_setting['typography_letter_spacing'] = [
                        'unit' => $astra_extras['letter-spacing-unit'] ?? 'px',
                        'size' => (float) ($astra_extras['letter-spacing'] ?? 0),
                    ];
                } else {
                    unset( $font_setting['typography_letter_spacing'] );
                }
            }
            
            // Critical fix for text decoration and transform inheritance.
            // If the element is a heading, we must explicitly set these to avoid inheriting from body text.
            if ( 'text' !== $elementor_id ) {
                $font_setting['typography_text_decoration'] = $astra_extras['text-decoration'] ?? 'initial';
                $font_setting['typography_text_transform'] = $astra_extras['text-transform'] ?? 'initial';
            } else {
                $font_setting['typography_text_decoration'] = $astra_extras['text-decoration'] ?? 'none';
                $font_setting['typography_text_transform'] = $astra_extras['text-transform'] ?? 'none';
            }

            $new_typography_settings[] = $font_setting;
        }

        if ( ! empty( $new_typography_settings ) ) {
            $kit_document->update_settings( [ 'system_typography' => $new_typography_settings ] );
        }

        \Elementor\Plugin::instance()->files_manager->clear_cache();

        $redirect_args = array(
            'ah_sync_status' => 'success',
            'sync_type'      => 'typography',
            'ah_sync_nonce'  => wp_create_nonce( 'ah_sync_action' ),
        );
        wp_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php?page=astra-elementor-sync' ) ) );
        exit;
    }

    /**
     * Inject dynamic styles into the head to fix priority issues.
     */
    public function inject_sync_styles() {
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return;
        }

        $kit_manager = \Elementor\Plugin::instance()->kits_manager;
        $kit_document = $kit_manager->get_active_kit();

        if ( ! $kit_document ) {
            return;
        }

        $kit_settings = $kit_document->get_settings();

        if ( ! isset( $kit_settings['system_typography'] ) || ! is_array( $kit_settings['system_typography'] ) ) {
            return;
        }

        // Fetch all typography settings for accurate, explicit styling.
        $styles = [];
        $styles['body'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h1'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h2'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h3'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h4'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h5'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        $styles['h6'] = [
            'decoration' => 'initial',
            'transform' => 'initial',
        ];
        
        $headings_ids = [ 'primary', 'secondary', 'accent' ];
        $body_id = 'text';

        foreach ( $kit_settings['system_typography'] as $font_setting ) {
            $elementor_id = $font_setting['_id'];
            $decoration = $font_setting['typography_text_decoration'] ?? 'initial';
            $transform = $font_setting['typography_text_transform'] ?? 'initial';

            if ( $body_id === $elementor_id ) {
                $styles['body']['decoration'] = $decoration;
                $styles['body']['transform'] = $transform;
            } elseif ( 'primary' === $elementor_id ) {
                $styles['h1']['decoration'] = $decoration;
                $styles['h1']['transform'] = $transform;
            } elseif ( 'secondary' === $elementor_id ) {
                $styles['h2']['decoration'] = $decoration;
                $styles['h2']['transform'] = $transform;
            } elseif ( 'accent' === $elementor_id ) {
                $styles['h3']['decoration'] = $decoration;
                $styles['h3']['transform'] = $transform;
            }
        }
        
        $custom_css = '';
        
        // Explicitly set body text decoration and transform.
        $custom_css .= "
            body, p, a, span {
                text-decoration: {$styles['body']['decoration']} !important;
                text-transform: {$styles['body']['transform']} !important;
            }
        ";
        
        // Explicitly set decoration and transform for all headings to prevent inheritance.
        $custom_css .= "
            h1, h2, h3, h4, h5, h6 {
                text-decoration: none !important;
                text-transform: initial !important;
            }
        ";
        
        echo '<style id="astra-elementor-sync-styles">' . wp_kses_post( $custom_css ) . '</style>';
    }
}

new AHElementorAstraSync();
