<?php

namespace SEOAgent\Blog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- This template is include()d from inside a class method, so the variables below are function-scoped locals, not globals.

$settings   = Settings::all();
$topics     = CPT_Topic::get_all( 200 );
$counts     = array_fill_keys( array_keys( CPT_Topic::statuses() ), 0 );
foreach ( $topics as $t ) {
	$status = CPT_Topic::get_status( $t->ID );
	if ( isset( $counts[ $status ] ) ) {
		$counts[ $status ]++;
	}
}
$pending_review = count( Review_Queue::get_pending_posts( 200 ) );
$provider       = AI_Client::text_provider();
$provider_ready = $provider && $provider->is_configured();
?>
<div class="wrap theblog-wrap">
	<h1><?php esc_html_e( 'Content Automation', 'seo-audit-content-ai-assistant' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Research, write, optimize, and publish from one system. Run on autopilot or stay hands-on.', 'seo-audit-content-ai-assistant' ); ?></p>

	<?php if ( ! $provider_ready ) : ?>
		<div class="notice notice-warning"><p>
			<?php
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=theblog-settings' ) ) . '">' . esc_html__( 'Settings', 'seo-audit-content-ai-assistant' ) . '</a>';

			if ( $provider instanceof Provider_WP_AI_Client ) {
				printf(
					/* translators: %s: link to the plugin's Settings screen. */
					esc_html__( 'No AI provider is connected in WordPress yet. Connect one under Settings → Connectors, or pick a provider directly on the %s screen.', 'seo-audit-content-ai-assistant' ),
					$settings_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_url() and esc_html__() immediately above.
				);
			} else {
				printf(
					/* translators: %s: link to the plugin's Settings screen. */
					esc_html__( 'No AI provider is configured yet. Add an API key on the %s screen to start generating content.', 'seo-audit-content-ai-assistant' ),
					$settings_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_url() and esc_html__() immediately above.
				);
			}
			?>
		</p></div>
	<?php endif; ?>

	<div class="theblog-cards">
		<div class="theblog-card">
			<span class="theblog-card-number"><?php echo (int) $counts['queued']; ?></span>
			<span class="theblog-card-label"><?php esc_html_e( 'Queued Topics', 'seo-audit-content-ai-assistant' ); ?></span>
		</div>
		<div class="theblog-card">
			<span class="theblog-card-number"><?php echo (int) $pending_review; ?></span>
			<span class="theblog-card-label"><?php esc_html_e( 'Awaiting Your Review', 'seo-audit-content-ai-assistant' ); ?></span>
		</div>
		<div class="theblog-card">
			<span class="theblog-card-number"><?php echo (int) $counts['published']; ?></span>
			<span class="theblog-card-label"><?php esc_html_e( 'Published by TheBlog', 'seo-audit-content-ai-assistant' ); ?></span>
		</div>
		<div class="theblog-card">
			<span class="theblog-card-number"><?php echo (int) $counts['error']; ?></span>
			<span class="theblog-card-label"><?php esc_html_e( 'Errors', 'seo-audit-content-ai-assistant' ); ?></span>
		</div>
	</div>

	<div class="theblog-panel">
		<h2><?php esc_html_e( 'Status', 'seo-audit-content-ai-assistant' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'AI Provider', 'seo-audit-content-ai-assistant' ); ?></td>
					<td><?php echo esc_html( $provider ? $provider->get_name() : '-' ); ?> — <?php echo $provider_ready ? esc_html__( 'Configured', 'seo-audit-content-ai-assistant' ) : esc_html__( 'Not configured', 'seo-audit-content-ai-assistant' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Autopilot', 'seo-audit-content-ai-assistant' ); ?></td>
					<td><?php echo $settings['autopilot_enabled'] ? esc_html__( 'Enabled', 'seo-audit-content-ai-assistant' ) : esc_html__( 'Disabled', 'seo-audit-content-ai-assistant' ); ?> (<?php echo esc_html( $settings['autopilot_interval'] ); ?>)</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Approval Gate', 'seo-audit-content-ai-assistant' ); ?></td>
					<td><?php esc_html_e( 'Always on — every generated post lands as "Pending Review" until you approve it.', 'seo-audit-content-ai-assistant' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'SEO Integration', 'seo-audit-content-ai-assistant' ); ?></td>
					<td>
						<?php
						if ( SEO_Integration::is_yoast_active() ) {
							esc_html_e( 'Yoast SEO detected — metadata is pushed there.', 'seo-audit-content-ai-assistant' );
						} elseif ( SEO_Integration::is_rankmath_active() ) {
							esc_html_e( 'Rank Math detected — metadata is pushed there.', 'seo-audit-content-ai-assistant' );
						} else {
							esc_html_e( 'No SEO plugin detected — using built-in fallback meta tags.', 'seo-audit-content-ai-assistant' );
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=theblog-topics' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add a Topic', 'seo-audit-content-ai-assistant' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=theblog-review-queue' ) ); ?>" class="button"><?php esc_html_e( 'Go to Review Queue', 'seo-audit-content-ai-assistant' ); ?></a>
	</p>
</div>
