<?php
/**
 * Plugin Name: Smart 404 AI
 * Description: AI-powered 404 pages with intelligent content matching and branded messaging
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: smart-404-ai
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Smart404AI {
    
    private $option_name = 'smart_404_ai_settings';
    private $analytics_option = 'smart_404_ai_analytics';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_init', array($this, 'handle_export'));
        add_action('template_redirect', array($this, 'handle_404'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_ajax_smart_404_track', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_smart_404_track', array($this, 'track_click'));
        add_action('wp_ajax_test_smart_404_api', array($this, 'test_api_connection'));
    }
    
    public function activate() {
        // Create default settings
        $default_settings = array(
            'api_provider' => 'openai',
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'brand_voice_sample' => '',
            'personality_preset' => 'friendly',
            'message_length' => 'standard',
            'include_emoji' => true,
            'fallback_message' => 'Oops! That page seems to have moved. Here are some alternatives that might help:'
        );
        
        if (!get_option($this->option_name)) {
            add_option($this->option_name, $default_settings);
        }
        
        // Initialize analytics
        if (!get_option($this->analytics_option)) {
            add_option($this->analytics_option, array());
        }
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'Smart 404 AI',
            'Smart 404 AI',
            'manage_options',
            'smart-404-ai',
            array($this, 'analytics_page'),
            'dashicons-search',
            30
        );
        
        // Analytics submenu (default)
        add_submenu_page(
            'smart-404-ai',
            'Smart 404 Analytics',
            'Analytics',
            'manage_options',
            'smart-404-ai',
            array($this, 'analytics_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'smart-404-ai',
            'Smart 404 AI Settings',
            'Settings',
            'manage_options',
            'smart-404-ai-settings',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('smart_404_ai_group', $this->option_name);
    }
    
    public function settings_page() {
        $settings = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h1>Smart 404 AI Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('smart_404_ai_group'); ?>
                
                <div class="postbox" style="margin-top: 20px;">
                    <div class="postbox-header">
                        <h2>🔐 API Configuration</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">API Provider</th>
                                <td>
                                    <select name="<?php echo $this->option_name; ?>[api_provider]">
                                        <option value="openai" <?php selected($settings['api_provider'], 'openai'); ?>>OpenAI</option>
                                        <option value="anthropic" <?php selected($settings['api_provider'], 'anthropic'); ?>>Anthropic (Claude)</option>
                                        <option value="gemini" <?php selected($settings['api_provider'], 'gemini'); ?>>Google Gemini</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">API Key</th>
                                <td>
                                    <input type="password" name="<?php echo $this->option_name; ?>[api_key]" 
                                           value="<?php echo esc_attr($settings['api_key']); ?>" 
                                           class="regular-text" placeholder="Enter your API key..." />
                                    <p class="description" id="api-key-help">
                                        Your API key will be stored securely
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Model</th>
                                <td>
                                    <select name="<?php echo $this->option_name; ?>[model]" id="model-select">
                                        <!-- OpenAI Models -->
                                        <option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?> data-provider="openai">GPT-3.5 Turbo (Cheaper)</option>
                                        <option value="gpt-4" <?php selected($settings['model'], 'gpt-4'); ?> data-provider="openai">GPT-4 (Better Quality)</option>
                                        <option value="gpt-4-turbo" <?php selected($settings['model'], 'gpt-4-turbo'); ?> data-provider="openai">GPT-4 Turbo</option>
                                        
                                        <!-- Anthropic Models -->
                                        <option value="claude-3-haiku-20240307" <?php selected($settings['model'], 'claude-3-haiku-20240307'); ?> data-provider="anthropic">Claude 3 Haiku (Fast & Cheap)</option>
                                        <option value="claude-3-sonnet-20240229" <?php selected($settings['model'], 'claude-3-sonnet-20240229'); ?> data-provider="anthropic">Claude 3 Sonnet (Balanced)</option>
                                        <option value="claude-3-opus-20240229" <?php selected($settings['model'], 'claude-3-opus-20240229'); ?> data-provider="anthropic">Claude 3 Opus (Most Capable)</option>
                                        
                                        <!-- Gemini Models -->
                                        <option value="gemini-pro" <?php selected($settings['model'], 'gemini-pro'); ?> data-provider="gemini">Gemini Pro</option>
                                        <option value="gemini-1.5-pro" <?php selected($settings['model'], 'gemini-1.5-pro'); ?> data-provider="gemini">Gemini 1.5 Pro</option>
                                        <option value="gemini-1.5-flash" <?php selected($settings['model'], 'gemini-1.5-flash'); ?> data-provider="gemini">Gemini 1.5 Flash (Faster)</option>
                                    </select>
                                    <p class="description" id="model-help">
                                        Select the AI model to use for generating 404 messages
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <button type="button" id="test-api" class="button">Test API Connection</button>
                        <div id="api-test-result"></div>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>🎭 Brand Voice & Personality</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Personality Preset</th>
                                <td>
                                    <select name="<?php echo $this->option_name; ?>[personality_preset]">
                                        <option value="professional" <?php selected($settings['personality_preset'], 'professional'); ?>>Professional</option>
                                        <option value="friendly" <?php selected($settings['personality_preset'], 'friendly'); ?>>Friendly</option>
                                        <option value="humorous" <?php selected($settings['personality_preset'], 'humorous'); ?>>Humorous</option>
                                        <option value="technical" <?php selected($settings['personality_preset'], 'technical'); ?>>Technical</option>
                                        <option value="custom" <?php selected($settings['personality_preset'], 'custom'); ?>>Custom (use brand voice sample)</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Brand Voice Sample</th>
                                <td>
                                    <textarea name="<?php echo $this->option_name; ?>[brand_voice_sample]" 
                                              rows="8" cols="50" class="large-text"
                                              placeholder="Paste 2-3 examples of your brand's writing style here..."><?php echo esc_textarea($settings['brand_voice_sample']); ?></textarea>
                                    <p class="description">
                                        <strong>Examples of good samples:</strong><br>
                                        • Your About page copy<br>
                                        • Product descriptions<br>
                                        • Email newsletter content<br>
                                        • Customer service responses
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>⚙️ Message Preferences</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Message Length</th>
                                <td>
                                    <select name="<?php echo $this->option_name; ?>[message_length]">
                                        <option value="brief" <?php selected($settings['message_length'], 'brief'); ?>>Brief</option>
                                        <option value="standard" <?php selected($settings['message_length'], 'standard'); ?>>Standard</option>
                                        <option value="detailed" <?php selected($settings['message_length'], 'detailed'); ?>>Detailed</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Include Emoji</th>
                                <td>
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[include_emoji]" 
                                           value="1" <?php checked($settings['include_emoji'], 1); ?> />
                                    <label>Add emoji to make messages more friendly</label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Fallback Message</th>
                                <td>
                                    <textarea name="<?php echo $this->option_name; ?>[fallback_message]" 
                                              rows="3" cols="50" class="large-text"><?php echo esc_textarea($settings['fallback_message']); ?></textarea>
                                    <p class="description">Used when AI is unavailable</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle API provider changes
            $('select[name="<?php echo $this->option_name; ?>[api_provider]"]').change(function() {
                updateApiHelp($(this).val());
                updateModelOptions($(this).val());
            });
            
            // Initialize on page load
            var currentProvider = $('select[name="<?php echo $this->option_name; ?>[api_provider]"]').val();
            updateApiHelp(currentProvider);
            updateModelOptions(currentProvider);
            
            function updateApiHelp(provider) {
                var helpText = '';
                var placeholder = '';
                
                switch(provider) {
                    case 'openai':
                        helpText = 'Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>';
                        placeholder = 'sk-...';
                        break;
                    case 'anthropic':
                        helpText = 'Get your API key from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>';
                        placeholder = 'sk-ant-...';
                        break;
                    case 'gemini':
                        helpText = 'Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>';
                        placeholder = 'AIza...';
                        break;
                }
                
                $('#api-key-help').html(helpText);
                $('input[name="<?php echo $this->option_name; ?>[api_key]"]').attr('placeholder', placeholder);
            }
            
            function updateModelOptions(provider) {
                var modelSelect = $('#model-select');
                var options = modelSelect.find('option');
                
                options.hide();
                options.filter('[data-provider="' + provider + '"]').show();
                
                // Select first visible option if current selection is hidden
                var currentOption = modelSelect.find('option:selected');
                if (currentOption.attr('data-provider') !== provider) {
                    var firstVisible = modelSelect.find('option[data-provider="' + provider + '"]:first');
                    if (firstVisible.length) {
                        modelSelect.val(firstVisible.val());
                    }
                }
            }
            
            // Test API connection
            $('#test-api').click(function() {
                var button = $(this);
                var result = $('#api-test-result');
                
                button.prop('disabled', true).text('Testing...');
                result.html('<p>Testing API connection...</p>');
                
                $.post(ajaxurl, {
                    action: 'test_smart_404_api',
                    nonce: '<?php echo wp_create_nonce('smart_404_test'); ?>'
                }, function(response) {
                    if (response.success) {
                        result.html('<p style="color: green;">✅ API connection successful!</p>');
                    } else {
                        result.html('<p style="color: red;">❌ API connection failed: ' + response.data + '</p>');
                    }
                    button.prop('disabled', false).text('Test API Connection');
                });
            });
        });
        </script>
        <?php
    }
    
    public function analytics_page() {
        $analytics = get_option($this->analytics_option, array());
        
        // Handle bulk actions
        if (isset($_POST['bulk_action']) && isset($_POST['selected_404s'])) {
            $this->handle_bulk_actions($_POST['bulk_action'], $_POST['selected_404s']);
            $analytics = get_option($this->analytics_option, array()); // Refresh data
        }
        
        // Handle individual 404 updates
        if (isset($_POST['update_404'])) {
            $this->handle_404_update($_POST);
            $analytics = get_option($this->analytics_option, array()); // Refresh data
        }
        
        // Sort by hits (most problematic first)
        uasort($analytics, function($a, $b) {
            return $b['hits'] - $a['hits'];
        });
        
        ?>
        <div class="wrap">
            <h1>Smart 404 Analytics & Management</h1>
            
            <?php if (empty($analytics)): ?>
                <div class="notice notice-info">
                    <p>No 404 errors logged yet. This dashboard will populate as users encounter broken links on your site.</p>
                </div>
            <?php else: ?>
                
                <!-- Quick Stats -->
                <div class="postbox" style="margin-top: 20px;">
                    <div class="postbox-header">
                        <h2>📊 Quick Stats</h2>
                    </div>
                    <div class="inside">
                        <?php
                        $total_404s = count($analytics);
                        $total_hits = array_sum(array_column($analytics, 'hits'));
                        $unresolved = count(array_filter($analytics, function($a) { return $a['status'] === 'unresolved'; }));
                        $success_rate = 0;
                        $total_clicks = array_sum(array_column($analytics, 'clicks'));
                        if ($total_hits > 0) {
                            $success_rate = round(($total_clicks / $total_hits) * 100, 1);
                        }
                        ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div class="stat-box" style="background: #f0f6fc; padding: 15px; border-radius: 6px;">
                                <h3 style="margin: 0 0 5px 0;">Total 404s</h3>
                                <span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $total_404s; ?></span>
                            </div>
                            <div class="stat-box" style="background: #fff2e5; padding: 15px; border-radius: 6px;">
                                <h3 style="margin: 0 0 5px 0;">Total Hits</h3>
                                <span style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo number_format($total_hits); ?></span>
                            </div>
                            <div class="stat-box" style="background: #f0f6fc; padding: 15px; border-radius: 6px;">
                                <h3 style="margin: 0 0 5px 0;">Unresolved</h3>
                                <span style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo $unresolved; ?></span>
                            </div>
                            <div class="stat-box" style="background: <?php echo $success_rate > 50 ? '#e7f5e7' : '#fff2e5'; ?>; padding: 15px; border-radius: 6px;">
                                <h3 style="margin: 0 0 5px 0;">Success Rate</h3>
                                <span style="font-size: 24px; font-weight: bold; color: <?php echo $success_rate > 50 ? '#008a00' : '#d63638'; ?>;"><?php echo $success_rate; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Management Tools -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>🔧 404 Management</h2>
                    </div>
                    <div class="inside">
                        <form method="post" id="404-management-form">
                            <div class="tablenav top">
                                <div class="alignleft actions bulkactions">
                                    <select name="bulk_action">
                                        <option value="">Bulk Actions</option>
                                        <option value="mark_fixed">Mark as Fixed</option>
                                        <option value="mark_redirected">Mark as Redirected</option>
                                        <option value="mark_ignored">Mark as Ignored</option>
                                        <option value="mark_unresolved">Mark as Unresolved</option>
                                        <option value="delete">Delete from Log</option>
                                    </select>
                                    <input type="submit" class="button action" value="Apply">
                                </div>
                                <div class="alignright actions">
                                    <a href="?page=smart-404-ai&export=csv" class="button">Export CSV</a>
                                    <button type="button" class="button" onclick="location.reload()">Refresh</button>
                                </div>
                            </div>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <td class="manage-column column-cb check-column">
                                            <input type="checkbox" id="cb-select-all">
                                        </td>
                                        <th>Broken URL</th>
                                        <th>Hits</th>
                                        <th>Clicks</th>
                                        <th>Success Rate</th>
                                        <th>Status</th>
                                        <th>Top Referrer</th>
                                        <th>First/Last Seen</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics as $url => $data): ?>
                                    <tr class="404-row" data-url="<?php echo esc_attr($url); ?>">
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" name="selected_404s[]" value="<?php echo esc_attr($url); ?>">
                                        </th>
                                        <td>
                                            <strong><code><?php echo esc_html($url); ?></code></strong>
                                            <?php if (!empty($data['notes'])): ?>
                                            <br><small style="color: #666;">📝 <?php echo esc_html($data['notes']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: #d63638;"><?php echo intval($data['hits']); ?></strong>
                                        </td>
                                        <td><?php echo intval($data['clicks']); ?></td>
                                        <td>
                                            <?php 
                                            $rate = $data['hits'] > 0 ? round(($data['clicks'] / $data['hits']) * 100, 1) : 0;
                                            $color = $rate > 50 ? '#008a00' : ($rate > 20 ? '#ffb900' : '#d63638');
                                            echo "<span style='color: {$color}; font-weight: bold;'>{$rate}%</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = array(
                                                'unresolved' => '#d63638',
                                                'fixed' => '#008a00', 
                                                'redirected' => '#0073aa',
                                                'ignored' => '#666'
                                            );
                                            $status = $data['status'];
                                            $color = $status_colors[$status];
                                            echo "<span style='color: {$color}; font-weight: bold;'>" . ucfirst($status) . "</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($data['referrers'])) {
                                                $top_referrer = array_keys($data['referrers'], max($data['referrers']))[0];
                                                $count = max($data['referrers']);
                                                $domain = parse_url($top_referrer, PHP_URL_HOST);
                                                echo "<small title='{$top_referrer}'>{$domain} ({$count})</small>";
                                            } else {
                                                echo "<small>Direct/Unknown</small>";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <small>
                                                First: <?php echo date('M j', $data['first_seen']); ?><br>
                                                Last: <?php echo date('M j, g:i A', $data['last_seen']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small toggle-details" 
                                                    data-url="<?php echo esc_attr($url); ?>">Details</button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Hidden details row -->
                                    <tr class="404-details" id="details-<?php echo md5($url); ?>" style="display: none;">
                                        <td colspan="9">
                                            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                                                <form method="post" style="display: inline-block; width: 100%;">
                                                    <input type="hidden" name="update_404" value="1">
                                                    <input type="hidden" name="404_url" value="<?php echo esc_attr($url); ?>">
                                                    
                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                                        <div>
                                                            <h4>Status & Notes</h4>
                                                            <p>
                                                                <label>Status: </label>
                                                                <select name="status">
                                                                    <option value="unresolved" <?php selected($data['status'], 'unresolved'); ?>>Unresolved</option>
                                                                    <option value="fixed" <?php selected($data['status'], 'fixed'); ?>>Fixed</option>
                                                                    <option value="redirected" <?php selected($data['status'], 'redirected'); ?>>Redirected</option>
                                                                    <option value="ignored" <?php selected($data['status'], 'ignored'); ?>>Ignored</option>
                                                                </select>
                                                            </p>
                                                            <p>
                                                                <label>Notes: </label><br>
                                                                <textarea name="notes" rows="3" style="width: 100%;"><?php echo esc_textarea($data['notes']); ?></textarea>
                                                            </p>
                                                            <p>
                                                                <label>Suggested Fix: </label><br>
                                                                <input type="text" name="suggested_fix" value="<?php echo esc_attr($data['suggested_fix']); ?>" 
                                                                       style="width: 100%;" placeholder="e.g., Redirect to /new-page">
                                                            </p>
                                                            <button type="submit" class="button button-primary">Update</button>
                                                        </div>
                                                        <div>
                                                            <h4>Referrer Analysis</h4>
                                                            <?php if (!empty($data['referrers'])): ?>
                                                                <ul style="margin: 0; font-size: 12px;">
                                                                <?php foreach ($data['referrers'] as $ref => $count): ?>
                                                                    <li><strong><?php echo $count; ?>x</strong> from <?php echo esc_html(parse_url($ref, PHP_URL_HOST)); ?></li>
                                                                <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <p><em>No referrer data available</em></p>
                                                            <?php endif; ?>
                                                            
                                                            <h4 style="margin-top: 15px;">Quick Actions</h4>
                                                            <p>
                                                                <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" 
                                                                   class="button button-small">Create New Page</a>
                                                                <a href="<?php echo admin_url('options-permalink.php'); ?>" 
                                                                   class="button button-small">Manage Redirects</a>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Select all checkbox
            $('#cb-select-all').change(function() {
                $('input[name="selected_404s[]"]').prop('checked', this.checked);
            });
            
            // Toggle details
            $('.toggle-details').click(function() {
                var url = $(this).data('url');
                var detailsId = '#details-' + btoa(url).replace(/[^a-zA-Z0-9]/g, '');
                $(detailsId).toggle();
                $(this).text($(detailsId).is(':visible') ? 'Hide' : 'Details');
            });
        });
        </script>
        
        <style>
        .stat-box h3 {
            font-size: 14px;
            margin: 0 0 5px 0;
        }
        .404-details td {
            border-top: none !important;
        }
        .wp-list-table .column-cb {
            width: 2.2em;
        }
        </style>
        <?php
    }
    
    private function handle_bulk_actions($action, $selected_urls) {
        if (empty($selected_urls) || !is_array($selected_urls)) {
            return;
        }
        
        $analytics = get_option($this->analytics_option, array());
        
        foreach ($selected_urls as $url) {
            if (!isset($analytics[$url])) continue;
            
            switch ($action) {
                case 'mark_fixed':
                    $analytics[$url]['status'] = 'fixed';
                    break;
                case 'mark_redirected':
                    $analytics[$url]['status'] = 'redirected';
                    break;
                case 'mark_ignored':
                    $analytics[$url]['status'] = 'ignored';
                    break;
                case 'mark_unresolved':
                    $analytics[$url]['status'] = 'unresolved';
                    break;
                case 'delete':
                    unset($analytics[$url]);
                    break;
            }
        }
        
        update_option($this->analytics_option, $analytics);
        
        $count = count($selected_urls);
        echo "<div class='notice notice-success'><p>{$count} items updated successfully.</p></div>";
    }
    
    private function handle_404_update($post_data) {
        $url = sanitize_text_field($post_data['404_url']);
        $analytics = get_option($this->analytics_option, array());
        
        if (isset($analytics[$url])) {
            $analytics[$url]['status'] = sanitize_text_field($post_data['status']);
            $analytics[$url]['notes'] = sanitize_textarea_field($post_data['notes']);
            $analytics[$url]['suggested_fix'] = sanitize_text_field($post_data['suggested_fix']);
            
            update_option($this->analytics_option, $analytics);
            echo "<div class='notice notice-success'><p>404 entry updated successfully.</p></div>";
        }
    }
    
    public function handle_404() {
        if (!is_404()) {
            return;
        }
        
        // Track this 404
        $this->track_404(get_query_var('name'));
        
        // Get smart suggestions and AI message
        $suggestions = $this->get_content_suggestions();
        $ai_message = $this->generate_ai_message($suggestions);
        
        // Load custom 404 template
        $this->load_404_template($ai_message, $suggestions);
    }
    
    private function get_content_suggestions() {
        $broken_url = get_query_var('name');
        if (empty($broken_url)) {
            $broken_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $broken_url = trim($broken_url, '/');
        }
        
        // Extract keywords from URL
        $keywords = $this->extract_keywords($broken_url);
        
        if (empty($keywords)) {
            return array();
        }
        
        // Search for relevant content
        $search_query = new WP_Query(array(
            's' => implode(' ', $keywords),
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'relevance'
        ));
        
        $suggestions = array();
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                $relevance_score = $this->calculate_relevance_score(get_the_title(), get_the_content(), $keywords);
                
                $suggestions[] = array(
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => get_the_excerpt(),
                    'relevance' => $relevance_score
                );
            }
            wp_reset_postdata();
        }
        
        // Sort by relevance
        usort($suggestions, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return array_slice($suggestions, 0, 3);
    }
    
    private function extract_keywords($url) {
        // Remove common URL parts
        $url = str_replace(array('-', '_', '/', '.html', '.php'), ' ', $url);
        
        // Remove year patterns (2020-2025)
        $url = preg_replace('/\b20[2-9][0-9]\b/', '', $url);
        
        // Split into words
        $words = explode(' ', $url);
        
        // Filter out common stop words and short words
        $stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'blog', 'page', 'post');
        $keywords = array();
        
        foreach ($words as $word) {
            $word = trim(strtolower($word));
            if (strlen($word) > 2 && !in_array($word, $stop_words) && !is_numeric($word)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    private function calculate_relevance_score($title, $content, $keywords) {
        $score = 0;
        $title_lower = strtolower($title);
        $content_lower = strtolower($content);
        
        foreach ($keywords as $keyword) {
            // Title matches are worth more
            if (strpos($title_lower, $keyword) !== false) {
                $score += 10;
            }
            
            // Content matches
            $content_matches = substr_count($content_lower, $keyword);
            $score += $content_matches * 2;
        }
        
        return $score;
    }
    
    private function generate_ai_message($suggestions) {
        $settings = get_option($this->option_name);
        
        if (empty($settings['api_key']) || empty($suggestions)) {
            return $settings['fallback_message'];
        }
        
        $prompt = $this->build_ai_prompt($suggestions, $settings);
        
        if ($settings['api_provider'] === 'openai') {
            return $this->call_openai($prompt, $settings);
        } elseif ($settings['api_provider'] === 'anthropic') {
            return $this->call_anthropic($prompt, $settings);
        } elseif ($settings['api_provider'] === 'gemini') {
            return $this->call_gemini($prompt, $settings);
        }
        
        return $settings['fallback_message'];
    }
    
    private function call_gemini($prompt, $settings) {
        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/' . $settings['model'] . ':generateContent?key=' . $settings['api_key'], array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 150,
                    'topP' => 0.8,
                    'topK' => 10
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $settings['fallback_message'];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($body['candidates'][0]['content']['parts'][0]['text']);
        }
        
        return $settings['fallback_message'];
    }
    
    private function build_ai_prompt($suggestions, $settings) {
        $personality_prompts = array(
            'professional' => 'Write in a professional, helpful tone suitable for business communication.',
            'friendly' => 'Write in a warm, friendly, conversational tone.',
            'humorous' => 'Write with gentle humor and personality, but keep it helpful.',
            'technical' => 'Write with technical references and developer-friendly language.',
            'custom' => 'Match the exact tone and style of this brand voice sample: ' . $settings['brand_voice_sample']
        );
        
        $length_instructions = array(
            'brief' => 'Keep it very concise - 1-2 sentences maximum.',
            'standard' => 'Use 2-3 sentences.',
            'detailed' => 'Write 3-4 sentences with more context.'
        );
        
        $emoji_instruction = $settings['include_emoji'] ? 'Include relevant emoji to make it more engaging.' : 'Do not use emoji.';
        
        $prompt = "You are writing a 404 error message for a website. ";
        $prompt .= $personality_prompts[$settings['personality_preset']] . " ";
        $prompt .= $length_instructions[$settings['message_length']] . " ";
        $prompt .= $emoji_instruction . "\n\n";
        $prompt .= "Write ONLY the introductory message that will appear before the content suggestions. ";
        $prompt .= "Do not include the actual suggestions or links - just the intro text.\n\n";
        $prompt .= "The suggestions found are:\n";
        
        foreach ($suggestions as $suggestion) {
            $prompt .= "- " . $suggestion['title'] . "\n";
        }
        
        return $prompt;
    }
    
    private function call_openai($prompt, $settings) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $settings['api_key'],
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $settings['model'],
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 150,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $settings['fallback_message'];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return trim($body['choices'][0]['message']['content']);
        }
        
        return $settings['fallback_message'];
    }
    
    private function call_anthropic($prompt, $settings) {
        // Anthropic API implementation
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $settings['api_key'],
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $settings['model'],
                'max_tokens' => 150,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $settings['fallback_message'];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['content'][0]['text'])) {
            return trim($body['content'][0]['text']);
        }
        
        return $settings['fallback_message'];
    }
    
    private function load_404_template($ai_message, $suggestions) {
        // Override the 404 template
        status_header(404);
        nocache_headers();
        
        get_header();
        ?>
        <div class="smart-404-container">
            <div class="smart-404-content">
                <h1 class="smart-404-title">Page Not Found</h1>
                
                <div class="smart-404-message">
                    <?php echo wp_kses_post(wpautop($ai_message)); ?>
                </div>
                
                <?php if (!empty($suggestions)): ?>
                <div class="smart-404-suggestions">
                    <h2>Here's what we found for you:</h2>
                    <ul class="suggestion-list">
                        <?php foreach ($suggestions as $index => $suggestion): ?>
                        <li class="suggestion-item">
                            <h3><a href="<?php echo esc_url($suggestion['url']); ?>" 
                                   data-suggestion="<?php echo $index; ?>"
                                   class="suggestion-link">
                                <?php echo esc_html($suggestion['title']); ?>
                            </a></h3>
                            <?php if (!empty($suggestion['excerpt'])): ?>
                            <p class="suggestion-excerpt"><?php echo esc_html($suggestion['excerpt']); ?></p>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="smart-404-actions">
                    <a href="<?php echo home_url(); ?>" class="button button-primary">Go to Homepage</a>
                    <button type="button" class="button" id="report-broken-link">Report Broken Link</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.suggestion-link').click(function() {
                var suggestionIndex = $(this).data('suggestion');
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'smart_404_track',
                    url: '<?php echo esc_js($_SERVER['REQUEST_URI']); ?>',
                    suggestion: suggestionIndex,
                    nonce: '<?php echo wp_create_nonce('smart_404_track'); ?>'
                });
            });
        });
        </script>
        <?php
        get_footer();
        exit;
    }
    
    public function enqueue_styles() {
        if (is_404()) {
            wp_add_inline_style('wp-admin', '
                .smart-404-container {
                    max-width: 800px;
                    margin: 40px auto;
                    padding: 20px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                }
                
                .smart-404-title {
                    font-size: 2.5em;
                    color: #333;
                    margin-bottom: 20px;
                }
                
                .smart-404-message {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #007cba;
                    margin: 20px 0;
                    font-size: 1.1em;
                    line-height: 1.6;
                }
                
                .suggestion-list {
                    list-style: none;
                    padding: 0;
                }
                
                .suggestion-item {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    padding: 15px;
                    margin: 10px 0;
                    transition: box-shadow 0.2s;
                }
                
                .suggestion-item:hover {
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .suggestion-link {
                    color: #007cba;
                    text-decoration: none;
                    font-weight: 600;
                }
                
                .suggestion-link:hover {
                    text-decoration: underline;
                }
                
                .suggestion-excerpt {
                    color: #666;
                    margin: 8px 0 0 0;
                    line-height: 1.4;
                }
                
                .smart-404-actions {
                    margin-top: 30px;
                    text-align: center;
                }
                
                .smart-404-actions .button {
                    margin: 0 10px;
                    padding: 10px 20px;
                    font-size: 16px;
                }
            ');
        }
    }
    
    private function track_404($url) {
        $analytics = get_option($this->analytics_option, array());
        
        // Get detailed information about this 404
        $request_uri = $_SERVER['REQUEST_URI'];
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ip_address = $this->get_client_ip();
        
        if (!isset($analytics[$request_uri])) {
            $analytics[$request_uri] = array(
                'hits' => 0,
                'clicks' => 0,
                'first_seen' => time(),
                'last_seen' => 0,
                'referrers' => array(),
                'user_agents' => array(),
                'status' => 'unresolved', // unresolved, fixed, redirected, ignored
                'notes' => '',
                'suggested_fix' => $this->auto_suggest_fix($request_uri)
            );
        }
        
        $analytics[$request_uri]['hits']++;
        $analytics[$request_uri]['last_seen'] = time();
        
        // Track referrers (where users are coming from)
        if (!empty($referrer)) {
            if (!isset($analytics[$request_uri]['referrers'][$referrer])) {
                $analytics[$request_uri]['referrers'][$referrer] = 0;
            }
            $analytics[$request_uri]['referrers'][$referrer]++;
        }
        
        // Track user agents (simplified)
        $simplified_ua = $this->simplify_user_agent($user_agent);
        if (!isset($analytics[$request_uri]['user_agents'][$simplified_ua])) {
            $analytics[$request_uri]['user_agents'][$simplified_ua] = 0;
        }
        $analytics[$request_uri]['user_agents'][$simplified_ua]++;
        
        update_option($this->analytics_option, $analytics);
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Smart 404 AI: 404 hit on {$request_uri} from referrer: {$referrer}");
        }
    }
    
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }
    
    private function simplify_user_agent($user_agent) {
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'bot') !== false || strpos($user_agent, 'Bot') !== false) return 'Bot/Crawler';
        return 'Other';
    }
    
    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'smart_404_track')) {
            wp_die('Security check failed');
        }
        
        $url = sanitize_text_field($_POST['url']);
        $analytics = get_option($this->analytics_option, array());
        
        if (isset($analytics[$url])) {
            $analytics[$url]['clicks']++;
            update_option($this->analytics_option, $analytics);
        }
        
        wp_send_json_success();
    }
    
    public function handle_export() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'smart-404-ai') {
            return;
        }
        
        if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $analytics = get_option($this->analytics_option, array());
        
        if (empty($analytics)) {
            wp_die('No 404 data to export');
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="404-errors-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'URL',
            'Hits',
            'Clicks', 
            'Success Rate (%)',
            'Status',
            'Notes',
            'Suggested Fix',
            'First Seen',
            'Last Seen',
            'Top Referrer',
            'Referrer Hits'
        ));
        
        // CSV data
        foreach ($analytics as $url => $data) {
            $success_rate = $data['hits'] > 0 ? round(($data['clicks'] / $data['hits']) * 100, 1) : 0;
            
            $top_referrer = '';
            $referrer_hits = 0;
            if (!empty($data['referrers'])) {
                $top_referrer = array_keys($data['referrers'], max($data['referrers']))[0];
                $referrer_hits = max($data['referrers']);
            }
            
            fputcsv($output, array(
                $url,
                $data['hits'],
                $data['clicks'],
                $success_rate,
                ucfirst($data['status']),
                $data['notes'],
                $data['suggested_fix'], 
                date('Y-m-d H:i:s', $data['first_seen']),
                date('Y-m-d H:i:s', $data['last_seen']),
                $top_referrer,
                $referrer_hits
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public function test_api_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'smart_404_test')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = get_option($this->option_name);
        
        if (empty($settings['api_key'])) {
            wp_send_json_error('No API key configured');
        }
        
        // Test with a simple prompt
        $test_prompt = "Write a brief, friendly 404 error message in one sentence.";
        
        if ($settings['api_provider'] === 'openai') {
            $result = $this->test_openai_connection($settings, $test_prompt);
        } elseif ($settings['api_provider'] === 'anthropic') {
            $result = $this->test_anthropic_connection($settings, $test_prompt);
        } elseif ($settings['api_provider'] === 'gemini') {
            $result = $this->test_gemini_connection($settings, $test_prompt);
        } else {
            wp_send_json_error('Unknown API provider');
        }
        
        if ($result['success']) {
            wp_send_json_success('API connection successful! Test response: ' . $result['message']);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    private function test_openai_connection($settings, $prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $settings['api_key'],
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $settings['model'],
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 50,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array('success' => false, 'error' => "HTTP {$status_code}: " . wp_remote_retrieve_body($response));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return array('success' => true, 'message' => trim($body['choices'][0]['message']['content']));
        }
        
        return array('success' => false, 'error' => 'Unexpected API response format');
    }
    
    private function test_gemini_connection($settings, $prompt) {
        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/' . $settings['model'] . ':generateContent?key=' . $settings['api_key'], array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 50,
                    'topP' => 0.8,
                    'topK' => 10
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $error_body = wp_remote_retrieve_body($response);
            $error_data = json_decode($error_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : $error_body;
            return array('success' => false, 'error' => "HTTP {$status_code}: " . $error_message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return array('success' => true, 'message' => trim($body['candidates'][0]['content']['parts'][0]['text']));
        }
        
        return array('success' => false, 'error' => 'Unexpected API response format');
    }
    
    private function auto_suggest_fix($broken_url) {
        // Auto-suggest potential fixes based on URL patterns
        
        // Check if it looks like an old dated post
        if (preg_match('/\/(\d{4})\//', $broken_url, $matches)) {
            $year = $matches[1];
            $current_year = date('Y');
            if ($year < $current_year) {
                return "Check for updated {$current_year} version of this content";
            }
        }
        
        // Check for common URL patterns
        if (strpos($broken_url, '/blog/') !== false) {
            return "Search blog posts for similar content or create new blog post";
        }
        
        if (strpos($broken_url, '/products/') !== false || strpos($broken_url, '/shop/') !== false) {
            return "Check if product was moved or discontinued, setup redirect";
        }
        
        if (strpos($broken_url, '/category/') !== false || strpos($broken_url, '/tag/') !== false) {
            return "Check if category/tag was renamed or merged";
        }
        
        if (strpos($broken_url, '.html') !== false || strpos($broken_url, '.php') !== false) {
            return "Setup redirect from old file-based URL to new WordPress permalink";
        }
        
        // Look for existing similar content
        $keywords = $this->extract_keywords($broken_url);
        if (!empty($keywords)) {
            $search_query = new WP_Query(array(
                's' => implode(' ', array_slice($keywords, 0, 2)), // Use first 2 keywords
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'posts_per_page' => 1
            ));
            
            if ($search_query->have_posts()) {
                $search_query->the_post();
                $suggested_url = get_permalink();
                wp_reset_postdata();
                return "Consider redirecting to: {$suggested_url}";
            }
        }
        
        return "Review content and setup appropriate redirect or create new page";
    }
    
    private function test_anthropic_connection($settings, $prompt) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $settings['api_key'],
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $settings['model'],
                'max_tokens' => 50,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array('success' => false, 'error' => "HTTP {$status_code}: " . wp_remote_retrieve_body($response));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['content'][0]['text'])) {
            return array('success' => true, 'message' => trim($body['content'][0]['text']));
        }
        
        return array('success' => false, 'error' => 'Unexpected API response format');
    }
}

// Initialize the plugin
new Smart404AI();
?>