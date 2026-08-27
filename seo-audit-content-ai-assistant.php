<?php
/**
 * Plugin Name:       SEO Audit and Content AI Assistant
 * Plugin URI:        https://nexcove.co.uk/apps/seo-audit-content-ai-assistant
 * Description:       A comprehensive SEO automation suite: research, write, optimize, audit, fix, and verify SEO improvements from one integrated system.
 * Version:           2.1.0
 * Author:            SSOMAI
 * Author URI:        https://nexcove.co.uk/digital
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       seo-audit-content-ai-assistant
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package SEOACAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------
// SEOACAI_* are the plugin's own constants. THEBLOG_* / SEO_AGENT_*
// are kept as aliases so any external code (child themes, mu-plugins)
// written against the pre-merge plugins keeps working unchanged.
//
// Every define() below is guarded with defined() so that if the old
// standalone "TheBlog Automation" or "SEO Agent" plugins are still
// active at the same time, we never throw redefinition warnings and we
// never silently steal/overwrite constants they rely on.

if ( ! defined( 'SEOACAI_VERSION' ) ) {
	define( 'SEOACAI_VERSION', '2.1.0' );
}
if ( ! defined( 'SEOACAI_FILE' ) ) {
	define( 'SEOACAI_FILE', __FILE__ );
}
if ( ! defined( 'SEOACAI_DIR' ) ) {
	define( 'SEOACAI_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SEOACAI_URL' ) ) {
	define( 'SEOACAI_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'SEOACAI_BASENAME' ) ) {
	define( 'SEOACAI_BASENAME', plugin_basename( __FILE__ ) );
}

// Back-compat aliases for code still referencing the pre-merge constants.
if ( ! defined( 'THEBLOG_VERSION' ) ) {
	define( 'THEBLOG_VERSION', SEOACAI_VERSION );
}
if ( ! defined( 'THEBLOG_FILE' ) ) {
	define( 'THEBLOG_FILE', SEOACAI_FILE );
}
if ( ! defined( 'THEBLOG_DIR' ) ) {
	define( 'THEBLOG_DIR', SEOACAI_DIR );
}
if ( ! defined( 'THEBLOG_URL' ) ) {
	define( 'THEBLOG_URL', SEOACAI_URL );
}
if ( ! defined( 'THEBLOG_BASENAME' ) ) {
	define( 'THEBLOG_BASENAME', SEOACAI_BASENAME );
}

if ( ! defined( 'SEO_AGENT_VERSION' ) ) {
	define( 'SEO_AGENT_VERSION', SEOACAI_VERSION );
}
if ( ! defined( 'SEO_AGENT_FILE' ) ) {
	define( 'SEO_AGENT_FILE', SEOACAI_FILE );
}
if ( ! defined( 'SEO_AGENT_DIR' ) ) {
	define( 'SEO_AGENT_DIR', SEOACAI_DIR );
}
if ( ! defined( 'SEO_AGENT_URL' ) ) {
	define( 'SEO_AGENT_URL', SEOACAI_URL );
}

// -----------------------------------------------------------------------
// Legacy plugin conflict guard
// -----------------------------------------------------------------------
// SEO Audit and Content AI Assistant fully replaces the standalone "TheBlog Automation" and
// "SEO Agent" plugins, and supersedes the "SEO Suite" build this plugin
// was previously released as — it is not designed to run alongside any of
// them (they fight over the same constants, CPTs, cron hooks and option
// keys). If a conflicting plugin is still active, bail out of booting
// SEO Audit and Content AI Assistant's own hooks (rather than fatal on a stale path) and show
// an admin notice telling the site owner to deactivate the old plugin(s).

/**
 * Detect whether a superseded build of this plugin is still active.
 *
 * @return string[] Plugin basenames (relative to wp-content/plugins) that
 *                   are active and conflict with SEO Audit and Content AI Assistant.
 */
function seoacai_conflicting_plugins() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$candidates = array(
		'theblog-automation/theblog-automation.php',
		'seo-agent/seo-agent.php',
		'seo-suite/seo-suite.php',
		'seo-automation/seo-automation.php',
	);

	$active = array();
	foreach ( $candidates as $plugin ) {
		if ( SEOACAI_BASENAME !== $plugin && is_plugin_active( $plugin ) ) {
			$active[] = $plugin;
		}
	}

	return $active;
}

/**
 * Show an admin notice pointing at the conflicting legacy plugin(s).
 */
function seoacai_conflict_notice() {
	// Keep the notice where it is actionable — the Plugins screen (where the
	// old plugin is deactivated), the Dashboard, and this plugin's own
	// screens (which are inert while a conflict is unresolved). Every other
	// admin page is left alone.
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen instanceof WP_Screen ) {
		return;
	}

	$is_relevant_screen = in_array( $screen->id, array( 'dashboard', 'plugins', 'plugins-network' ), true )
		|| false !== strpos( $screen->id, 'seo-agent' )
		|| false !== strpos( $screen->id, 'theblog' );

	if ( ! $is_relevant_screen ) {
		return;
	}

	$conflicts = seoacai_conflicting_plugins();

	if ( empty( $conflicts ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p><p>%3$s</p></div>',
		esc_html__( 'SEO Audit and Content AI Assistant:', 'seo-audit-content-ai-assistant' ),
		esc_html__( 'SEO Audit and Content AI Assistant replaces the standalone TheBlog Automation and SEO Agent plugins, and supersedes the earlier SEO Suite build. They cannot be active at the same time — deactivate the old plugin(s) below to avoid conflicts and fatal errors.', 'seo-audit-content-ai-assistant' ),
		esc_html( implode( ', ', $conflicts ) )
	);
}
add_action( 'admin_notices', 'seoacai_conflict_notice' );

// -----------------------------------------------------------------------
// Autoloading
// -----------------------------------------------------------------------
// Single PSR-4-style autoloader for the whole SEOAgent\ namespace tree,
// which now includes both the audit engine (SEOAgent\*) and the content
// generation engine (SEOAgent\Blog\*).
//
// The namespace deliberately keeps its original SEOAgent\ name: it is
// internal, and renaming it would break every third-party integration
// hooked onto the published class names for no user-visible gain.
require_once SEOACAI_DIR . 'src/autoload.php';

// -----------------------------------------------------------------------
// Activation / Deactivation
// -----------------------------------------------------------------------

/**
 * Activate both feature sets: audit engine tables/cron/capability, and
 * content pipeline defaults.
 */
function seoacai_activate() {
	// Refuse to activate cleanly alongside the plugins this replaces —
	// their activation hooks already created the CPTs/cron/tables this
	// plugin also creates, so running both would duplicate state.
	if ( ! empty( seoacai_conflicting_plugins() ) ) {
		return;
	}

	if ( class_exists( 'SEOAgent\\Blog\\Activator' ) ) {
		\SEOAgent\Blog\Activator::activate();
	}

	if ( class_exists( 'SEOAgent\\Plugin' ) ) {
		\SEOAgent\Plugin::activate();
	}
}

/**
 * Deactivate both feature sets. Data is preserved; only scheduled
 * cron events are cleared.
 */
function seoacai_deactivate() {
	if ( class_exists( 'SEOAgent\\Blog\\Deactivator' ) ) {
		\SEOAgent\Blog\Deactivator::deactivate();
	}

	if ( class_exists( 'SEOAgent\\Plugin' ) ) {
		\SEOAgent\Plugin::deactivate();
	}
}

register_activation_hook( __FILE__, 'seoacai_activate' );
register_deactivation_hook( __FILE__, 'seoacai_deactivate' );

// -----------------------------------------------------------------------
// Boot
// -----------------------------------------------------------------------

/**
 * Boot the unified SEO Audit and Content AI Assistant plugin: content generation + SEO auditing.
 *
 * A single admin menu is registered by SEOAgent\Admin\AdminMenu, which
 * attaches the content-generation screens (SEOAgent\Blog\Admin) as
 * submenus of its own top-level "SEO Audit and Content AI Assistant" menu.
 */
function seoacai_run() {
	// Don't double-boot: if a legacy standalone plugin is still active it
	// already registered its own CPTs/cron/menus. Booting SEO Audit and Content AI Assistant's
	// copies too would duplicate all of that. The admin notice above
	// tells the site owner to deactivate the old plugin(s); once that's
	// done this function runs normally on the next request.
	if ( ! empty( seoacai_conflicting_plugins() ) ) {
		return;
	}

	// Content generation engine.
	if ( class_exists( 'SEOAgent\\Blog\\CPT_Topic' ) ) {
		\SEOAgent\Blog\CPT_Topic::init();
	}
	if ( class_exists( 'SEOAgent\\Blog\\Scheduler' ) ) {
		\SEOAgent\Blog\Scheduler::init();
	}
	if ( class_exists( 'SEOAgent\\Blog\\Review_Queue' ) ) {
		\SEOAgent\Blog\Review_Queue::init();
	}

	// SEO audit engine (also boots its own admin menu, which pulls in
	// the content-generation screens as submenus — see AdminMenu::add_menu()).
	if ( class_exists( 'SEOAgent\\Plugin' ) ) {
		\SEOAgent\Plugin::instance()->boot();
	}

	if ( is_admin() ) {
		if ( class_exists( 'SEOAgent\\Blog\\Admin' ) ) {
			\SEOAgent\Blog\Admin::init();
		}
		if ( class_exists( 'SEOAgent\\Blog\\Ajax' ) ) {
			\SEOAgent\Blog\Ajax::init();
		}
	}
}

add_action( 'plugins_loaded', 'seoacai_run' );
