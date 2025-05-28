# Smart404AI

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)
![AI Powered](https://img.shields.io/badge/AI-Multi--Provider-orange.svg)

Transform your WordPress 404 error pages into intelligent, AI-driven user experiences using your choice of Google Gemini, OpenAI GPT, or Anthropic Claude. Smart404AI analyzes broken URLs, understands user intent, and provides personalized content suggestions through real-time AI interaction.

## Features

### Multi-Provider AI Support
- Choose between Google Gemini, OpenAI GPT, or Anthropic Claude
- Free tier available with Google Gemini
- Cost-effective models: GPT-4o-mini, Claude-3-Haiku, Gemini-1.5-Flash
- Easy provider switching without losing settings

### Intelligent URL Analysis
- Semantic analysis of broken URLs using advanced AI models
- Context-aware understanding of user intent
- Cross-referencing with existing site content
- Smart scoring of content similarity

### AI-Powered Content Suggestions
- Dynamic recommendations based on semantic matching
- Contextual explanations for each suggestion
- Personalized messaging adapted to site tone
- Real-time content discovery

### Interactive AI Assistant
- Live chat powered by your chosen AI provider
- Natural language query understanding
- Site-specific knowledge integration
- Conversational troubleshooting

### Analytics and Insights
- Comprehensive 404 error logging
- User interaction tracking
- AI response performance metrics
- Broken link pattern analysis

### Developer Features
- WordPress hooks integration
- AJAX-powered real-time responses
- Responsive design with modern CSS
- Extensible architecture
- Debug mode for developers

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- API key from at least one supported provider:
  - Google AI Studio (free tier available)
  - OpenAI Platform (pay-per-use)
  - Anthropic Console (pay-per-use)
- Modern web browser with JavaScript enabled

## Installation

### Manual Installation

1. Download the Smart404AI plugin files
2. Upload the `smart404ai` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel
4. Navigate to **Settings > Smart404AI** to configure

### WordPress Admin Installation

1. Go to **Plugins > Add New** in your WordPress admin
2. Upload the Smart404AI zip file
3. Click **Install Now** and then **Activate**
4. Configure the plugin at **Settings > Smart404AI**

## Configuration

### Getting Your API Keys

**Google Gemini (Recommended for beginners)**
1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Create a new API key
4. Copy the generated key

**OpenAI GPT**
1. Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. Sign in or create an account
3. Navigate to API Keys section
4. Create a new secret key

**Anthropic Claude**
1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Sign in or create an account
3. Navigate to API Keys section
4. Create a new API key

### Plugin Setup

1. Go to **Settings > Smart404AI** in your WordPress admin
2. Select your preferred AI provider
3. Paste the corresponding API key in the designated field
4. Enable/disable the AI chat feature as desired
5. Click **Save Changes**
6. Use the **Test Connection** button to verify setup

## Usage

### For Site Visitors

When users encounter a 404 error, Smart404AI automatically:

- Analyzes the broken URL to understand intent
- Provides intelligent content suggestions
- Offers an interactive AI chat assistant
- Displays fallback navigation options

### For Site Administrators

Monitor 404 performance through the admin dashboard:

- View recent 404 error logs
- Track user interaction patterns
- Test AI integration status
- Analyze content suggestion effectiveness

## File Structure

```
smart404ai/
├── smart404ai.php          # Main plugin file
├── templates/
│   └── 404.php             # AI-powered 404 template
├── js/
│   └── smart404ai.js       # Frontend JavaScript
├── css/
│   └── smart404ai.css      # Stylesheet
└── README.md               # Documentation
```

## API Integration

Smart404AI integrates with multiple leading AI providers, allowing you to choose the best option for your needs:

### Supported Providers

**Google Gemini 1.5 Flash**
- Free tier available (60 requests per minute)
- Fast response times
- Good for high-traffic sites
- Excellent at creative content generation

**OpenAI GPT-4o-mini**
- Pay-per-use pricing
- High-quality responses
- Strong reasoning capabilities
- Great for complex analysis

**Anthropic Claude 3 Haiku**
- Pay-per-use pricing
- Fast and cost-effective
- Excellent safety features
- Good for conversational interactions

### API Features

The plugin handles:
- Secure API key management
- Request rate limiting
- Error handling and fallbacks
- Response caching for performance
- Provider-specific optimizations

### API Calls

The plugin makes API calls for:
- URL analysis and content matching
- Dynamic message generation
- Interactive chat responses
- Content suggestion explanations
- Entertaining 404 content creation

## Customization

### Template Override

To customize the 404 page template:

1. Copy `templates/404.php` to your theme directory
2. Modify the template as needed
3. The plugin will automatically use your custom template

### Styling

Override default styles by adding CSS to your theme:

```css
.smart404ai-container {
    /* Your custom styles */
}
```

### Hooks and Filters

Available WordPress hooks:

```php
// Modify AI analysis results
add_filter('smart404ai_analysis_result', 'custom_analysis_handler');

// Customize chat responses
add_filter('smart404ai_chat_response', 'custom_chat_handler');

// Track custom events
add_action('smart404ai_404_detected', 'custom_404_tracker');
```

## Performance Considerations

### Caching

Smart404AI implements intelligent caching to minimize API calls:

- Similar URL patterns are cached
- Chat responses for common queries are stored
- Fallback content is prepared for offline scenarios

### Rate Limiting

The plugin respects Google's API rate limits:

- Automatic request throttling
- Graceful degradation when limits are reached
- Local fallback for high-traffic scenarios

## Troubleshooting

### Common Issues

**API Key Not Working**
- Verify the key is correctly copied from Google AI Studio
- Ensure the key has proper permissions
- Test the connection using the admin panel button

**404 Template Not Loading**
- Check file permissions in the plugin directory
- Verify WordPress template hierarchy
- Clear any caching plugins

**JavaScript Errors**
- Ensure jQuery is loaded
- Check for plugin conflicts
- Verify browser JavaScript is enabled

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Privacy and Data

### Data Collection

Smart404AI collects:
- Broken URL patterns (for analysis)
- User interaction metrics (anonymized)
- Chat conversation logs (temporary)

### Data Storage

- All data is stored locally in your WordPress database
- No personal information is sent to external services
- Chat logs are automatically purged after 30 days

### GDPR Compliance

The plugin is designed with privacy in mind:
- No personal data collection
- Anonymized analytics only
- User consent mechanisms available

## Contributing

We welcome contributions to Smart404AI:

### Development Setup

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Coding Standards

- Follow WordPress coding standards
- Include appropriate documentation
- Write unit tests for new features
- Ensure backward compatibility

## Support

### Documentation

- Plugin documentation: [Coming Soon]
- WordPress Codex: [WordPress.org](https://wordpress.org/support/)
- Google AI Documentation: [ai.google.dev](https://ai.google.dev/)

### Community

- Report bugs via GitHub Issues
- Feature requests welcome
- Community forums available

## Changelog

### Version 1.0.0
- Initial release
- Multi-provider AI integration (Google Gemini, OpenAI GPT, Anthropic Claude)
- Real-time AI chat with markdown support
- Intelligent URL analysis and content suggestions
- Entertaining 404 titles and messages
- Comprehensive analytics and log management
- Page creation from 404 patterns
- Responsive design with modern icons
- WordPress 5.0+ compatibility
- Cost-effective model selection (GPT-4o-mini, Claude-3-Haiku, Gemini-1.5-Flash)

## License

Smart404AI is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

- Developed by MaxtDesign
- Powered by Google Gemini, OpenAI GPT, and Anthropic Claude
- Built for WordPress
- Inspired by modern user experience principles

---

**Smart404AI** - Making 404 errors intelligent with your choice of leading AI providers.