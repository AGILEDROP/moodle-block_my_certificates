# My Certificates Block

My Certificates is a Moodle block plugin that displays user-earned certificates in a clean, card-based grid with live PDF previews. It integrates with the Custom Certificate module to provide a single, visual place for learners to view and download their certificates.

Ideal for dashboards, course pages, and the site home page, it highlights completed certificates and optionally shows other certificates users can unlock next.

## Key Features

- **Certificate Grid**: Displays earned certificates in a responsive card layout
- **PDF Previews**: Renders certificate previews in-browser with PDF.js
- **Download Links**: One-click access to the official certificate PDF
- **Configurable Empty State**: Custom “no certificates” message per block instance
- **Optional “Unlock More” Section**: Show additional available certificates
- **Accessible Markup**: Semantic structure and ARIA-friendly output

## Requirements

- Moodle 4.5 or higher
- PHP 8.1 or higher
- [Custom Certificate module](https://moodle.org/plugins/mod_customcert) (mod_customcert) version 2024042212 or higher

## Installation

### Installing via uploaded ZIP file

1. Log in as an admin and go to Site administration > Plugins > Install plugins.
2. Upload the ZIP file with the plugin code.
3. Check the plugin validation report and finish the installation.

### Installing manually

Copy the plugin directory to:

```
{your/moodle/dirroot}/blocks/my_certificates
```

Then visit Site administration > Notifications to complete the installation.

Alternatively, you can run:

```
$ php admin/cli/upgrade.php
```

## Usage

### Adding the block

1. Enable editing on your dashboard, course page, or site home page.
2. Click “Add a block”.
3. Select “My Certificates”.

### Block settings

In the block configuration you can:

- Set the **No certificates text** message (HTML supported)
- Enable/disable the **Show all certificates** section

## Notes

- The block does not create new database tables.
- All certificate data is read from Custom Certificate tables.
- PDF rendering is client-side using PDF.js.

## Privacy

This plugin does not store personal data. It only displays existing data from Moodle core and the Custom Certificate module. It implements the Moodle Privacy API as a null provider.

## License

2026 Agiledrop ltd. developer@agiledrop.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

You should have received a copy of the GNU General Public License along
with this program. If not, see https://www.gnu.org/licenses/.
