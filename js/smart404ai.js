/**
 * Smart404AI JavaScript
 * File: js/smart404ai.js
 */

jQuery(document).ready(function($) {
    
    // Chat functionality
    const chatMessages = $('#chat-messages');
    const chatInput = $('#chat-input');
    const chatSend = $('#chat-send');
    const loadingIndicator = $('#ai-loading');
    
    // Overlay elements
    const chatOverlay = $('#chat-overlay');
    const chatOverlayMessages = $('#chat-overlay-messages');
    const chatOverlayInput = $('#chat-overlay-input');
    const chatOverlaySend = $('#chat-overlay-send');
    const expandChatBtn = $('#expand-chat');
    const closeChatBtn = $('#close-chat-overlay');
    
    // Expandable chat functionality
    expandChatBtn.click(function() {
        // Sync messages to overlay
        chatOverlayMessages.html(chatMessages.html());
        chatOverlay.fadeIn(300);
        chatOverlayInput.focus();
    });
    
    closeChatBtn.click(function() {
        chatOverlay.fadeOut(300);
    });
    
    // Close overlay on background click
    chatOverlay.click(function(e) {
        if (e.target === this) {
            chatOverlay.fadeOut(300);
        }
    });
    
    // Close overlay on Escape key
    $(document).keydown(function(e) {
        if (e.key === 'Escape' && chatOverlay.is(':visible')) {
            chatOverlay.fadeOut(300);
        }
    });
    
    // Sync input between regular and overlay chat
    function syncChatInputs(sourceInput, targetInput) {
        targetInput.val(sourceInput.val());
    }
    
    chatInput.on('input', function() {
        syncChatInputs($(this), chatOverlayInput);
    });
    
    chatOverlayInput.on('input', function() {
        syncChatInputs($(this), chatInput);
    });
    
    // Send message function
    function sendMessage(message) {
        if (!message.trim()) return;
        
        // Add user message to chat
        addMessageToChatWithTyping('user', message);
        
        // Clear input
        chatInput.val('');
        
        // Show loading
        showLoading();
        
        // Send to AI
        $.ajax({
            url: smart404ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'smart404ai_chat',
                message: message,
                current_url: window.location.href,
                nonce: smart404ai_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    addMessageToChatWithTyping('ai', response.data.message);
                } else {
                    addMessageToChatWithTyping('ai', 'Sorry, I encountered an error: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                addMessageToChatWithTyping('ai', 'Sorry, I\'m having trouble connecting right now. Please try again later.');
            }
        });
    }
    
    // Add message to chat display
    function addMessageToChat(sender, message) {
        const messageClass = sender === 'user' ? 'user-message' : 'ai-message';
        const avatar = sender === 'user' ? '👤' : '🤖';
        
        // For AI messages, parse markdown. For user messages, escape HTML
        const formattedMessage = sender === 'ai' ? parseMarkdown(message) : escapeHtml(message);
        
        const messageHtml = `
            <div class="${messageClass}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-text">${formattedMessage}</div>
                    <span class="message-time">${new Date().toLocaleTimeString()}</span>
                </div>
            </div>
        `;
        
        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    // Parse basic markdown to HTML
    function parseMarkdown(text) {
        // Escape HTML first to prevent XSS
        let html = escapeHtml(text);
        
        // Handle special AI response patterns first
        // Convert numbered lists that might not have proper markdown format
        html = html.replace(/^(\d+)\.?\s+(.*)$/gm, '$1. $2');
        
        // Convert bullet points that might use different characters
        html = html.replace(/^[•▪▫‣⁃]\s+(.*)$/gm, '• $1');
        
        // Fix malformed markdown (like ***text:**** to **text:**)
        html = html.replace(/\*{3,}([^*]+?):\*{3,}/g, '**$1:**');
        html = html.replace(/\*{3,}([^*]+?)\*{3,}/g, '**$1**');
        
        // Convert markdown to HTML - Process bold first, then italic
        // Bold text **text** or __text__
        html = html.replace(/\*\*([^*]+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__([^_]+?)__/g, '<strong>$1</strong>');
        
        // Italic text *text* or _text_ (only if not inside bold)
        html = html.replace(/(?<!\<strong>)\*([^*\n]+?)\*(?!\<\/strong>)/g, '<em>$1</em>');
        html = html.replace(/(?<!\<strong>)_([^_\n]+?)_(?!\<\/strong>)/g, '<em>$1</em>');
        
        // Fallback for browsers that don't support lookbehind
        html = html.replace(/\*([^*\n]+?)\*/g, function(match, content) {
            // Don't replace if it's inside a strong tag
            return match.includes('<strong>') ? match : '<em>' + content + '</em>';
        });
        
        // Code blocks ```code``` or ~~~code~~~
        html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        html = html.replace(/~~~([\s\S]*?)~~~/g, '<pre><code>$1</code></pre>');
        
        // Inline code `code`
        html = html.replace(/`([^`\n]+?)`/g, '<code>$1</code>');
        
        // Headers
        html = html.replace(/^#{4}\s+(.*$)/gim, '<h4>$1</h4>');
        html = html.replace(/^#{3}\s+(.*$)/gim, '<h3>$1</h3>');
        html = html.replace(/^#{2}\s+(.*$)/gim, '<h2>$1</h2>');
        html = html.replace(/^#{1}\s+(.*$)/gim, '<h1>$1</h1>');
        
        // Links - Handle both markdown links and plain URLs in square brackets
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
        html = html.replace(/\[Link:\s*([^\]]+)\]/g, '<a href="$1" target="_blank" rel="noopener">Visit Link</a>');
        
        // Handle line breaks and paragraphs
        const lines = html.split('\n');
        const processedLines = [];
        let inList = false;
        let listType = null;
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            
            // Skip empty lines
            if (line === '') {
                if (inList) {
                    processedLines.push(`</${listType}>`);
                    inList = false;
                    listType = null;
                }
                processedLines.push('</p><p>');
                continue;
            }
            
            // Check if this line is a list item
            const isOrderedListItem = /^\d+\.\s/.test(line);
            const isUnorderedListItem = /^[•▪▫‣⁃*-]\s/.test(line);
            
            if (isOrderedListItem || isUnorderedListItem) {
                const currentListType = isOrderedListItem ? 'ol' : 'ul';
                
                if (!inList) {
                    processedLines.push(`<${currentListType}>`);
                    inList = true;
                    listType = currentListType;
                } else if (listType !== currentListType) {
                    processedLines.push(`</${listType}>`);
                    processedLines.push(`<${currentListType}>`);
                    listType = currentListType;
                }
                
                // Extract list item content
                const content = line.replace(/^\d+\.\s|^[•▪▫‣⁃*-]\s/, '');
                processedLines.push(`<li>${content}</li>`);
            } else {
                if (inList) {
                    processedLines.push(`</${listType}>`);
                    inList = false;
                    listType = null;
                }
                processedLines.push(line);
            }
        }
        
        // Close any remaining list
        if (inList) {
            processedLines.push(`</${listType}>`);
        }
        
        html = processedLines.join('\n');
        
        // Clean up paragraph breaks and wrap content
        html = html.replace(/\n+/g, '\n');
        html = html.replace(/^\n|\n$/g, '');
        
        // Wrap in paragraph if not already wrapped in block elements
        if (!html.match(/^<(h[1-6]|ul|ol|pre|div)/)) {
            html = '<p>' + html + '</p>';
        }
        
        // Clean up consecutive elements
        html = html.replace(/<\/p>\s*<p>/g, '</p><p>');
        html = html.replace(/<p>\s*<\/p>/g, '');
        html = html.replace(/<\/ul>\s*<ul>/g, '');
        html = html.replace(/<\/ol>\s*<ol>/g, '');
        
        // Final cleanup of malformed strong tags
        html = html.replace(/<strong>([^<]*)<\/strong>:/g, '<strong>$1:</strong>');
        
        return html;
    }
    
    // Enhanced message display with typing animation
    function addMessageToChatWithTyping(sender, message) {
        const messageClass = sender === 'user' ? 'user-message' : 'ai-message';
        const avatar = sender === 'user' ? '<span class="icon-user"></span>' : '<span class="icon-bot"></span>';
        
        const messageContainer = $(`
            <div class="${messageClass}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-text"></div>
                    <span class="message-time">${new Date().toLocaleTimeString()}</span>
                </div>
            </div>
        `);
        
        chatMessages.append(messageContainer);
        
        const messageTextElement = messageContainer.find('.message-text');
        
        // Always parse markdown for AI messages, escape HTML for user messages
        const formattedMessage = sender === 'ai' ? parseMarkdown(message) : escapeHtml(message);
        
        if (sender === 'ai' && message.length > 100) {
            // Show typing animation for longer AI messages
            messageTextElement.html('<div class="typing-indicator">AI is thinking...</div>');
            
            setTimeout(function() {
                messageTextElement.html(formattedMessage);
            }, 800);
        } else {
            // For short messages or user messages, show immediately
            messageTextElement.html(formattedMessage);
        }
        
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    // Show/hide loading indicator
    function showLoading() {
        loadingIndicator.fadeIn(200);
        chatSend.prop('disabled', true).text('Sending...');
    }
    
    function hideLoading() {
        loadingIndicator.fadeOut(200);
        chatSend.prop('disabled', false).text('Send');
    }
    
    // Escape HTML for security
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Event listeners for both chat interfaces
    chatSend.click(function() {
        sendMessage(chatInput.val());
    });
    
    chatOverlaySend.click(function() {
        sendMessage(chatOverlayInput.val(), true);
    });
    
    chatInput.keypress(function(e) {
        if (e.which === 13) { // Enter key
            sendMessage(chatInput.val());
        }
    });
    
    chatOverlayInput.keypress(function(e) {
        if (e.which === 13) { // Enter key
            sendMessage(chatOverlayInput.val(), true);
        }
    });
    
    // Suggestion buttons
    $('.suggestion-btn').click(function() {
        const question = $(this).data('question');
        chatInput.val(question);
        sendMessage(question);
    });
    
    // Auto-focus chat input
    setTimeout(function() {
        chatInput.focus();
    }, 1000);
    
    // Smart suggestions with fade-in animation
    $('.suggestion-card').each(function(index) {
        $(this).delay(index * 100).fadeIn(300);
    });
    
    // Add some interactive elements
    $('.nav-button').hover(
        function() {
            $(this).addClass('hover-effect');
        },
        function() {
            $(this).removeClass('hover-effect');
        }
    );
    
    // Track user interactions for analytics
    function trackInteraction(type, data) {
        // Optional: Send analytics data back to WordPress
        $.post(smart404ai_ajax.ajax_url, {
            action: 'track_smart404ai_interaction',
            interaction_type: type,
            data: data,
            nonce: smart404ai_ajax.nonce
        });
    }
    
    // Track suggestion clicks
    $('.suggestion-card a').click(function() {
        trackInteraction('suggestion_click', {
            title: $(this).text(),
            url: $(this).attr('href')
        });
    });
    
    // Track chat usage
    chatSend.click(function() {
        trackInteraction('chat_message', {
            message_length: chatInput.val().length
        });
    });
    
    // Advanced: Typing indicator
    let typingTimer;
    chatInput.on('input', function() {
        clearTimeout(typingTimer);
        
        // Show typing indicator after user stops typing for 2 seconds
        typingTimer = setTimeout(function() {
            if (chatInput.val().trim().length > 0) {
                // Could add "AI is preparing a response..." indicator
            }
        }, 2000);
    });
    
    // Progressive enhancement: Add smooth scrolling
    $('a[href^="#"]').click(function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Add some Easter eggs for tech-savvy users
    if (window.location.pathname.includes('api') || 
        window.location.pathname.includes('dev') || 
        window.location.pathname.includes('code')) {
        
        setTimeout(function() {
            addMessageToChat('ai', '🚀 I see you might be a developer! Try asking me about our API documentation or development resources.');
        }, 3000);
    }
    
    // Console easter egg
    console.log('%cSmart404AI', 'font-size: 20px; color: #4285f4; font-weight: bold;');
    console.log('%cThis 404 page is powered by Google Gemini AI!', 'font-size: 14px; color: #34a853;');
    console.log('%cFor developers: Try typing "debug" in the chat!', 'font-size: 12px; color: #ea4335;');
    
    // Debug mode easter egg
    chatInput.on('input', function() {
        if ($(this).val().toLowerCase() === 'debug') {
            setTimeout(function() {
                addMessageToChatWithTyping('ai', '**Debug mode activated!** Here are some technical details:\n\n' +
                    '• **AI Provider:** Multi-provider support (Gemini, OpenAI, Claude)\n' +
                    '• **Smart Analysis:** URL analyzed behind-the-scenes for suggestions\n' +
                    '• **Entertainment Mode:** Fun 404 titles and messages generated\n' +
                    '• **Max Suggestions:** Limited to 4 most relevant matches\n' +
                    '• **Response Time:** ~2-3 seconds\n' +
                    '• **Chat History:** Maintained for session\n' +
                    '• **Models Used:** GPT-4o-mini, Claude-3-Haiku, Gemini-1.5-Flash\n\n' +
                    'The AI now provides both practical help AND entertainment with multiple provider options!');
            }, 500);
        }
    });
    
});

// Optional: Add service worker for offline functionality
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').then(function(registration) {
        console.log('SW registered with scope: ', registration.scope);
    }).catch(function(error) {
        console.log('SW registration failed: ', error);
    });
}