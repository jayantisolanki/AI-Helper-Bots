<?php
/*
Plugin Name: AI Helper Bots
Description: A smart assistant that answers questions, books appointments, and handles basic support using OpenAI API.
Version: 1.0
Author: Jayanti Solanki
*/

defined('ABSPATH') || exit;

// Enqueue Scripts and Styles
function ai_helper_bots_enqueue_assets() {
    wp_enqueue_script('ai-helper-bots-js', plugins_url('/js/ai-helper-bots.js', __FILE__), ['jquery'], '1.0', true);
    wp_enqueue_style('ai-helper-bots-css', plugins_url('/css/ai-helper-bots.css', __FILE__));
    
    // Pass AJAX URL and nonce to JavaScript
    wp_localize_script('ai-helper-bots-js', 'aiHelperBotsData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ai_helper_bots_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'ai_helper_bots_enqueue_assets');

// Add Chatbot Widget to Frontend
function ai_helper_bots_render_widget() {
    echo '<div id="ai-helper-bot">
            <div id="ai-bot-header">AI Helper Bot</div>
            <div id="ai-bot-messages"></div>
            <input type="text" id="ai-bot-input" placeholder="Type your question..." />
            <button id="ai-bot-send">Send</button>
          </div>';
}
add_action('wp_footer', 'ai_helper_bots_render_widget');

// Handle AJAX Request to OpenAI API
function ai_helper_bots_handle_query() {
    check_ajax_referer('ai_helper_bots_nonce', 'security');
    
    $user_message = sanitize_text_field($_POST['message']);
    $api_key = get_option('ai_helper_bots_api_key');
    
    if (!$api_key) {
        wp_send_json_error(['message' => 'API key is not set.']);
    }

    // Call OpenAI API
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $user_message],
            ],
            'max_tokens' => 100,
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('OpenAI API error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'Error communicating with OpenAI.']);
    }

    $body = wp_remote_retrieve_body($response);
    error_log('OpenAI API response: ' . $body); // Log the raw response

    $body = json_decode($body, true);
    if (!isset($body['choices'][0]['message']['content'])) {
        wp_send_json_error(['message' => 'Failed to get a valid response from OpenAI.']);
    }

    $bot_reply = $body['choices'][0]['message']['content'];
    wp_send_json_success(['message' => $bot_reply]);
}



add_action('wp_ajax_ai_helper_bots_query', 'ai_helper_bots_handle_query');
add_action('wp_ajax_nopriv_ai_helper_bots_query', 'ai_helper_bots_handle_query');

// Add Settings Page
function ai_helper_bots_settings_page() {
    add_options_page('AI Helper Bots', 'AI Helper Bots', 'manage_options', 'ai-helper-bots', 'ai_helper_bots_render_settings_page');
}
add_action('admin_menu', 'ai_helper_bots_settings_page');

function ai_helper_bots_render_settings_page() {
    if (isset($_POST['save_ai_helper_bots_settings'])) {
        update_option('ai_helper_bots_api_key', sanitize_text_field($_POST['ai_helper_bots_api_key']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>AI Helper Bots Settings</h1>
        <form method="post">
            <label for="ai_helper_bots_api_key">OpenAI API Key:</label><br>
            <input type="text" name="ai_helper_bots_api_key" id="ai_helper_bots_api_key" value="<?php echo esc_attr(get_option('ai_helper_bots_api_key')); ?>" size="50" />
            <br><br>
            <input type="submit" name="save_ai_helper_bots_settings" class="button-primary" value="Save Settings" />
        </form>
    </div>
    <?php
}
?>
