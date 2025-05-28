<?php
/**
 * Plugin Name: Smart404AI
 * Description: Transform 404 errors into intelligent, AI-driven user experiences using Google Gemini
 * Version: 1.0.0
 * Author: Maxt Design
 * Plugin URI: https://github.com/MaxtDesign/smart404ai
 * Text Domain: smart404ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Smart404AI {
    
    private $api_keys;
    private $plugin_options;
    public $ai_provider; // Made public for test function access
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_404'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_smart404ai_chat', array($this, 'ai_chat_handler'));
        add_action('wp_ajax_nopriv_smart404ai_chat', array($this, 'ai_chat_handler'));
        add_action('wp_ajax_export_404_logs', array($this, 'export_404_logs'));
        add_action('wp_ajax_delete_404_logs', array($this, 'delete_404_logs'));
        add_action('wp_ajax_create_page_from_404', array($this, 'create_page_from_404'));
        
        $this->plugin_options = get_option('smart404ai_options', array());
        $this->ai_provider = isset($this->plugin_options['ai_provider']) ? $this->plugin_options['ai_provider'] : 'gemini';
        $this->api_keys = array(
            'gemini' => isset($this->plugin_options['gemini_api_key']) ? $this->plugin_options['gemini_api_key'] : '',
            'openai' => isset($this->plugin_options['openai_api_key']) ? $this->plugin_options['openai_api_key'] : '',
            'anthropic' => isset($this->plugin_options['anthropic_api_key']) ? $this->plugin_options['anthropic_api_key'] : ''
        );
    }
    
    public function init() {
        // Initialize Smart404AI plugin
    }
    
    public function handle_404() {
        if (is_404() && !empty($this->api_keys[$this->ai_provider])) {
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
            
            // Add theme-aware styling
            $this->add_theme_integration_styles();
        }
    }
    
    public function add_theme_integration_styles() {
        // Try to extract theme colors automatically
        $primary_color = '#4285f4'; // Default fallback
        $text_color = '#333'; // Default fallback
        $background_color = '#fff'; // Default fallback
        
        // Attempt to get theme customizer colors
        if (function_exists('get_theme_mod')) {
            $primary_color = get_theme_mod('accent_color', get_theme_mod('primary_color', $primary_color));
            $text_color = get_theme_mod('text_color', $text_color);
            $background_color = get_theme_mod('background_color', $background_color);
            
            // Add # to background color if it's missing
            if ($background_color && strpos($background_color, '#') !== 0) {
                $background_color = '#' . $background_color;
            }
        }
        
        // Check if theme has CSS custom properties we can use
        $theme_aware_css = "
        :root {
            --smart404ai-primary: {$primary_color};
            --smart404ai-text: {$text_color};
            --smart404ai-bg: {$background_color};
            --smart404ai-font: inherit;
        }
        
        /* Theme integration attempts */
        .smart404ai-container {
            color: var(--smart404ai-text, #333);
            font-family: var(--smart404ai-font, inherit);
        }
        
        .smart404ai-title {
            color: var(--smart404ai-primary, #4285f4);
        }
        
        .suggestion-link,
        #chat-send,
        .nav-button:hover {
            background: var(--smart404ai-primary, #4285f4) !important;
        }
        
        /* Try to inherit theme button styles */
        .nav-button {
            font-family: inherit;
        }
        ";
        
        wp_add_inline_style('smart404ai-style', $theme_aware_css);
    }
    
    public function analyze_broken_url($url, $referrer = '') {
        if (empty($this->api_keys[$this->ai_provider])) {
            return array('suggestions' => array(), 'fun_title' => 'Oops! Page Not Found', 'fun_message' => 'Please configure your ' . ucfirst($this->ai_provider) . ' API key to enable AI-powered suggestions.');
        }
        
        $site_content = $this->get_site_content_summary();
        $broken_url_parts = parse_url($url);
        $path = isset($broken_url_parts['path']) ? $broken_url_parts['path'] : '';
        
        // Get brand voice settings
        $brand_tone = isset($this->plugin_options['brand_tone']) ? $this->plugin_options['brand_tone'] : 'friendly';
        $industry = isset($this->plugin_options['industry_template']) ? $this->plugin_options['industry_template'] : 'general';
        $brand_sample = isset($this->plugin_options['brand_sample']) ? $this->plugin_options['brand_sample'] : '';
        
        // Build brand context
        $brand_context = $this->get_brand_context($brand_tone, $industry, $brand_sample);
        
        $prompt = "You are a witty, creative assistant helping users who encountered a 404 error on a WordPress website.

{$brand_context}

Broken URL: {$url}
URL Path: {$path}
Referrer: {$referrer}
Site Content Summary: {$site_content}

Please provide TWO different types of responses:

1. SMART SUGGESTIONS: Analyze the broken URL and find the 3-4 most relevant existing pages from the site content. Be practical and helpful.

2. ENTERTAINING 404 MESSAGE: Create a humorous, on-brand 404 experience with:
   - A creative, punchy error title (matching the brand tone and industry context)
   - A fun, engaging explanation (could be fictional, metaphorical, or just playfully explain what happened)
   - Keep it light, entertaining, but not too long
   - Match the brand voice and industry context provided above

Respond in this JSON format:
{
    \"suggestions\": [
        {\"title\": \"Page Title\", \"url\": \"URL\", \"reason\": \"Why this matches user intent\"},
        {\"title\": \"Page Title\", \"url\": \"URL\", \"reason\": \"Why this matches user intent\"}
    ],
    \"fun_title\": \"Creative 404 error title (matching brand tone)\",
    \"fun_message\": \"Entertaining explanation matching brand voice (2-3 sentences max)\"
}

Make the entertaining content engaging, memorable, and perfectly aligned with the brand voice, while keeping suggestions practical and helpful.";

        return $this->call_ai_api($prompt);
    }
    
    public function get_brand_context($tone, $industry, $sample) {
        $tone_descriptions = array(
            'professional' => 'Maintain a professional, authoritative tone. Be helpful and clear without being overly casual.',
            'friendly' => 'Use a warm, welcoming tone. Be approachable and helpful, like talking to a friend.',
            'humorous' => 'Be playful and witty. Use humor appropriately while still being helpful.',
            'casual' => 'Keep it relaxed and conversational. Use everyday language and be laid-back.',
            'technical' => 'Use precise, technical language. Be informative and detailed in explanations.',
            'quirky' => 'Be unique and creative. Use unexpected metaphors and playful language.'
        );
        
        $industry_contexts = array(
            'tech_startup' => 'Tech startup context - use modern, innovative language. References to coding, apps, and digital innovation are appropriate.',
            'local_business' => 'Local business context - be community-focused and personal. Mention local connections and neighborhood feel.',
            'creative_agency' => 'Creative agency context - be artistic and innovative. Use creative metaphors and design-focused language.',
            'ecommerce' => 'E-commerce context - focus on products, shopping, and customer service. Be sales-friendly but not pushy.',
            'nonprofit' => 'Nonprofit context - emphasize mission, community impact, and helping others. Be sincere and purposeful.',
            'educational' => 'Educational context - be informative and encouraging. Use learning-focused language and academic tone.',
            'healthcare' => 'Healthcare context - be caring, professional, and trustworthy. Emphasize help and well-being.',
            'finance' => 'Financial services context - be trustworthy, professional, and clear. Avoid complex jargon.',
            'general' => 'General business context - adaptable tone suitable for any industry.'
        );
        
        $brand_context = "BRAND VOICE GUIDELINES:\n";
        $brand_context .= "Tone: " . ucfirst($tone) . " - " . ($tone_descriptions[$tone] ?? 'Be helpful and engaging.') . "\n";
        $brand_context .= "Industry Context: " . ($industry_contexts[$industry] ?? $industry_contexts['general']) . "\n";
        
        if (!empty($sample)) {
            $brand_context .= "Brand Writing Sample (match this style): \"" . substr($sample, 0, 500) . "\"\n";
        }
        
        $brand_context .= "IMPORTANT: All responses must perfectly match this brand voice and tone.\n\n";
        
        return $brand_context;
    }
    
    public function call_ai_api($prompt, $max_tokens = 1000) {
        switch ($this->ai_provider) {
            case 'openai':
                return $this->call_openai_api($prompt, $max_tokens);
            case 'anthropic':
                return $this->call_anthropic_api($prompt, $max_tokens);
            case 'gemini':
            default:
                return $this->call_gemini_api($prompt, $max_tokens);
        }
    }
    
    public function call_openai_api($prompt, $max_tokens = 1000) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-4o-mini', // Using cost-effective model
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $max_tokens,
            'temperature' => 0.7,
            'response_format' => array('type' => 'json_object')
        );
        
        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_keys['openai']
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('error' => 'OpenAI API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            $ai_response = $decoded['choices'][0]['message']['content'];
            $parsed = json_decode($ai_response, true);
            
            if ($parsed) {
                return $parsed;
            }
            
            return array('raw_response' => $ai_response);
        }
        
        return array('error' => 'Unexpected OpenAI API response format');
    }
    
    public function call_anthropic_api($prompt, $max_tokens = 1000) {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $data = array(
            'model' => 'claude-3-haiku-20240307', // Using cost-effective model
            'max_tokens' => $max_tokens,
            'temperature' => 0.7,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt . "\n\nPlease respond only with valid JSON."
                )
            )
        );
        
        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_keys['anthropic'],
                'anthropic-version' => '2023-06-01'
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('error' => 'Anthropic API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['content'][0]['text'])) {
            $ai_response = $decoded['content'][0]['text'];
            
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
        
        return array('error' => 'Unexpected Anthropic API response format');
    }
    
    public function call_gemini_api($prompt, $max_tokens = 1000) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $this->api_keys['gemini'];
        
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
        
        // Get brand voice settings
        $brand_tone = isset($this->plugin_options['brand_tone']) ? $this->plugin_options['brand_tone'] : 'friendly';
        $industry = isset($this->plugin_options['industry_template']) ? $this->plugin_options['industry_template'] : 'general';
        $brand_sample = isset($this->plugin_options['brand_sample']) ? $this->plugin_options['brand_sample'] : '';
        
        // Build brand context
        $brand_context = $this->get_brand_context($brand_tone, $industry, $brand_sample);
        
        $prompt = "You are a helpful assistant for a WordPress website. A user is on a 404 page and asked: '{$user_message}'

{$brand_context}

Current URL: {$current_url}
Available site content: {$site_content}

Please provide a helpful response that perfectly matches the brand voice and tone specified above. Suggest specific pages/posts if relevant. Be conversational and friendly while maintaining the brand personality. You can use markdown formatting for better readability.

Remember to stay true to the brand voice - if it's humorous, be funny; if it's professional, be formal; if it's quirky, be creative. The response should feel like it's coming directly from the brand itself.";

        $response = $this->call_ai_api($prompt, 500);
        
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
    
    // Log Management Functions
    public function export_404_logs() {
        check_ajax_referer('smart404ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $logs = get_option('smart404ai_logs', array());
        
        if (empty($logs)) {
            wp_send_json_error('No logs to export');
        }
        
        // Prepare CSV data
        $csv_data = "URL,Referrer,User Agent,Timestamp\n";
        
        foreach ($logs as $log) {
            $csv_data .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $log['url']),
                str_replace('"', '""', $log['referrer']),
                str_replace('"', '""', $log['user_agent']),
                $log['timestamp']
            );
        }
        
        $filename = 'smart404ai-logs-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv_data));
        
        echo $csv_data;
        exit;
    }
    
    public function delete_404_logs() {
        check_ajax_referer('smart404ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['delete_type']);
        
        if ($type === 'all') {
            update_option('smart404ai_logs', array());
            wp_send_json_success('All logs deleted successfully');
        } elseif ($type === 'old') {
            $logs = get_option('smart404ai_logs', array());
            $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
            
            $filtered_logs = array_filter($logs, function($log) use ($cutoff_date) {
                return $log['timestamp'] > $cutoff_date;
            });
            
            update_option('smart404ai_logs', array_values($filtered_logs));
            $deleted_count = count($logs) - count($filtered_logs);
            wp_send_json_success("Deleted {$deleted_count} old logs (older than 30 days)");
        }
        
        wp_send_json_error('Invalid delete type');
    }
    
    public function create_page_from_404() {
        check_ajax_referer('smart404ai_nonce', 'nonce');
        
        if (!current_user_can('edit_pages')) {
            wp_send_json_error('Insufficient permissions to create pages');
        }
        
        $url_path = sanitize_text_field($_POST['url_path']);
        $suggested_title = sanitize_text_field($_POST['suggested_title']);
        
        // Create page
        $page_data = array(
            'post_title' => $suggested_title ?: 'New Page from 404: ' . trim($url_path, '/'),
            'post_content' => '<p>This page was created from a 404 error for the URL: <code>' . esc_html($url_path) . '</code></p><p>Please add your content here.</p>',
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_author' => get_current_user_id()
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            wp_send_json_error('Failed to create page: ' . $page_id->get_error_message());
        }
        
        // Try to set the correct slug based on the 404 URL
        $desired_slug = trim($url_path, '/');
        if ($desired_slug) {
            wp_update_post(array(
                'ID' => $page_id,
                'post_name' => sanitize_title($desired_slug)
            ));
        }
        
        $edit_url = admin_url('post.php?post=' . $page_id . '&action=edit');
        
        wp_send_json_success(array(
            'page_id' => $page_id,
            'edit_url' => $edit_url,
            'message' => 'Page created successfully as draft'
        ));
    }
    
    public function get_404_frequency_analysis() {
        $logs = get_option('smart404ai_logs', array());
        $url_counts = array();
        
        foreach ($logs as $log) {
            $url = $log['url'];
            if (!isset($url_counts[$url])) {
                $url_counts[$url] = array(
                    'count' => 0,
                    'last_seen' => $log['timestamp'],
                    'referrers' => array()
                );
            }
            $url_counts[$url]['count']++;
            if ($log['timestamp'] > $url_counts[$url]['last_seen']) {
                $url_counts[$url]['last_seen'] = $log['timestamp'];
            }
            if (!empty($log['referrer']) && !in_array($log['referrer'], $url_counts[$url]['referrers'])) {
                $url_counts[$url]['referrers'][] = $log['referrer'];
            }
        }
        
        // Sort by frequency
        uasort($url_counts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $url_counts;
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
            'AI Provider Settings',
            array($this, 'settings_section_callback'),
            'smart404ai'
        );
        
        add_settings_field(
            'ai_provider',
            'AI Provider',
            array($this, 'ai_provider_callback'),
            'smart404ai',
            'smart404ai_main'
        );
        
        add_settings_field(
            'gemini_api_key',
            'Google Gemini API Key',
            array($this, 'gemini_api_key_callback'),
            'smart404ai',
            'smart404ai_main'
        );
        
        add_settings_field(
            'openai_api_key',
            'OpenAI API Key',
            array($this, 'openai_api_key_callback'),
            'smart404ai',
            'smart404ai_main'
        );
        
        add_settings_field(
            'anthropic_api_key',
            'Anthropic (Claude) API Key',
            array($this, 'anthropic_api_key_callback'),
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
        
        add_settings_section(
            'smart404ai_brand',
            'Brand Voice & Style',
            array($this, 'brand_section_callback'),
            'smart404ai'
        );
        
        add_settings_field(
            'brand_tone',
            'Brand Tone',
            array($this, 'brand_tone_callback'),
            'smart404ai',
            'smart404ai_brand'
        );
        
        add_settings_field(
            'industry_template',
            'Industry Template',
            array($this, 'industry_template_callback'),
            'smart404ai',
            'smart404ai_brand'
        );
        
        add_settings_field(
            'brand_sample',
            'Brand Writing Sample',
            array($this, 'brand_sample_callback'),
            'smart404ai',
            'smart404ai_brand'
        );
    }
    
    public function brand_section_callback() {
        echo '<p>Customize how the AI represents your brand. This helps ensure all responses match your unique voice and personality.</p>';
    }
    
    public function brand_tone_callback() {
        $value = isset($this->plugin_options['brand_tone']) ? $this->plugin_options['brand_tone'] : 'friendly';
        echo '<select name="smart404ai_options[brand_tone]">';
        echo '<option value="professional"' . selected('professional', $value, false) . '>Professional</option>';
        echo '<option value="friendly"' . selected('friendly', $value, false) . '>Friendly</option>';
        echo '<option value="humorous"' . selected('humorous', $value, false) . '>Humorous</option>';
        echo '<option value="casual"' . selected('casual', $value, false) . '>Casual</option>';
        echo '<option value="technical"' . selected('technical', $value, false) . '>Technical</option>';
        echo '<option value="quirky"' . selected('quirky', $value, false) . '>Quirky</option>';
        echo '</select>';
        echo '<p class="description">Choose the overall tone for your 404 pages and AI responses.</p>';
    }
    
    public function industry_template_callback() {
        $value = isset($this->plugin_options['industry_template']) ? $this->plugin_options['industry_template'] : 'general';
        echo '<select name="smart404ai_options[industry_template]">';
        echo '<option value="general"' . selected('general', $value, false) . '>General Business</option>';
        echo '<option value="tech_startup"' . selected('tech_startup', $value, false) . '>Tech Startup</option>';
        echo '<option value="local_business"' . selected('local_business', $value, false) . '>Local Business</option>';
        echo '<option value="creative_agency"' . selected('creative_agency', $value, false) . '>Creative Agency</option>';
        echo '<option value="ecommerce"' . selected('ecommerce', $value, false) . '>E-commerce Store</option>';
        echo '<option value="nonprofit"' . selected('nonprofit', $value, false) . '>Nonprofit Organization</option>';
        echo '<option value="educational"' . selected('educational', $value, false) . '>Educational Institution</option>';
        echo '<option value="healthcare"' . selected('healthcare', $value, false) . '>Healthcare</option>';
        echo '<option value="finance"' . selected('finance', $value, false) . '>Financial Services</option>';
        echo '</select>';
        echo '<p class="description">Select your industry for context-appropriate language and references.</p>';
    }
    
    public function brand_sample_callback() {
        $value = isset($this->plugin_options['brand_sample']) ? $this->plugin_options['brand_sample'] : '';
        echo '<textarea name="smart404ai_options[brand_sample]" rows="4" cols="70" placeholder="Paste a sample of your brand writing (from About Us, homepage, etc.) to help the AI learn your style...">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Optional: Provide a writing sample to help the AI match your brand voice more accurately.</p>';
    }
    
    public function settings_section_callback() {
        echo '<p>Choose your preferred AI provider and configure the corresponding API key below. You only need one API key for your chosen provider.</p>';
    }
    
    public function ai_provider_callback() {
        $value = isset($this->plugin_options['ai_provider']) ? $this->plugin_options['ai_provider'] : 'gemini';
        echo '<select name="smart404ai_options[ai_provider]" id="ai_provider">';
        echo '<option value="gemini"' . selected('gemini', $value, false) . '>Google Gemini (Free tier available)</option>';
        echo '<option value="openai"' . selected('openai', $value, false) . '>OpenAI GPT-4o-mini (Pay-per-use)</option>';
        echo '<option value="anthropic"' . selected('anthropic', $value, false) . '>Anthropic Claude (Pay-per-use)</option>';
        echo '</select>';
        echo '<p class="description">Select your preferred AI provider. Each has different pricing and capabilities.</p>';
    }
    
    public function gemini_api_key_callback() {
        $value = isset($this->plugin_options['gemini_api_key']) ? $this->plugin_options['gemini_api_key'] : '';
        echo '<input type="password" name="smart404ai_options[gemini_api_key]" value="' . esc_attr($value) . '" size="50" class="api-key-field" data-provider="gemini" />';
        echo '<p class="description">Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a> (Free tier available)</p>';
    }
    
    public function openai_api_key_callback() {
        $value = isset($this->plugin_options['openai_api_key']) ? $this->plugin_options['openai_api_key'] : '';
        echo '<input type="password" name="smart404ai_options[openai_api_key]" value="' . esc_attr($value) . '" size="50" class="api-key-field" data-provider="openai" />';
        echo '<p class="description">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a> (Pay-per-use pricing)</p>';
    }
    
    public function anthropic_api_key_callback() {
        $value = isset($this->plugin_options['anthropic_api_key']) ? $this->plugin_options['anthropic_api_key'] : '';
        echo '<input type="password" name="smart404ai_options[anthropic_api_key]" value="' . esc_attr($value) . '" size="50" class="api-key-field" data-provider="anthropic" />';
        echo '<p class="description">Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a> (Pay-per-use pricing)</p>';
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
                <button type="button" id="test-ai-integration" class="button button-secondary">Test <span id="current-provider"><?php echo ucfirst($this->ai_provider); ?></span> Connection</button>
                <div id="test-result"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle provider switching
            $('#ai_provider').change(function() {
                var selectedProvider = $(this).val();
                $('#current-provider').text(selectedProvider.charAt(0).toUpperCase() + selectedProvider.slice(1));
                
                // Show/hide relevant API key fields
                $('.api-key-field').closest('tr').hide();
                $('.api-key-field[data-provider="' + selectedProvider + '"]').closest('tr').show();
            });
            
            // Initialize visibility
            $('#ai_provider').trigger('change');
            
            $('#test-ai-integration').click(function() {
                $(this).prop('disabled', true).text('Testing...');
                $('#test-result').html('<p>Testing connection to ' + $('#ai_provider').val() + '...</p>');
                
                $.post(ajaxurl, {
                    action: 'test_smart404ai_integration',
                    nonce: '<?php echo wp_create_nonce('smart404ai_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#test-result').html('<div class="notice notice-success"><p>✅ AI integration working! Response: ' + response.data + '</p></div>');
                    } else {
                        $('#test-result').html('<div class="notice notice-error"><p>❌ Connection failed: ' + response.data + '</p></div>');
                    }
                    var provider = $('#ai_provider').val();
                    $('#test-ai-integration').prop('disabled', false).text('Test ' + provider.charAt(0).toUpperCase() + provider.slice(1) + ' Connection');
                });
            });
        });
        </script>
        
        <style>
        .api-key-field[data-provider]:not([data-provider="gemini"]) {
            display: none;
        }
        .api-key-field {
            width: 400px;
        }
        .notice {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid;
            background: #fff;
        }
        .notice-success {
            border-left-color: #46b450;
        }
        .notice-error {
            border-left-color: #dc3232;
        }
        </style>
        <?php
    }
    
    public function display_404_logs() {
        $logs = get_option('smart404ai_logs', array());
        $recent_logs = array_slice(array_reverse($logs), 0, 10);
        $frequency_analysis = $this->get_404_frequency_analysis();
        $top_offenders = array_slice($frequency_analysis, 0, 5, true);
        
        if (empty($logs)) {
            echo '<p>No 404 errors logged yet.</p>';
            return;
        }
        
        echo '<div class="smart404ai-log-management">';
        
        // Log Management Actions
        echo '<div class="log-actions" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0;">Log Management</h3>';
        echo '<button type="button" id="export-logs" class="button button-secondary">↓ Export Logs</button> ';
        echo '<button type="button" id="delete-old-logs" class="button button-secondary">⊗ Delete Old Logs (30+ days)</button> ';
        echo '<button type="button" id="delete-all-logs" class="button button-secondary" style="color: #d63638;">✕ Delete All Logs</button>';
        echo '<p class="description">Total logs: ' . count($logs) . '</p>';
        echo '</div>';
        
        // Top Offenders Section
        if (!empty($top_offenders)) {
            echo '<div class="top-offenders" style="margin-bottom: 30px;">';
            echo '<h3>Most Frequent 404s <span class="description">(Candidates for new pages)</span></h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th style="width: 50%;">URL</th><th>Count</th><th>Last Seen</th><th>Actions</th></tr></thead><tbody>';
            
            foreach ($top_offenders as $url => $data) {
                if ($data['count'] < 2) break; // Only show URLs with multiple hits
                
                echo '<tr>';
                echo '<td><code>' . esc_html($url) . '</code></td>';
                echo '<td><span class="frequency-badge" style="background: #d63638; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . $data['count'] . '</span></td>';
                echo '<td>' . esc_html($data['last_seen']) . '</td>';
                echo '<td>';
                echo '<button type="button" class="create-page-btn button button-small" data-url="' . esc_attr($url) . '">+ Create Page</button>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>';
        }
        
        // Recent Logs Section
        echo '<h3>Recent 404 Errors</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>URL</th><th>Referrer</th><th>Time</th></tr></thead><tbody>';
        
        foreach ($recent_logs as $log) {
            echo '<tr>';
            echo '<td><code>' . esc_html($log['url']) . '</code></td>';
            echo '<td>' . esc_html(substr($log['referrer'], 0, 50)) . '</td>';
            echo '<td>' . esc_html($log['timestamp']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // JavaScript for log management
        echo '<script>
        jQuery(document).ready(function($) {
            $("#export-logs").click(function() {
                window.location.href = ajaxurl + "?action=export_404_logs&nonce=' . wp_create_nonce('smart404ai_nonce') . '";
            });
            
            $("#delete-old-logs").click(function() {
                if (confirm("Delete logs older than 30 days?")) {
                    $.post(ajaxurl, {
                        action: "delete_404_logs",
                        delete_type: "old",
                        nonce: "' . wp_create_nonce('smart404ai_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            alert(response.data);
                            location.reload();
                        } else {
                            alert("Error: " + response.data);
                        }
                    });
                }
            });
            
            $("#delete-all-logs").click(function() {
                if (confirm("Are you sure you want to delete ALL 404 logs? This cannot be undone.")) {
                    $.post(ajaxurl, {
                        action: "delete_404_logs",
                        delete_type: "all",
                        nonce: "' . wp_create_nonce('smart404ai_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            alert(response.data);
                            location.reload();
                        } else {
                            alert("Error: " + response.data);
                        }
                    });
                }
            });
            
            $(".create-page-btn").click(function() {
                var url = $(this).data("url");
                var title = prompt("Enter page title (or leave blank for auto-generated):");
                
                if (title !== null) {
                    $(this).prop("disabled", true).text("Creating...");
                    
                    $.post(ajaxurl, {
                        action: "create_page_from_404",
                        url_path: url,
                        suggested_title: title,
                        nonce: "' . wp_create_nonce('smart404ai_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            if (confirm(response.data.message + "\\n\\nWould you like to edit the page now?")) {
                                window.open(response.data.edit_url, "_blank");
                            }
                            location.reload();
                        } else {
                            alert("Error: " + response.data);
                        }
                    }).always(function() {
                        $(".create-page-btn").prop("disabled", false).text("+ Create Page");
                    });
                }
            });
        });
        </script>';
    }
}

// Initialize the Smart404AI plugin
new Smart404AI();

// Test AI integration AJAX handler
add_action('wp_ajax_test_smart404ai_integration', function() {
    check_ajax_referer('smart404ai_nonce', 'nonce');
    
    $handler = new Smart404AI();
    $provider = $handler->ai_provider;
    
    $test_message = "Say 'Hello! Smart404AI integration with " . ucfirst($provider) . " is working correctly.' in a friendly way.";
    $test_response = $handler->call_ai_api($test_message, 100);
    
    if (isset($test_response['error'])) {
        wp_send_json_error($test_response['error']);
    } else {
        $message = isset($test_response['raw_response']) ? $test_response['raw_response'] : 'Connection successful!';
        wp_send_json_success($message);
    }
});
?>