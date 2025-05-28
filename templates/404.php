<?php
/**
 * Smart404AI Template
 * File: templates/404.php
 */

get_header(); 

$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

// Get AI analysis
$smart404ai = new Smart404AI();
$ai_analysis = $smart404ai->analyze_broken_url($current_url, $referrer);
?>

<div class="smart404ai-container">
    <div class="smart404ai-header">
        <?php if (isset($ai_analysis['fun_title'])): ?>
            <h1 class="smart404ai-title">
                <span class="icon-error"></span>
                <?php echo esc_html($ai_analysis['fun_title']); ?>
            </h1>
        <?php else: ?>
            <h1 class="smart404ai-title">
                <span class="icon-error"></span>
                Oops! Page Not Found
            </h1>
        <?php endif; ?>
        
        <?php if (isset($ai_analysis['fun_message'])): ?>
            <div class="smart404ai-subtitle"><?php echo esc_html($ai_analysis['fun_message']); ?></div>
        <?php else: ?>
            <div class="smart404ai-subtitle">But our AI assistant is here to help you find what you're looking for!</div>
        <?php endif; ?>
    </div>

    <div class="smart404ai-content">
        
        <!-- Smart Suggestions Section -->
        <?php if (isset($ai_analysis['suggestions']) && !empty($ai_analysis['suggestions'])): ?>
        <div class="ai-suggestions-section">
            <h2><span class="icon-target"></span> Smart Suggestions</h2>
            <div class="suggestions-grid">
                <?php 
                // Limit to 4 suggestions max
                $suggestions = array_slice($ai_analysis['suggestions'], 0, 4);
                foreach ($suggestions as $suggestion): ?>
                    <div class="suggestion-card">
                        <h3><a href="<?php echo esc_url($suggestion['url']); ?>"><?php echo esc_html($suggestion['title']); ?></a></h3>
                        <p class="suggestion-reason"><?php echo esc_html($suggestion['reason']); ?></p>
                        <a href="<?php echo esc_url($suggestion['url']); ?>" class="suggestion-link">Visit Page →</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- AI Chat Assistant -->
        <?php 
        $smart404ai_options = get_option('smart404ai_options', array());
        $enable_chat = isset($smart404ai_options['enable_chat']) ? $smart404ai_options['enable_chat'] : 1;
        if ($enable_chat): 
        ?>
        <div class="ai-chat-section">
            <h2><span class="icon-chat"></span> Ask Our AI Assistant</h2>
            <div class="ai-chat-container">
                <div class="chat-header">
                    <span class="chat-title">AI Assistant</span>
                    <button type="button" id="expand-chat" class="expand-chat-btn" title="Expand Chat">
                        <span class="icon-expand"></span>
                        <span class="expand-text">Expand</span>
                    </button>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="ai-message">
                        <div class="message-avatar"><span class="icon-bot"></span></div>
                        <div class="message-content">
                            <p>Hi there! I'm your AI assistant. I can help you find what you're looking for on this site, or we can just chat about what brought you here. What were you hoping to find?</p>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <input type="text" id="chat-input" placeholder="Ask me anything about this site..." />
                    <button id="chat-send" type="button">Send</button>
                </div>
                
                <div class="chat-suggestions">
                    <p>Try asking:</p>
                    <button class="suggestion-btn" data-question="What are your most popular posts?">Most popular posts</button>
                    <button class="suggestion-btn" data-question="Show me your latest articles">Latest articles</button>
                    <button class="suggestion-btn" data-question="Where is your contact page?">Contact information</button>
                </div>
            </div>
        </div>

        <!-- Expanded Chat Overlay -->
        <div id="chat-overlay" class="chat-overlay" style="display: none;">
            <div class="chat-overlay-content">
                <div class="chat-overlay-header">
                    <h3><span class="icon-chat"></span> AI Assistant</h3>
                    <button type="button" id="close-chat-overlay" class="close-overlay-btn">
                        <span class="icon-close"></span>
                    </button>
                </div>
                <div class="chat-overlay-messages" id="chat-overlay-messages">
                    <!-- Messages will be synced here -->
                </div>
                <div class="chat-overlay-input">
                    <input type="text" id="chat-overlay-input" placeholder="Ask me anything about this site..." />
                    <button id="chat-overlay-send" type="button">Send</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Technical Details (Collapsible) -->
        <div class="technical-details">
            <details>
                <summary><span class="icon-settings"></span> Technical Details</summary>
                <div class="tech-info">
                    <p><strong>Broken URL:</strong> <code><?php echo esc_html($current_url); ?></code></p>
                    <?php if ($referrer): ?>
                        <p><strong>Came from:</strong> <code><?php echo esc_html($referrer); ?></code></p>
                    <?php endif; ?>
                    <p><strong>AI Model:</strong> Google Gemini 1.5 Flash</p>
                    <p><strong>Analysis Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </details>
        </div>

        <!-- Fallback Navigation -->
        <div class="fallback-navigation">
            <h3>Or browse our main sections:</h3>
            <div class="nav-links">
                <a href="<?php echo home_url(); ?>" class="nav-button"><span class="icon-home"></span> Home</a>
                <a href="<?php echo home_url('/blog'); ?>" class="nav-button"><span class="icon-blog"></span> Blog</a>
                <?php
                $pages = get_pages(array('sort_column' => 'menu_order', 'number' => 5));
                foreach ($pages as $page):
                ?>
                    <a href="<?php echo get_permalink($page->ID); ?>" class="nav-button">
                        <?php echo esc_html($page->post_title); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<!-- Loading indicator -->
<div class="ai-loading" id="ai-loading" style="display: none;">
    <div class="loading-spinner"></div>
    <p>AI is thinking...</p>
</div>

<?php get_footer(); ?>