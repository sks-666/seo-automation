<?php

namespace SEOAgent\Blog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- This template is include()d from inside a class method, so the variables below are function-scoped locals, not globals.

$topics          = CPT_Topic::get_all( 100 );
$statuses        = CPT_Topic::statuses();
$content_sources = CPT_Topic::content_sources();
$content_types   = CPT_Topic::content_types();
$article_lengths = CPT_Topic::article_lengths();
$wc_active       = WooCommerce::is_active();
?>
<div class="wrap theblog-wrap">
	<h1><?php esc_html_e( 'Add Content', 'seo-audit-content-ai-assistant' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Generate from a topic/keyword, or ground an article in a WooCommerce product\'s own description. Autopilot picks queued items up automatically on schedule, or click "Generate Now" to run the pipeline immediately.', 'seo-audit-content-ai-assistant' ); ?></p>

	<div class="theblog-panel">
		<h2><?php esc_html_e( 'Content Source', 'seo-audit-content-ai-assistant' ); ?></h2>

		<div class="theblog-source-toggle" role="radiogroup" aria-label="<?php esc_attr_e( 'Content Source', 'seo-audit-content-ai-assistant' ); ?>">
			<label class="theblog-toggle-option">
				<input type="radio" name="content_source_ui" value="topic" checked />
				<?php echo esc_html( $content_sources['topic'] ); ?>
			</label>
			<label class="theblog-toggle-option <?php echo $wc_active ? '' : 'theblog-toggle-disabled'; ?>">
				<input type="radio" name="content_source_ui" value="product_url" <?php disabled( ! $wc_active ); ?> />
				<?php echo esc_html( $content_sources['product_url'] ); ?>
			</label>
		</div>

		<?php if ( ! $wc_active ) : ?>
			<p class="description theblog-warning-text"><?php esc_html_e( 'WooCommerce is not active on this site, so product-based generation is unavailable. The Topic / Keyword workflow below is unaffected.', 'seo-audit-content-ai-assistant' ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="theblog-add-content-form">
			<?php wp_nonce_field( 'theblog_add_topic' ); ?>
			<input type="hidden" name="action" value="theblog_add_topic" />
			<input type="hidden" name="content_source" id="theblog-content-source" value="topic" />

			<!-- Topic / Keyword mode -->
			<table class="form-table theblog-mode-panel" id="theblog-mode-topic">
				<tr>
					<th><label for="keyword"><?php esc_html_e( 'Topic / Keyword', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="text" id="keyword" name="keyword" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. best productivity apps for remote teams', 'seo-audit-content-ai-assistant' ); ?>" /></td>
				</tr>
			</table>

			<!-- WooCommerce Product mode -->
			<table class="form-table theblog-mode-panel" id="theblog-mode-product" style="display:none;">
				<tr>
					<th><label for="theblog-product-url-input"><?php esc_html_e( 'Product URL', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<input type="url" id="theblog-product-url-input" class="regular-text" placeholder="https://example.com/product/example/" />
						<button type="button" class="button" id="theblog-analyze-product"><?php esc_html_e( 'Analyze Product', 'seo-audit-content-ai-assistant' ); ?></button>
						<span class="spinner theblog-inline-spinner" id="theblog-analyze-spinner"></span>
						<p class="description"><?php esc_html_e( 'The product is resolved directly from WordPress/WooCommerce — no scraping.', 'seo-audit-content-ai-assistant' ); ?></p>
						<div id="theblog-analyze-result" class="theblog-analyze-result" style="display:none;"></div>
						<div id="theblog-analyze-error" class="theblog-analyze-error" style="display:none;"></div>
					</td>
				</tr>
				<tr class="theblog-product-fields" style="display:none;">
					<th><label for="theblog-primary-keyword"><?php esc_html_e( 'Primary Keyword', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<input type="text" id="theblog-primary-keyword" name="primary_keyword" class="regular-text" />
						<p class="description" id="theblog-secondary-keywords-display"></p>
						<p class="description"><?php esc_html_e( 'AI-suggested keyword candidates, not verified search-volume data. Edit if you know better.', 'seo-audit-content-ai-assistant' ); ?></p>
					</td>
				</tr>
				<tr class="theblog-product-fields" style="display:none;">
					<th><label for="content_type"><?php esc_html_e( 'Content Type', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<select name="content_type" id="content_type">
							<?php foreach ( $content_types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="theblog-product-fields" style="display:none;">
					<th><label for="article_length"><?php esc_html_e( 'Article Length', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<select name="article_length" id="article_length">
							<?php foreach ( $article_lengths as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, '1500-2000' ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="theblog-product-fields" style="display:none;">
					<th><?php esc_html_e( 'SEO', 'seo-audit-content-ai-assistant' ); ?></th>
					<td>
						<label><input type="checkbox" name="seo_rank_math" value="1" checked /> <?php esc_html_e( 'Optimize for Rank Math', 'seo-audit-content-ai-assistant' ); ?></label><br>
						<label><input type="checkbox" name="seo_yoast" value="1" checked /> <?php esc_html_e( 'Optimize for Yoast SEO', 'seo-audit-content-ai-assistant' ); ?></label><br>
						<label><input type="checkbox" name="seo_faq" value="1" checked /> <?php esc_html_e( 'Generate FAQ', 'seo-audit-content-ai-assistant' ); ?></label><br>
						<label><input type="checkbox" name="seo_internal_links" value="1" checked /> <?php esc_html_e( 'Add internal links', 'seo-audit-content-ai-assistant' ); ?></label><br>
						<label><input type="checkbox" name="seo_alt_text" value="1" checked /> <?php esc_html_e( 'Generate image alt text', 'seo-audit-content-ai-assistant' ); ?></label>
					</td>
				</tr>
				<input type="hidden" name="product_id" id="theblog-product-id" value="" />
				<input type="hidden" name="product_title" id="theblog-product-title" value="" />
				<input type="hidden" name="product_url" id="theblog-product-url" value="" />
				<input type="hidden" name="secondary_keywords" id="theblog-secondary-keywords" value="" />
			</table>

			<table class="form-table">
				<tr>
					<th><label for="scheduled_at"><?php esc_html_e( 'Schedule For', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<input type="datetime-local" id="scheduled_at" name="scheduled_at" />
						<p class="description"><?php esc_html_e( 'Leave blank to make it available immediately (still requires "Generate Now" or an autopilot run).', 'seo-audit-content-ai-assistant' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Add to Queue', 'seo-audit-content-ai-assistant' ), 'primary', 'submit', true, array( 'id' => 'theblog-add-to-queue' ) ); ?>
		</form>
	</div>

	<div class="theblog-panel">
		<h2><?php esc_html_e( 'Queue', 'seo-audit-content-ai-assistant' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Topic / Product', 'seo-audit-content-ai-assistant' ); ?></th>
					<th><?php esc_html_e( 'Source', 'seo-audit-content-ai-assistant' ); ?></th>
					<th><?php esc_html_e( 'Primary Keyword', 'seo-audit-content-ai-assistant' ); ?></th>
					<th><?php esc_html_e( 'Status', 'seo-audit-content-ai-assistant' ); ?></th>
					<th><?php esc_html_e( 'Scheduled', 'seo-audit-content-ai-assistant' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'seo-audit-content-ai-assistant' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $topics ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'Nothing queued yet. Add a topic or product above.', 'seo-audit-content-ai-assistant' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $topics as $topic ) :
					$status       = CPT_Topic::get_status( $topic->ID );
					$source       = CPT_Topic::get_content_source( $topic->ID );
					$primary_kw   = get_post_meta( $topic->ID, '_theblog_primary_keyword', true );
					$scheduled_at = get_post_meta( $topic->ID, '_theblog_scheduled_at', true );
					$post_id      = (int) get_post_meta( $topic->ID, '_theblog_generated_post_id', true );
					$error        = get_post_meta( $topic->ID, '_theblog_error', true );
					$can_generate = in_array( $status, array( 'queued', 'error' ), true ) || CPT_Topic::is_stuck_processing( $topic->ID );
					// Deliberately excludes 'published': that status means the
					// generated post already went live, and regenerating would
					// overwrite _theblog_generated_post_id with a new draft's
					// ID, silently losing this row's link back to the live post.
					$can_regenerate = in_array( $status, array( 'ready', 'rejected' ), true );
					?>
					<tr>
						<td><strong><?php echo esc_html( $topic->post_title ); ?></strong>
							<?php if ( $error ) : ?>
								<br><span class="theblog-error-text"><?php echo esc_html( $error ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $content_sources[ $source ] ?? $source ); ?></td>
						<td><?php echo esc_html( $primary_kw ); ?></td>
						<td><span class="theblog-status theblog-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $statuses[ $status ] ?? $status ); ?></span></td>
						<td><?php echo esc_html( $scheduled_at ); ?></td>
						<td>
							<?php if ( $can_generate || $can_regenerate ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'theblog_generate_' . $topic->ID ); ?>
									<input type="hidden" name="action" value="theblog_generate_now" />
									<input type="hidden" name="topic_id" value="<?php echo (int) $topic->ID; ?>" />
									<button type="submit" class="button button-secondary">
										<?php
										if ( 'processing' === $status ) {
											esc_html_e( 'Retry (stuck)', 'seo-audit-content-ai-assistant' );
										} elseif ( $can_regenerate ) {
											esc_html_e( 'Generate Again', 'seo-audit-content-ai-assistant' );
										} else {
											esc_html_e( 'Generate Now', 'seo-audit-content-ai-assistant' );
										}
										?>
									</button>
								</form>
							<?php endif; ?>

							<?php if ( $post_id ) : ?>
								<a class="button" href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php esc_html_e( 'View Draft', 'seo-audit-content-ai-assistant' ); ?></a>
							<?php endif; ?>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this queue entry?', 'seo-audit-content-ai-assistant' ) ); ?>');">
								<?php wp_nonce_field( 'theblog_delete_topic_' . $topic->ID ); ?>
								<input type="hidden" name="action" value="theblog_delete_topic" />
								<input type="hidden" name="topic_id" value="<?php echo (int) $topic->ID; ?>" />
								<button type="submit" class="button-link-delete theblog-link-delete"><?php esc_html_e( 'Delete', 'seo-audit-content-ai-assistant' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
