# Basehit.io - Complete Rebranded Site

Welcome to the **Basehit.io** platform repository. This project contains a complete set of rebranded PHP files for the Basehit.io platform, transitioning from the previous "Lustifieds" branding to a modern, clean, and professional identity.

## 🚀 Overview

Basehit.io is a trusted platform for authentic personals. This repository includes 37+ PHP files that have been thoroughly updated to reflect the new branding, including:
- All "Lustifieds" references updated to **Basehit**.
- All domains updated to **basehit.io**.
- Clean filenames with version numbers removed.
- Integrated **Basehit design system** using Tailwind CSS and custom color variables.

## 🎨 Design System & Styling

The platform utilizes a custom design system focused on responsiveness and modern aesthetics:
- **Gradient Hero Sections**: Engaging visual elements for key landing pages.
- **Custom Tailwind Variables**: Uses `gh-` prefixed color variables for consistent branding:
  - `gh-bg`: Background color
  - `gh-panel`: Panel/card background
  - `gh-border`: Border color
  - `gh-accent`: Primary accent color (pink/purple)
  - `gh-success`: Success color (green)
  - `gh-muted`: Muted text color
  - `gh-fg`: Foreground/text color
- **Icons**: Bootstrap Icons used throughout the interface.
- **Responsive Layouts**: Fully optimized for mobile, tablet, and desktop viewing.

## 📂 Project Structure

### Core & Legal
- `index.php`: Homepage with rebranded hero and features.
- `about.php`: New "About Basehit" page.
- `terms.php`, `privacy.php`, `safety.php`: Updated legal and safety guidelines.

### User & Creator Management
- `register.php`, `login.php`: User authentication flows.
- `profile.php`, `edit-profile.php`: User profile management.
- `creator-dashboard.php`: Comprehensive dashboard for content creators.
- `creator-analytics.php`, `creator-earnings.php`: Tools for tracking performance and revenue.

### Marketplace & Listings
- `browse.php`, `listing.php`: Discovery and viewing of personals.
- `marketplace.php`, `marketlisting.php`: Creator-focused marketplace.
- `post-ad.php`: Streamlined advertisement posting.

### Community & Content
- `forum.php`: Community discussion boards.
- `story.php`, `story-view.php`: User-submitted stories and content.

## 🛠 Installation & Setup

### Requirements
- **PHP**: 7.4 or higher.
- **Database**: MySQL or MariaDB.
- **CSS Framework**: Tailwind CSS (configured with `gh-` variables).
- **Icons**: Bootstrap Icons CSS.

### Steps
1. **Extract Files**: Deploy the PHP files to your web root directory.
2. **Database Configuration**: Update `config/database.php` with your credentials.
3. **Verify Dependencies**: Ensure `views/header.php`, `views/footer.php`, and `includes/maintenance_check.php` are present.
4. **Permissions**: Set proper file permissions (typically `644` for PHP files).
5. **Moderation Setup**: Configure `config/moderation.php` with your Perplexity API key for automated content flagging.

## 🛡 Content Moderation

This platform includes an **AI-powered contextual flagging system** integrated with Perplexity. 
- Configuration is managed via `config/moderation.php`.
- Supports automated rejection and review thresholds.
- Moderates listings, forum posts, stories, messages, and profiles.

## 📧 Support & Contact

- **General Support**: support@basehit.io
- **Legal Inquiries**: legal@basehit.io
- **Privacy**: privacy@basehit.io
- **Official Website**: [https://basehit.io](https://basehit.io)

---
*Basehit.io - Your trusted platform for authentic personals.*
