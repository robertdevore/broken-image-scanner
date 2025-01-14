# Broken Image Scanner
The **Enhanced Broken Image Scanner** plugin helps you identify broken image URLs across all public content types on your WordPress® site. 

It provides a modern interface with a progress bar, dynamically updates scan results, and allows you to download the scan data as a CSV file for easy management.

## Features

- Scans for broken images in posts, pages, and all public custom post types.
- Real-time progress bar during scans.
- Displays a table of broken image URLs with direct links to edit affected posts.
- Generates a CSV file of the scan results with a single click.

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install the plugin through the  Plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress®.
3. Navigate to the **Image Scanner** page in your  admin menu to start scanning.

## Usage

1. Go to the **Image Scanner** page in the WordPress® admin menu.
2. Click the **Start Scan** button to initiate the scan.
3. Monitor the real-time progress bar as the plugin scans your site.
4. View the broken image URLs in a detailed table.
5. Click the **Download CSV** button (replacing the **Start Scan** button) to export the scan results.

### CSV File Format

The CSV file includes two columns:
- **Post Title**: The title of the post containing the broken image.
- **Broken URL**: The URL of the broken image.

## Frequently Asked Questions (FAQ)

**What does this plugin scan?**
The plugin scans all posts, pages, and public custom post types for `<img>` tags and checks if the image URLs are reachable.

**What happens if the scan is interrupted?**
You can restart the scan at any time. The plugin works in batches, so it won’t overload your server.

**How does the plugin check for broken images?**
It sends a `HEAD` request to each image URL and checks the HTTP response. If the response is not `200 OK`, the image is flagged as broken.

**Can I edit posts directly from the scan results?**
Yes, the table includes links to the WordPress editor for each affected post, making it easy to fix broken images.

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
