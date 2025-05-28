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
    
    // Send message function
    function sendMessage(message) {
        if (!message.trim()) return;
        
        // Add user message to chat
        addMessageToChat('user', message);
        
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
                    addMessageToChat('ai', response.data.message);
                } else {
                    addMessageToChat('ai', 'Sorry, I encountered an error: ' + response.data);
                }
            },
            error: function() {
                hideLoading();
                addMessageToChat('ai', 'Sorry, I\'m having trouble connecting right now. Please try again later.');
            }
        });
    }
    
    // Add message to chat display
    function addMessageToChat(sender, message) {
        const messageClass = sender === 'user' ? 'user-message' : 'ai-message';
        const avatar = sender === 'user' ? '👤' : '🤖';
        
        const messageHtml = `
            <div class="${messageClass}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <p>${escapeHtml(message)}</p>
                    <span class="message-time">${new Date().toLocaleTimeString()}</span>
                </div>
            </div>
        `;
        
        chatMessages.append(messageHtml);
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
    
    // Event listeners
    chatSend.click(function() {
        sendMessage(chatInput.val());
    });
    
    chatInput.keypress(function(e) {
        if (e.which === 13) { // Enter key
            sendMessage(chatInput.val());
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
    console.log('%c🤖 Smart404AI', 'font-size: 20px; color: #4285f4; font-weight: bold;');
    console.log('%cThis 404 page is powered by Google Gemini AI!', 'font-size: 14px; color: #34a853;');
    console.log('%cFor developers: Try typing "debug" in the chat!', 'font-size: 12px; color: #ea4335;');
    
    // Debug mode easter egg
    chatInput.on('input', function() {
        if ($(this).val().toLowerCase() === 'debug') {
            setTimeout(function() {
                addMessageToChat('ai', '🔧 Debug mode activated! Here are some technical details:\n\n' +
                    '• AI Model: Google Gemini 1.5 Flash\n' +
                    '• Response Time: ~2-3 seconds\n' +
                    '• Content Analysis: Semantic matching enabled\n' +
                    '• Chat History: Maintained for session\n' +
                    '• Error Logging: Active\n\n' +
                    'Want to see the source code? Check the browser developer tools! 🚀');
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