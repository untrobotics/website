# UNT Robotics Website Structure

## Overview

The UNT Robotics website is organized into several key directories, each serving a specific purpose:

```
.
├── about/                # Organization and team information
├── admin/                # Administrative tools and interfaces
├── ajax/                 # AJAX endpoints for dynamic content
├── api/                  # External service integrations
│   ├── campuslabs/       # Campus Labs membership integration
│   ├── discord/          # Discord bot integration
│   ├── endpoint-example/ # Example endpoint implementations
│   ├── google/           # Google services integration
│   ├── groupme/          # GroupMe messaging
│   ├── jira/             # Jira project management hooks
│   ├── paypal/           # PayPal payment integration
│   ├── printful/         # Merchandise fulfillment
│   ├── robots/           # Robot management endpoints
│   ├── sendgrid/         # Email service
│   └── twitter/          # Twitter integration
├── auth/                 # Authentication and user management
├── botathon/             # Competition registration and management
├── cicd/                 # Continuous integration/deployment
├── cron/                 # Scheduled tasks and automation
├── datasheets/           # Technical documentation
├── docs/                 # Project documentation
├── downloads/            # Public downloadable files
├── dues/                 # Membership dues management
├── dyndns/               # Dynamic DNS service
├── fonts/                # Web fonts
├── images/               # Image assets
├── js/                   # JavaScript files
├── legal/                # Terms and policies
├── me/                   # User dashboard
├── media/                # Media files (video, audio)
├── merch/                # Merchandise store
├── paypal/               # Payment processing
├── scripts/              # Utility scripts
├── sponsorships/         # Sponsorship management
├── sql/                  # Database schemas
├── template/             # Core website framework
└── twilio/               # Phone/SMS integration
```

## Core Components

### Template System (`/template/`)

- `top.php` - Core initialization file
- `header.php` & `footer.php` - Page layout templates
- `functions/` - Shared utility functions
- `classes/` - Core PHP classes
- `constants.php` - Global constants and configuration
- `sample.config.php` - Configuration template

### Authentication System (`/auth/`)

- User registration and login
- Discord integration
- SAML authentication for UNT credentials
- Password management
- Session handling

### API Integrations (`/api/`)

```tree
api/
├── campuslabs/        # Campus Labs membership integration
│   └── total-members.php
├── discord/           # Discord bot integration
│   ├── bot.php
│   └── bots/         # Different bot functionalities
│       ├── admin.php
│       └── test.php
├── endpoint-example/  # Example endpoint implementations
│   └── groupme-endpoint-example.php
├── google/           # Google services integration
│   └── count_majors.php
├── groupme/          # GroupMe messaging platform
├── jira/             # Jira project management hooks 
│   └── webhooks.php
├── paypal/           # PayPal payment integration
│   └── buttons/      # Payment button generation
├── printful/         # Merchandise fulfillment
│   └── webhook.php
├── robots/           # Robot management endpoints
│   ├── tunnel.php
│   └── update-iptables.sh
├── sendgrid/         # SendGrid email service
├── sendgrid-inbound/ # Inbound email processing
│   └── parse.php
├── twilio-send.php   # Twilio messaging
└── twitter/          # Twitter integration
    └── get-tweets.php
```

Key API functionalities:

1. **User Management**
   - Campus Labs for member tracking 
   - Discord for member roles and communication
   - GroupMe for team messaging

2. **Payment Processing**
   - PayPal button generation
   - Payment webhooks
   - Transaction processing

3. **Communications**
   - SendGrid for outbound emails
   - SendGrid inbound parsing
   - Twilio for SMS/voice
   - Twitter feed integration

4. **Project Management**
   - Jira webhooks for automation
   - Robot management endpoints
   - Tunnel management for remote access

5. **Google Integration**
   - Major counting/analytics
   - Form processing
   - Data analysis

6. **Example Implementations**
   - GroupMe endpoint examples
   - Webhook templates
   - Integration patterns

### Payment Processing (`/paypal/`)

- IPN (Instant Payment Notification) handlers
- Payment button generation
- Transaction logging
- Payment verification

### Merchandise System (`/merch/`)

- Product listings
- Shopping cart
- Printful integration
- Order processing
- Payment handling

### Communication System (`/twilio/`)

- Voice call handling
- Voicemail system
- SMS messaging
- Call routing
- Hold music system

## Database Structure (`/sql/`)

Key database tables and their purposes:

- `botathon_registration` - Event registration
- `dues_payments` - Member dues tracking
- `password_reset_tokens` - Password management
- `tunnel_api_keys` - API authentication
- `outgoing_request_cache` - API request caching

### Migrations (`/sql/migrations/`)

Database schema changes are tracked in migration files:

- `URW-147.sql`
- `URW-178-cache-configs.sql`
- `URW-64-ftp-users.sql`
- etc.

## Content Areas

### Main Pages

- `index.php` - Homepage
- `about/` - Organization information
- `events.php` - Event listings
- `contact.php` - Contact form

### Member & Event Services

- `about/` - About pages and team member information
- `dues/` - Membership dues handling
- `me/` - Member dashboard
- `botathon/` - Competition registration and management 
- `dyndns/` - Dynamic DNS service for member projects
- `sponsorships/` - Sponsor management and donations

### Administrative & System

- `admin/` - Admin control panel and tools
- `ajax/` - AJAX endpoints for dynamic content
- `cicd/` - Continuous integration/deployment
- `cron/` - Scheduled tasks and automation
- `scripts/` - Utility scripts and tools
- `datasheets/` - Technical documentation and specs

## Assets

### Static Files & Media

- `css/` - Stylesheets
- `js/` - JavaScript files
- `fonts/` - Web fonts
- `images/` - Image assets
  - `bio-pics/` - Member photos
  - `headers/` - Page headers
  - `sponsor-logos/` - Sponsor branding
  - `merch/` - Product images
  - `web-team-pics/` - Web team photos
- `media/` - Video and audio content
- `downloads/` - Downloadable resources
- `legal/` - Terms, policies, and rules

### Documents

- `downloads/` - Downloadable files
- `datasheets/` - Technical documentation

## Key Files

### Configuration

- `template/sample.config.php` → Copy to `config.php`
- `template/constants.php` - Global constants
- `twilio/phone-numbers-config-sample.php` → Copy to `phone-numbers-config.php`

### Entry Points

- `template/top.php` - Application bootstrap
- `index.php` - Main entry point
- Various `index.php` files in subdirectories

## Development Guidelines

### File Organization

1. Place new features in appropriate directories
2. Follow existing naming conventions
3. Keep related files together
4. Use subdirectories for complex features

### Code Structure

1. Include `top.php` for initialization
2. Use provided template functions
3. Follow PSR-12 coding standards
4. Document new functions and classes

### Database Changes

1. Create migration files in `/sql/migrations/`
2. Follow naming convention: `URW-{ticket}-description.sql`
3. Update schema documentation

### Security

1. All sensitive files must check `API_SECRET`
2. Validate all user input
3. Use prepared SQL statements
4. Follow authentication flow

## Development Workflow

1. **Local Setup**
   - Clone repository
   - Copy configuration templates
   - Import database schema
   - Configure virtual host

2. **Development**
   - Create feature branch
   - Follow coding standards
   - Test thoroughly
   - Update documentation

3. **Deployment**
   - Submit pull request
   - Pass code review
   - Merge to main
   - Deploy via CI/CD

## Additional Resources

- [GETTING_STARTED.md](GETTING_STARTED.md) - Setup guide
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines
- [Jira Board](https://untrobotics.atlassian.net) - Project management
