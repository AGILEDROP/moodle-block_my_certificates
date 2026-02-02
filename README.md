# My Certificates Block

A Moodle block plugin that displays user-earned certificates in an attractive card-based grid layout with PDF previews.

## Description

The My Certificates block provides users with a visual overview of all certificates they have earned through the [Custom Certificate module](https://moodle.org/plugins/mod_customcert). Each certificate is displayed as a card with:

- Live PDF preview of the certificate
- Certificate name and course information
- Date of issuance
- Direct download link

## Features

- **Visual Certificate Display**: Automatically renders PDF previews of earned certificates
- **Responsive Grid Layout**: Cards adapt to different screen sizes
- **Modern Design**: Clean, professional appearance that integrates with any Moodle theme
- **Easy Configuration**: Simple block configuration with customizable "no certificates" message
- **Accessibility**: Properly structured HTML with ARIA labels and keyboard navigation support
- **Performance**: Efficient PDF rendering using PDF.js library

## Requirements

- Moodle 4.5 or higher
- PHP 8.1 or higher
- [Custom Certificate module](https://moodle.org/plugins/mod_customcert) (mod_customcert) version 2024042212 or higher

## Installation

### Via Moodle Plugin Directory

1. Visit Site administration > Plugins > Install plugins
2. Search for "My Certificates"
3. Click Install and follow the prompts

### Manual Installation

1. Download the plugin from the [Moodle plugins directory](https://moodle.org/plugins/)
2. Extract the archive
3. Copy the `my_certificates` folder to `/blocks/` directory in your Moodle installation
4. Visit Site administration > Notifications to complete the installation
5. Configure the block settings if needed

### Via Git

```bash
cd /path/to/moodle/blocks/
git clone https://github.com/yourusername/moodle-block_my_certificates.git my_certificates
```

Then visit Site administration > Notifications to complete the installation.

## Usage

### Adding the Block

1. Enable editing mode on your dashboard, course page, or site home page
2. Click "Add a block"
3. Select "My Certificates" from the list
4. The block will display all certificates earned by the current user

### Configuring the Block

1. Click the gear icon on the block
2. Configure the "No certificates text" field
   - This message is displayed when a user has no certificates yet
   - Supports HTML formatting
   - Can include links to course catalogs or learning paths
3. Save changes

### For Students

- View all earned certificates in one place
- Click on any certificate to download the PDF
- Certificates automatically update when new ones are earned

### For Administrators

The block can be placed on:
- User dashboards (My Moodle)
- Site home page
- Course pages
- Any other Moodle page that supports blocks

## Configuration

### Block Instance Settings

When configuring a block instance, you can set:

- **No certificates text**: HTML message shown when the user has no certificates
  - Example: "Complete courses to earn certificates!"
  - Can include links to encourage learning

### Global Settings

There are no global settings for this plugin. All configuration is per-block instance.

## Technical Details

### File Structure

```
blocks/my_certificates/
├── amd/
│   ├── src/
│   │   └── pdf_preview.js       # ES6 source for PDF rendering
│   └── build/
│       ├── pdf_preview.min.js   # Compiled AMD module
│       └── pdf_preview.min.js.map
├── db/
│   └── access.php               # Capability definitions
├── js/
│   ├── pdf.min.js              # PDF.js library
│   └── pdf.worker.min.js       # PDF.js worker
├── lang/
│   └── en/
│       └── block_my_certificates.php  # English strings
├── templates/
│   └── content.mustache         # Main template
├── block_my_certificates.php    # Block class
├── edit_form.php               # Block configuration form
├── styles.css                  # Block styles
├── version.php                 # Plugin version info
└── README.md                   # This file
```

### Database Queries

The plugin queries the following Moodle tables:
- `customcert_issues` - Certificate issuances
- `customcert` - Certificate definitions
- `course` - Course information
- `course_modules` - Course module instances
- `modules` - Module definitions

No additional database tables are created by this plugin.

### Rendering

- Uses Mustache templates for output
- PDF previews rendered client-side with PDF.js
- Responsive CSS Grid layout
- No external dependencies beyond PDF.js

## Capabilities

### block/my_certificates:addinstance

Allows adding the My Certificates block to a page.

- **Risk**: None
- **Default**: Teacher, Editingteacher, Manager

### block/my_certificates:myaddinstance

Allows adding the My Certificates block to the My Moodle page.

- **Risk**: None
- **Default**: User

## Troubleshooting

### Certificates Not Displaying

1. Ensure Custom Certificate module is installed and up to date
2. Check that certificates have been issued to the user
3. Verify the user has permission to view their certificates
4. Check browser console for JavaScript errors

### PDF Previews Not Loading

1. Check that the PDF.js library files are present in `/blocks/my_certificates/js/`
2. Ensure JavaScript is enabled in the browser
3. Check browser console for errors
4. Try clearing browser cache

### Styling Issues

1. Ensure `/blocks/my_certificates/styles.css` is present
2. Clear Moodle caches (Site administration > Development > Purge all caches)
3. Try a different theme to check for theme conflicts
4. Check browser console for CSS errors

## Support

For bug reports and feature requests, please use:
- [GitHub Issues](https://github.com/yourusername/moodle-block_my_certificates/issues)
- [Moodle.org Plugins Forum](https://moodle.org/plugins/)

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

Follow Moodle coding standards and include:
- PHPDoc comments
- Language strings for any new text
- Updates to README if needed

## Privacy

This plugin does not store any personal data beyond what is already stored by Moodle core and the Custom Certificate module. It only displays certificate information that already exists in the database.

### Data Storage

- No additional user data is stored
- No cookies are set
- No data is sent to external services

### GDPR Compliance

The plugin respects Moodle's privacy API and:
- Uses existing Custom Certificate data
- Does not create new personal data
- Respects user privacy settings

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

## Credits

- **Developed by**: Agiledrop
- **Maintained by**: [Your Organization]
- **PDF Rendering**: [PDF.js](https://mozilla.github.io/pdf.js/) by Mozilla
- **License**: GPL-3.0-or-later

## Changelog

### Version 1.0.1 (2025)
- Removed DataEU-specific styling
- Added generic, responsive card design
- Improved accessibility
- Added comprehensive documentation
- Ready for Moodle plugins directory submission

### Version 1.0.0 (2025)
- Initial release
- Certificate grid display
- PDF preview functionality
- Download links