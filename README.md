# ChatFlow

A conversational form builder plugin for Craft CMS that transforms traditional forms into engaging, chat-like experiences.

![Craft CMS](https://img.shields.io/badge/Craft%20CMS-5.0+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Price](https://img.shields.io/badge/price-$39-blue.svg)

**💰 Pricing:** $39 USD one-time purchase + $19/year optional renewals for continued updates and support

## Features

### 🎯 Conversational Forms
- **Chat-like Interface**: Transform boring forms into engaging conversations
- **One Question at a Time**: Keep users focused with a progressive disclosure pattern
- **Real-time Validation**: Instant feedback as users type
- **Multiple Field Types**: Support for text, email, phone, textarea, multiple choice buttons, and date fields
- **Optional Questions**: Allow users to skip non-required questions with customizable skip button text
- **Mobile-First Design**: Responsive and touch-friendly interface

### 🎨 Customizable Appearance
- **Avatar Styles**: Choose between solid color, gradient, or custom image avatars
- **Brand Colors**: Customize primary and secondary colors to match your brand
- **Custom Initials**: Set custom initials for the chat avatar
- **Automatic Text Contrast**: Smart text color selection based on background brightness
- **CSS Custom Properties**: Full theming control via CSS variables

### 📧 Flexible Notifications
- **Multiple Email Recipients**: Send notifications to multiple email addresses (comma or newline separated)
- **Slack Integration**: Send submission notifications to Slack channels via webhooks
- **Microsoft Teams Integration**: Post submissions to Teams channels
- **Custom Webhooks**: Integrate with any service via custom webhook URLs (Zapier, Make, etc.)
- **Form-Level Settings**: Configure notifications per form with fallback to global plugin settings
- **Rich Email Templates**: Beautiful HTML email notifications with all submission data

### 🌍 Multi-Site & Multi-Language Support
- **Multi-Site Ready**: Full support for Craft CMS multi-site installations
- **Site-Specific Content**: Translate question text, placeholders, skip text, and multiple choice options per site
- **Easy Site Switching**: Switch between sites directly from the form editor with a convenient dropdown
- **Fallback Logic**: Automatically falls back to primary site content when translations are missing
- **Built-in Translations**: Control panel available in English (en), Dutch (nl), German (de), French (fr), and Spanish (es)

### 🛡️ Built-in Spam Protection
- **Honeypot Fields**: Hidden fields that bots fill but humans cannot see
- **Time-Based Validation**: Reject submissions that are too fast (bots) or too slow (expired)
- **JavaScript Validation**: Ensures JavaScript is enabled with token generation
- **Rate Limiting**: Prevent spam floods with configurable per-IP limits
- **Zero Dependencies**: No external services required - works out of the box
- **Fully Configurable**: Enable/disable and adjust all thresholds from settings

### 🔧 Developer-Friendly
- **Simple Template Tag**: Render forms with a single Twig tag
- **Headless API**: Submit forms via AJAX for custom integrations
- **Webhook Payload**: Structured JSON data for easy integration
- **Extensible**: Clean architecture for custom modifications

## Requirements

- Craft CMS 5.0.0 or later
- PHP 8.2 or later

## Installation

### Via Composer (Recommended)

```bash
composer require totalwebcreations/chatflow
```

Then install the plugin via the Craft Control Panel or command line:

```bash
php craft plugin/install chatflow
```

### Manual Installation

1. Download the latest release
2. Extract to `plugins/chatflow/`
3. Install via Control Panel or command line

## Usage

### Creating a Form

1. Navigate to **ChatFlow** → **Forms** in the Control Panel
2. Click **New Form**
3. Configure the form:
   - **General Tab**: Set form name, handle, and success message
   - **Questions Tab**: Add and configure questions
   - **Notifications Tab**: Set up email, Slack, Teams, or webhook notifications

### Multi-Site Forms

If you have multiple sites configured in Craft CMS, ChatFlow automatically supports site-specific content:

1. **Site Selector**: When editing a form, use the site dropdown in the breadcrumb navigation (top-left) to switch between sites
2. **Translatable Content**: The following fields can be customized per site:
   - Question text
   - Placeholder text
   - Skip button text
   - Multiple choice options
3. **Shared Structure**: Form structure (field types, field names, required status) is shared across all sites
4. **Automatic Detection**: Forms automatically display content based on the current site when rendered on the frontend
5. **Fallback**: If content hasn't been translated for a site, it falls back to the primary site's content

**Example**: Create a contact form in English, then switch to your Dutch site and translate all question text, placeholders, and button labels while keeping the same form structure.

### Adding Questions

Each question supports:
- **Question Text**: The question shown to users
- **Field Type**: text, email, tel, textarea, buttons (multiple choice), or date
- **Field Name**: Technical name for the field (used in submissions and templates)
- **Placeholder**: Optional placeholder text
- **Required**: Toggle to make the question mandatory
- **Skip Button Text**: Custom text for the skip button (optional questions only)
- **Options**: Multiple choice options (for button field type, one per line)

### Rendering a Form

First, create your trigger button with a unique ID:

```twig
<button id="contactButton" class="my-custom-button">
    Get in Touch
</button>
```

Then, render the ChatFlow modal and connect it to your button:

```twig
{{ craft.chatflow.modal('contactForm', 'contactButton') }}
```

Replace `contactForm` with your form's handle and `contactButton` with your button's ID.

**Complete Example:**
```twig
<a id="ctaButton" class="btn btn-primary">
    Start Chat
</a>

{{ craft.chatflow.modal('contactForm', 'ctaButton') }}
```

The modal will automatically open when the user clicks the element with the specified ID.

### Headless/AJAX Integration

Submit forms programmatically:

```javascript
const formData = {
    formHandle: 'contactForm',
    data: {
        name: 'John Doe',
        email: 'john@example.com',
        message: 'Hello!'
    }
};

const response = await fetch('/actions/chatflow/submit/submit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.csrfTokenValue
    },
    body: JSON.stringify(formData)
});

const result = await response.json();
```

## Configuration

### Plugin Settings

Navigate to **Settings** → **Plugins** → **ChatFlow** to configure:

#### Appearance
- **Avatar Type**: Solid color, gradient, or custom image
- **Primary Color**: Main brand color
- **Secondary Color**: Secondary brand color (used in gradients)
- **Initials**: Avatar initials (2 characters)
- **Avatar Image**: Upload a custom avatar image

#### Notifications (Global Defaults)
- **Default Notification Emails**: Comma or newline separated email addresses
- **Enable Email Notifications**: Toggle email notifications on/off
- **Slack Webhook URL**: Default Slack webhook for all forms
- **Teams Webhook URL**: Default Microsoft Teams webhook
- **Custom Webhook URL**: Default custom webhook endpoint

#### Spam Protection
- **Enable Spam Protection**: Toggle spam protection on/off (enabled by default)
- **Minimum Submission Time**: Reject submissions faster than this (default: 2 seconds)
- **Maximum Submission Time**: Reject submissions slower than this (default: 1800 seconds / 30 minutes)
- **Max Submissions**: Maximum submissions per IP within time window (default: 3)
- **Rate Limit Time Window**: Time window for rate limiting (default: 600 seconds / 10 minutes)

**How it works:**
- **Honeypot**: Hidden field (`_chatflow_website`) that legitimate users won't see, but bots will fill
- **Time Validation**: Too fast = bot, too slow = expired session
- **JavaScript Token**: Ensures JavaScript is enabled (ChatFlow requires JS anyway)
- **Rate Limiting**: Prevents spam floods from the same IP using Craft's cache system

All spam protection runs automatically when enabled. Failed spam checks are logged to `storage/logs/web.log` for monitoring.

### Form-Level Notifications

Each form can override global notification settings:

1. Go to the form's **Notifications** tab
2. Configure notifications specific to this form:
   - Email addresses (leave empty to use plugin default)
   - Slack webhook URL (leave empty to use plugin default)
   - Teams webhook URL (leave empty to use plugin default)
   - Custom webhook URL (leave empty to use plugin default)

## Notifications

### Email Notifications

Email notifications include:
- Form name and submission date
- All answered questions with responses
- Submission ID and IP address
- Beautiful HTML template with ChatFlow branding

**Multiple Recipients**: Enter multiple email addresses separated by commas or newlines:
```
team@example.com
manager@example.com
support@example.com
```

### Slack Notifications

1. Create a Slack App at https://api.slack.com/apps
2. Enable Incoming Webhooks
3. Add a webhook to your workspace
4. Copy the webhook URL (looks like `https://hooks.slack.com/services/...`)
5. Paste into ChatFlow settings or form notification settings

Slack messages include:
- ChatFlow branding and color
- Form name
- All submission data as fields
- Timestamp

### Microsoft Teams Notifications

1. In Teams, go to your channel
2. Click **•••** → **Connectors** → **Incoming Webhook**
3. Configure and create the webhook
4. Copy the webhook URL
5. Paste into ChatFlow settings

Teams messages use MessageCard format with:
- Form name
- Submission timestamp
- All answers as facts

### Custom Webhooks

Send submission data to any webhook endpoint (Zapier, Make, custom APIs):

**Payload Structure**:
```json
{
  "form": {
    "id": 1,
    "name": "Contact Form",
    "handle": "contact"
  },
  "submission": {
    "id": 123,
    "data": {
      "name": "John Doe",
      "email": "john@example.com",
      "message": "Hello!"
    },
    "dateCreated": "2026-02-01T14:25:56-08:00",
    "ipAddress": "127.0.0.1",
    "userAgent": "Mozilla/5.0..."
  }
}
```

**Testing Webhooks Locally**:

ChatFlow includes a built-in webhook tester:

1. Use this URL in your webhook settings: `https://yoursite.test/actions/chatflow/webhook-test/receive`
2. Submit a form
3. View the logs at: `https://yoursite.test/actions/chatflow/webhook-test/logs`
4. Or check the log file: `storage/logs/chatflow-webhook-test.log`

## Viewing Submissions

1. Navigate to **ChatFlow** → **Submissions**
2. View all submissions across all forms
3. Filter by form
4. See submission date, form name, and submission data

Click on a submission to view:
- All answered questions
- Submission metadata (IP address, user agent, date)

## Theming & Customization

### CSS Custom Properties

ChatFlow uses CSS custom properties for easy theming:

```css
:root {
    --chatflow-primary: #0891b2;
    --chatflow-gradient: linear-gradient(to bottom right, #0891b2, #06b6d4);
    --chatflow-user-message-bg: #0891b2;
    --chatflow-focus-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
    --chatflow-hover-bg: rgba(8, 145, 178, 0.05);
    --chatflow-text-color: #ffffff;
}
```

### Custom Styling

Override ChatFlow styles in your own CSS:

```css
/* Customize the chat bubble */
.chatflow-modal {
    border-radius: 20px;
}

/* Customize the trigger button */
.chatflow-trigger {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Customize messages */
.chatflow-message.user {
    background: #your-color;
}
```

## Translation

To add a new language:

1. Copy `src/translations/en/chatflow.php`
2. Create a new folder for your language (e.g., `it` for Italian)
3. Translate all strings
4. Submit a pull request!

## Troubleshooting

### Forms Not Appearing

1. Check that the form handle is correct
2. Verify the form has at least one question
3. Check browser console for JavaScript errors
4. Clear Craft's caches

### Notifications Not Sending

1. Check Craft's email settings (Settings → Email)
2. Verify webhook URLs are correct
3. Check `storage/logs/web.log` for errors
4. For Slack/Teams: verify the webhook is active in the service

### Styling Issues

1. Check for CSS conflicts with other plugins/themes
2. Use browser DevTools to inspect elements
3. Verify ChatFlow assets are loaded (check Network tab)

## Development

### Project Structure

```
chatflow/
├── src/
│   ├── assets/          # Frontend JavaScript and CSS
│   ├── controllers/     # Control Panel and API controllers
│   ├── migrations/      # Database migrations
│   ├── models/          # Form, Question, Submission models
│   ├── records/         # ActiveRecord database models
│   ├── services/        # Business logic services
│   ├── templates/       # Twig templates for CP
│   └── translations/    # i18n language files
├── composer.json
└── README.md
```

### Building Assets

Frontend assets are located in `src/assets/`:
- `js/chatflow.js` - Main conversational form logic
- `css/chatflow.css` - Styles for the chat interface

### Database Schema

**chatflow_forms**
- Form metadata (name, handle, success message)
- Notification settings (email, Slack, Teams, webhook)

**chatflow_questions**
- Question configuration
- Field types and validation
- Sort order for display

**chatflow_submissions**
- Submission data (JSON)
- User metadata (IP, user agent)
- Timestamps

## Support

- **Documentation**: https://github.com/TotalWebCreations/chatflow
- **Issues**: https://github.com/TotalWebCreations/chatflow/issues
- **Email**: support@totalwebcreations.nl

## License

This plugin is licensed under the MIT License. See LICENSE.md for details.

## Credits

Developed by [TotalWebCreations](https://totalwebcreations.nl)

---

**Enjoying ChatFlow?** Please star the repo and share with others! ⭐
