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

<div class="ai-404-container">
    <div class="ai-404-header">
        <h1 class="ai-404-title">🤖 Oops! Page Not Found</h1>
        <div class="ai-404-subtitle">But our AI assistant is here to help you find what you're looking for!</div>
    </div>

    <div class="ai-404-content">
        
        <!-- AI Analysis Section -->
        <div class="ai-analysis-section">
            <h2>🧠 AI Analysis</h2>
            <div class="ai-analysis-box">
                <?php if (isset($ai_analysis['analysis'])): ?>
                    <p><strong>What you were likely looking for:</strong> <?php echo esc_html($ai_analysis['analysis']); ?></p>
                <?php endif; ?>
                
                <?php if (isset($ai_analysis['message'])): ?>
                    <div class="ai-message">
                        <p><?php echo esc_html($ai_analysis['message']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Smart Suggestions Section -->
        <?php if (isset($ai_analysis['suggestions']) && !empty($ai_analysis['suggestions'])): ?>
        <div class="ai-suggestions-section">
            <h2>🎯 Smart Suggestions</h2>
            <div class="suggestions-grid">
                <?php foreach ($ai_analysis['suggestions'] as $suggestion): ?>
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
        <div class="ai-chat-section">
            <h2>💬 Ask Our AI Assistant</h2>
            <div class="ai-chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="ai-message">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            <p>Hi! I'm your AI assistant. I can help you find what you're looking for on this site. What were you hoping to find?</p>
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

        <!-- Technical Details (Collapsible) -->
        <div class="technical-details">
            <details>
                <summary>🔧 Technical Details</summary>
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
                <a href="<?php echo home_url(); ?>" class="nav-button">🏠 Home</a>
                <a href="<?php echo home_url('/blog'); ?>" class="nav-button">📝 Blog</a>
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