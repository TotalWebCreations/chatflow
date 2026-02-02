# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.3 - 2026-02-02

### Fixed
- CHANGELOG format alignment with Craft Plugin Store requirements

## 1.0.2 - 2026-02-02

### Fixed
- Version tag alignment for Craft Plugin Store compatibility

## 1.0.1 - 2026-02-01

### Added
- Built-in spam protection:
  - Honeypot field validation (hidden field that bots fill)
  - Time-based validation (minimum and maximum submission time)
  - JavaScript token generation and validation
  - Rate limiting per IP address (configurable submissions per timeframe)
  - SpamProtectionService for centralized validation
  - Fully configurable thresholds in plugin settings
  - Failed spam attempts logged to Craft logs
  - Enabled by default with sensible defaults

## 1.0.0 - 2026-02-01

### Added
- Initial release of ChatFlow
- Conversational form builder with chat-like interface
- Control Panel interface for creating and managing forms
- Support for multiple field types:
  - Text input
  - Email (with validation)
  - Phone number
  - Textarea
  - Multiple choice buttons
  - Date picker
- Question builder with drag-and-drop reordering
- Optional questions with customizable skip button text
- Submissions management and viewing
- Export submissions to CSV
- Multi-channel notification system:
  - Email notifications (multiple recipients)
  - Slack integration via webhooks
  - Microsoft Teams integration
  - Custom webhooks (Zapier, Make, etc.)
- Form-level notification settings with fallback to global plugin settings
- Customizable appearance:
  - Avatar styles (solid, gradient, custom image)
  - Brand colors (primary and secondary)
  - Custom initials
  - Automatic text contrast based on background
- Multi-site support:
  - Full Craft CMS multi-site compatibility
  - Site-specific question text, placeholders, skip text, and multiple choice options
  - Site selector in form editor (breadcrumb navigation)
  - Automatic fallback to primary site content
  - Frontend automatically displays content for current site
- Multi-language support:
  - Control Panel translations in English (en), Dutch (nl), German (de), French (fr), and Spanish (es)
  - Site-specific content translations for frontend forms
- Frontend features:
  - Mobile-first responsive design
  - Real-time validation
  - Progress indicator
  - Smooth animations
  - Touch-friendly interface
- Developer features:
  - Simple Twig template tag: `{{ craft.chatflow.render('handle') }}`
  - Headless API for AJAX submissions
  - Webhook payload with structured JSON data
  - CSS custom properties for easy theming
  - Built-in webhook tester for local development
- Auto-generated form handles from names
- HTML email templates with ChatFlow branding
- IP address and user agent tracking for submissions

### Technical Details
- Requires Craft CMS 5.0.0 or later
- Requires PHP 8.2 or later
- Uses Craft's native Guzzle HTTP client for webhooks
- Implements proper database migrations for easy installation
- Clean MVC architecture with dedicated services layer
