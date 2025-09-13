=== Astra & Elementor Sync Bridge ===
Contributors: alihamza
Donate link: https://alihamza.work/astra-elementor-sync/
Tags: astra, elementor, sync, colors, typography
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 40.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple and effective plugin to synchronize your Astra Theme settings with Elementor Global Styles.

== Description ==
The Astra & Elementor Sync Bridge plugin is designed to streamline your design workflow by automatically synchronizing your key Astra Theme settings directly into Elementor's Global Styles. This ensures a consistent look and feel across your entire website without the need for manual color and typography adjustments.

With this plugin, you can easily synchronize:

Global Colors: Map your Astra-defined colors for headings, links, body text, and accents to Elementor's Global Colors.

Global Typography: Sync font family, size, weight, and other typography settings to Elementor.

The plugin provides a simple interface in the WordPress dashboard to trigger the synchronization with a single click.

For more details and documentation, visit the plugin's official page:
https://alihamza.work/astra-elementor-sync/

== Installation ==

Download the plugin files and unzip the folder.

Upload the astra-elementor-sync folder to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

== How to Use ==

After activating the plugin, go to Settings > Astra & Elementor Sync in your WordPress dashboard.

On the plugin page, you will see two buttons: "Sync Colors" and "Sync Typography".

Click the respective button to synchronize your Astra settings with Elementor's Global Styles.

Once the synchronization is complete, a success message will appear. You can then check Elementor's Global Styles to see the changes.

== Changelog ==
= 40.0.0 - 2025-09-13 =

Fix: Resolved a critical bug where the Accent color was not synchronizing correctly due to an incorrect database key. The plugin now uses the theme-color key for the Accent color.

Improvement: Replaced the direct database option lookup with Astra's astra_get_option() function for more reliable and future-proof data retrieval.

Fix: Addressed the OutputNotEscaped security issue by properly escaping the CSS output.

= 39.0.0 - 2025-09-12 =

Fix: Corrected the color mapping to align with user-requested logic.

Primary Color now syncs with Astra's Heading color.

Secondary Color now syncs with Astra's Link color.

Text Color now syncs with Astra's Body Text color.

Accent Color now syncs with Astra's Accent color.

Improvement: The plugin now uses more specific keys for color retrieval to handle different Astra configurations.

= 38.0.0 - 2025-09-11 =

Initial release with core functionality for synchronizing Astra's color and typography settings with Elementor's Global Styles.

Includes manual CSS injection to fix typography inheritance issues.

== Frequently Asked Questions ==
= Does this plugin work with Elementor Pro? =
Yes, this plugin is fully compatible with both the free and Pro versions of Elementor.

= What happens if I change my settings in Astra after syncing? =
You will need to re-run the synchronization from the plugin's settings page to update Elementor with the latest changes.

= Can I choose which settings to sync? =
Currently, the plugin provides separate buttons for synchronizing colors and typography. Future versions may include more granular control.