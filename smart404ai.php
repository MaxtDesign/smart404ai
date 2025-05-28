<?php
/**
 * Plugin Name: Smart404AI
 * Description: Transform 404 errors into intelligent, AI-driven user experiences using Google Gemini
 * Version: 1.0.0
 * Author: Your Name
 * Plugin URI: https://github.com/yourname/smart404ai
 * Text Domain: smart404ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Smart404AI {
    
    private $gemini_api_key;
    private $plugin_options;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_404'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_smart404ai_chat', array($this, 'ai_chat_handler'));
        add_action('wp_ajax_nopriv_smart404ai_chat', array($this, 'ai_chat_handler'));
        
        $this->plugin_options = get_option('smart404ai_options', array());
        $this->gemini_api_key = isset($this->plugin_options['gemini_api_key']) ? $this->plugin_options['gemini_api_key'] : '';
    }
    
    public function init() {
        // Initialize Smart404AI plugin
    }
    
    public function handle_404() {
        if (is_404() && !empty($this->gemini_api_key)) {
            $this->log_404_attempt();
            add_filter('404_template', array($this, 'custom_404_template'));
        }
    }
    
    public function custom_404_template($template) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/404.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        return $template;
    }
    
    public function enqueue_scripts() {
        if (is_404()) {
            wp_enqueue_script('smart404ai-handler', plugin_dir_url(__FILE__) . 'js/smart404ai.js', array('jquery'), '1.0.0', true);
            wp_localize_script('smart404ai-handler', 'smart404ai_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('smart404ai_nonce')
            ));
            wp_enqueue_style('smart404ai-style', plugin_dir_url(__FILE__) . 'css/smart404ai.css', array(), '1.0.0');
        }
    }
    
    public function analyze_broken_url($url, $referrer = '') {
        if (empty($this->gemini_api_key)) {
            return array('suggestions' => array(), 'message' => 'Please configure your Gemini API key.');
        }
        
        $site_content = $this->get_site_content_summary();
        $broken_url_parts = parse_url($url);
        $path = isset($broken_url_parts['path']) ? $broken_url_parts['path'] : '';
        
        $prompt = "You are helping users who encountered a 404 error on a WordPress website. 
        
Broken URL: {$url}
URL Path: {$path}
Referrer: {$referrer}
Site Content Summary: {$site_content}

Based on the broken URL and available content, please:
1. Analyze what the user was likely looking for
2. Suggest 3-5 most relevant existing pages/posts
3. Create a friendly, contextually appropriate message

Respond in JSON format:
{
    \"analysis\": \"Brief analysis of user intent\",
    \"suggestions\": [
        {\"title\": \"Page Title\", \"url\": \"URL\", \"reason\": \"Why this matches\"},
        {\"title\": \"Page Title\", \"url\": \"URL\", \"reason\": \"Why this matches\"}
    ],
    \"message\": \"Friendly, personalized message for the user\"
}";

        return $this->call_gemini_api($prompt);
    }
    
    public function call_gemini_api($prompt, $max_tokens = 1000) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $this->gemini_api_key;
        
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $max_tokens,
            )
        );
        
        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('error' => 'API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $decoded['candidates'][0]['content']['parts'][0]['text'];
            
            // Try to extract JSON from the response
            $json_start = strpos($ai_response, '{');
            $json_end = strrpos($ai_response, '}');
            
            if ($json_start !== false && $json_end !== false) {
                $json_string = substr($ai_response, $json_start, $json_end - $json_start + 1);
                $parsed = json_decode($json_string, true);
                
                if ($parsed) {
                    return $parsed;
                }
            }
            
            return array('raw_response' => $ai_response);
        }
        
        return array('error' => 'Unexpected API response format');
    }
    
    public function get_site_content_summary() {
        $posts = get_posts(array(
            'numberposts' => 20,
            'post_status' => 'publish',
            'post_type' => array('post', 'page')
        ));
        
        $content_summary = array();
        foreach ($posts as $post) {
            $content_summary[] = array(
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'excerpt' => wp_trim_words($post->post_content, 20),
                'categories' => wp_get_post_categories($post->ID, array('fields' => 'names'))
            );
        }
        
        return json_encode($content_summary);
    }
    
    public function ai_chat_handler() {
        check_ajax_referer('smart404ai_nonce', 'nonce');
        
        $user_message = sanitize_text_field($_POST['message']);
        $current_url = sanitize_url($_POST['current_url']);
        
        $site_content = $this->get_site_content_summary();
        
        $prompt = "You are a helpful assistant for a WordPress website. A user is on a 404 page and asked: '{$user_message}'
        
Current URL: {$current_url}
Available site content: {$site_content}

Please provide a helpful response and suggest specific pages/posts if relevant. Be conversational and friendly.";

        $response = $this->call_gemini_api($prompt, 500);
        
        if (isset($response['error'])) {
            wp_send_json_error($response['error']);
        } else {
            $ai_text = isset($response['raw_response']) ? $response['raw_response'] : 
                      (isset($response['message']) ? $response['message'] : 'I\'m here to help! What were you looking for?');
            wp_send_json_success(array('message' => $ai_text));
        }
    }
    
    public function log_404_attempt() {
        $log_data = array(
            'url' => $_SERVER['REQUEST_URI'],
            'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => current_time('mysql')
        );
        
        $logs = get_option('smart404ai_logs', array());
        $logs[] = $log_data;
        
        // Keep only last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('smart404ai_logs', $logs);
    }
    
    // Admin Interface
    public function add_admin_menu() {
        add_options_page(
            'Smart404AI Settings',
            'Smart404AI',
            'manage_options',
            'smart404ai',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('smart404ai_options', 'smart404ai_options');
        
        add_settings_section(
            'smart404ai_main',
            'Main Settings',
            array($this, 'settings_section_callback'),
            'smart404ai'
        );
        
        add_settings_field(
            'gemini_api_key',
            'Google Gemini API Key',
            array($this, 'gemini_api_key_callback'),
            'smart404ai',
            'smart404ai_main'
        );
        
        add_settings_field(
            'enable_chat',
            'Enable AI Chat on 404 Pages',
            array($this, 'enable_chat_callback'),
            'smart404ai',
            'smart404ai_main'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>Configure your AI-powered 404 handler settings below.</p>';
    }
    
    public function gemini_api_key_callback() {
        $value = isset($this->plugin_options['gemini_api_key']) ? $this->plugin_options['gemini_api_key'] : '';
        echo '<input type="password" name="smart404ai_options[gemini_api_key]" value="' . esc_attr($value) . '" size="50" />';
        echo '<p class="description">Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></p>';
    }
    
    public function enable_chat_callback() {
        $value = isset($this->plugin_options['enable_chat']) ? $this->plugin_options['enable_chat'] : 1;
        echo '<input type="checkbox" name="smart404ai_options[enable_chat]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>Enable AI chat assistant on 404 pages</label>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart404AI Settings</h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved!</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('smart404ai_options');
                do_settings_sections('smart404ai');
                submit_button();
                ?>
            </form>
            
            <div class="smart404ai-analytics">
                <h2>Recent 404 Analytics</h2>
                <?php $this->display_404_logs(); ?>
            </div>
            
            <div class="smart404ai-test">
                <h2>Test AI Integration</h2>
                <button type="button" id="test-ai-integration" class="button button-secondary">Test Gemini Connection</button>
                <div id="test-result"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-ai-integration').click(function() {
                $(this).prop('disabled', true).text('Testing...');
                $('#test-result').html('<p>Testing connection to Google Gemini...</p>');
                
                $.post(ajaxurl, {
                    action: 'test_smart404ai_integration',
                    nonce: '<?php echo wp_create_nonce('smart404ai_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#test-result').html('<div class="notice notice-success"><p>✅ AI integration working! Response: ' + response.data + '</p></div>');
                    } else {
                        $('#test-result').html('<div class="notice notice-error"><p>❌ Connection failed: ' + response.data + '</p></div>');
                    }
                    $('#test-ai-integration').prop('disabled', false).text('Test Gemini Connection');
                });
            });
        });
        </script>
        <?php
    }
    
    public function display_404_logs() {
        $logs = get_option('smart404ai_logs', array());
        $recent_logs = array_slice(array_reverse($logs), 0, 10);
        
        if (empty($recent_logs)) {
            echo '<p>No 404 errors logged yet.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>URL</th><th>Referrer</th><th>Time</th></tr></thead><tbody>';
        
        foreach ($recent_logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log['url']) . '</td>';
            echo '<td>' . esc_html(substr($log['referrer'], 0, 50)) . '</td>';
            echo '<td>' . esc_html($log['timestamp']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
}

// Initialize the Smart404AI plugin
new Smart404AI();

// Test AI integration AJAX handler
add_action('wp_ajax_test_smart404ai_integration', function() {
    check_ajax_referer('smart404ai_nonce', 'nonce');
    
    $handler = new Smart404AI();
    $test_response = $handler->call_gemini_api('Say "Hello! Smart404AI integration is working correctly." in a friendly way.', 100);
    
    if (isset($test_response['error'])) {
        wp_send_json_error($test_response['error']);
    } else {
        $message = isset($test_response['raw_response']) ? $test_response['raw_response'] : 'Connection successful!';
        wp_send_json_success($message);
    }
});
?>