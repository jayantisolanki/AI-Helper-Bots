<?php
/**
 * Plugin Name: AI Helper Bots
 * Plugin URI:  https://jayantisolanki.com/
 * Description: A chatbot assistant that talks to your customers 24/7 using OpenAI APIs.
 * Version:     1.0
 * Author:      Jayanti Solanki
 * Author URI:  https://jayantisolanki.com/
 * License:     GPL2
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue scripts and styles
function ai_helper_bots_enqueue_assets() {
    wp_enqueue_script( 'ai-helper-bots-script', plugin_dir_url( __FILE__ ) . 'assets/js/ai-helper-bots.js', [ 'jquery' ], '1.0', true );
    wp_localize_script( 'ai-helper-bots-script', 'aiHelperBots', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'ai_helper_bots_nonce' ),
    ]);
    wp_enqueue_style( 'ai-helper-bots-style', plugin_dir_url( __FILE__ ) . 'assets/css/ai-helper-bots.css' );
}
add_action( 'wp_enqueue_scripts', 'ai_helper_bots_enqueue_assets' );

// Add chatbot HTML container
function ai_helper_bots_render_chatbot() {
    echo '<div id="ai-helper-bots">
            <div class="chat-header">AI Helper Bots</div>
            <div class="chat-body"></div>
            <div class="chat-footer">
                <input type="text" id="ai-chat-input" placeholder="Ask me anything...">
                <button id="ai-send-button">Send</button>
            </div>
          </div>';
}
add_action( 'wp_footer', 'ai_helper_bots_render_chatbot' );

// Handle AJAX requests
function ai_helper_bots_handle_request() {
    check_ajax_referer( 'ai_helper_bots_nonce', 'nonce' );

    $user_input = sanitize_text_field( $_POST['message'] );
    $openai_api_key = get_option( 'ai_helper_bots_api_key' );

    if ( ! $openai_api_key ) {
        wp_send_json_error( [ 'message' => 'API key is not set.' ] );
    }

    $response = ai_helper_bots_get_ai_response( $user_input, $openai_api_key );

    if ( $response ) {
        wp_send_json_success( [ 'message' => $response ] );
    } else {
        wp_send_json_error( [ 'message' => 'Failed to get response.' ] );
    }
}
add_action( 'wp_ajax_ai_helper_bots_request', 'ai_helper_bots_handle_request' );
add_action( 'wp_ajax_nopriv_ai_helper_bots_request', 'ai_helper_bots_handle_request' );

// Get response from OpenAI API
function ai_helper_bots_get_ai_response( $user_input, $api_key ) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode( [
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an AI assistant.'],
                ['role' => 'user', 'content' => $user_input],
            ],
            'max_tokens'  => 150,
            'temperature' => 0.7,
        ]),
    ];

    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        return [ 'success' => false, 'message' => 'Request failed: ' . $response->get_error_message() ];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['choices'] ) ) {
        return [ 'success' => false, 'message' => $body['error']['message'] ?? 'Failed to get response.' ];
    }

    return [ 'success' => true, 'data' => $body['choices'][0]['message']['content'] ];
}


// Add settings page
function ai_helper_bots_settings_menu() {
    add_options_page(
        'AI Helper Bots Settings',
        'AI Helper Bots',
        'manage_options',
        'ai-helper-bots',
        'ai_helper_bots_settings_page'
    );
}
add_action( 'admin_menu', 'ai_helper_bots_settings_menu' );

// Render settings page
function ai_helper_bots_settings_page() {
    if ( isset( $_POST['submit'] ) ) {
        check_admin_referer( 'ai_helper_bots_save_settings' );
        $api_key = sanitize_text_field( $_POST['ai_helper_bots_api_key'] );
        update_option( 'ai_helper_bots_api_key', $api_key );
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $api_key = get_option( 'ai_helper_bots_api_key', '' );
    ?>
    <div class="wrap">
        <h1>AI Helper Bots Settings</h1>
        <form method="POST">
            <?php wp_nonce_field( 'ai_helper_bots_save_settings' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ai_helper_bots_api_key">OpenAI API Key</label></th>
                    <td><input type="text" name="ai_helper_bots_api_key" id="ai_helper_bots_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>
    </div>
    <?php
}

// Create CSS for chatbot
function ai_helper_bots_create_styles() {
    $css = "
    #ai-helper-bots { position: fixed; bottom: 20px; right: 20px; width: 300px; background: #fff; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: Arial, sans-serif; }
    .chat-header { background: #0073aa; color: #fff; padding: 10px; text-align: center; font-weight: bold; }
    .chat-body { height: 200px; overflow-y: auto; padding: 10px; border-bottom: 1px solid #ddd; }
    .chat-footer { display: flex; padding: 10px; }
    #ai-chat-input { flex: 1; padding: 5px; border: 1px solid #ddd; border-radius: 5px; }
    #ai-send-button { margin-left: 5px; padding: 5px 10px; background: #0073aa; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    #ai-send-button:hover { background: #005f8d; }
    ";
    file_put_contents( plugin_dir_path( __FILE__ ) . 'assets/css/ai-helper-bots.css', $css );
}
register_activation_hook( __FILE__, 'ai_helper_bots_create_styles' );

// Create JS for chatbot
function ai_helper_bots_create_script() {
    $js = "
    jQuery(document).ready(function($) {
        $('#ai-send-button').on('click', function() {
            var message = $('#ai-chat-input').val();
            if (!message) return;

            $('.chat-body').append('<div><b>You:</b> ' + message + '</div>');
            $('#ai-chat-input').val('');

            $.post(aiHelperBots.ajaxUrl, {
                action: 'ai_helper_bots_request',
                nonce: aiHelperBots.nonce,
                message: message
            }, function(response) {
                if (response.success) {
                    $('.chat-body').append('<div><b>Bot:</b> ' + response.data.message + '</div>');
                } else {
                    $('.chat-body').append('<div><b>Bot:</b> Something went wrong.</div>');
                }
                $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
            });
        });
    });
    ";
    file_put_contents( plugin_dir_path( __FILE__ ) . 'assets/js/ai-helper-bots.js', $js );
}
register_activation_hook( __FILE__, 'ai_helper_bots_create_script' );
