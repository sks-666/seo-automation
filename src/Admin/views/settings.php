<?php
/**
 * Settings screen.
 *
 * @package SEOAgent
 *
 * @var array<string,mixed>                       $settings   Current settings.
 * @var array<string,\WP_Post_Type>               $post_types Public post types.
 * @var array<string,\WP_Taxonomy>                $taxonomies Public taxonomies.
 * @var string|false                              $new_token  A freshly generated token, shown once.
 * @var \SEOAgent\Seo\SeoAdapterInterface         $seo        Active metadata adapter.
 */

use SEOAgent\Admin\AdminMenu;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- This template is include()d from inside a class method, so the variables below are function-scoped locals, not globals.
?>
<div class="wrap">
	<h1><?php esc_html_e( 'SEO Audit settings', 'seo-audit-content-ai-assistant' ); ?></h1>

	<?php if ( $new_token ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Your agent token — copy it now, it is not shown again.', 'seo-audit-content-ai-assistant' ); ?></strong></p>
			<p><code style="font-size:14px;user-select:all"><?php echo esc_html( $new_token ); ?></code></p>
			<p><?php esc_html_e( 'Send it as the X-SEO-Agent-Token header alongside normal WordPress authentication.', 'seo-audit-content-ai-assistant' ); ?></p>
		</div>
	<?php endif; ?>

	<?php AdminMenu::form_open( 'save_settings' ); ?>
	<div style="display:block">

		<h2><?php esc_html_e( 'How much the agent may do on its own', 'seo-audit-content-ai-assistant' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Autonomy', 'seo-audit-content-ai-assistant' ); ?></th>
				<td>
					<?php
					$modes = array(
						'review'    => array(
							__( 'Review everything', 'seo-audit-content-ai-assistant' ),
							__( 'Nothing is written without an explicit approval. Fixes can still be previewed freely.', 'seo-audit-content-ai-assistant' ),
						),
						'auto_safe' => array(
							__( 'Apply safe fixes automatically', 'seo-audit-content-ai-assistant' ),
							__( 'Deterministic fixes run unattended. Anything needing written copy still waits for approval.', 'seo-audit-content-ai-assistant' ),
						),
						'auto_all'  => array(
							__( 'Apply everything automatically', 'seo-audit-content-ai-assistant' ),
							__( 'Any fix with sufficient input is applied without asking. Every change remains reversible from the change log.', 'seo-audit-content-ai-assistant' ),
						),
					);

					foreach ( $modes as $value => $mode ) :
						?>
						<label style="display:block;margin-bottom:10px">
							<input type="radio" name="autonomy" value="<?php echo esc_attr( $value ); ?>" <?php checked( $settings['autonomy'], $value ); ?> />
							<strong><?php echo esc_html( $mode[0] ); ?></strong>
							<span class="description" style="display:block;margin-left:24px"><?php echo esc_html( $mode[1] ); ?></span>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Nightly audits', 'seo-audit-content-ai-assistant' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="scheduled_audits_enabled" value="1" <?php checked( ! empty( $settings['scheduled_audits_enabled'] ) ); ?> />
						<?php esc_html_e( 'Run a full audit once a day via WP-Cron', 'seo-audit-content-ai-assistant' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'What gets audited', 'seo-audit-content-ai-assistant' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Post types', 'seo-audit-content-ai-assistant' ); ?></th>
				<td>
					<?php foreach ( $post_types as $post_type ) : ?>
						<label style="display:inline-block;margin:0 16px 6px 0">
							<input type="checkbox" name="audit_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>"
								<?php checked( in_array( $post_type->name, (array) $settings['audit_post_types'], true ) ); ?> />
							<?php echo esc_html( $post_type->label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Taxonomies', 'seo-audit-content-ai-assistant' ); ?></th>
				<td>
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<label style="display:inline-block;margin:0 16px 6px 0">
							<input type="checkbox" name="audit_taxonomies[]" value="<?php echo esc_attr( $taxonomy->name ); ?>"
								<?php checked( in_array( $taxonomy->name, (array) $settings['audit_taxonomies'], true ) ); ?> />
							<?php echo esc_html( $taxonomy->label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Thresholds', 'seo-audit-content-ai-assistant' ); ?></h2>
		<p class="description"><?php esc_html_e( 'These define what counts as a problem. Defaults follow common practice; change them if your site has a reason to differ.', 'seo-audit-content-ai-assistant' ); ?></p>

		<table class="form-table" role="presentation">
			<?php
			$numbers = array(
				'title_min_length'       => __( 'Minimum title length', 'seo-audit-content-ai-assistant' ),
				'title_max_length'       => __( 'Maximum title length', 'seo-audit-content-ai-assistant' ),
				'description_min_length' => __( 'Minimum description length', 'seo-audit-content-ai-assistant' ),
				'description_max_length' => __( 'Maximum description length', 'seo-audit-content-ai-assistant' ),
				'min_word_count'         => __( 'Thin content below (words)', 'seo-audit-content-ai-assistant' ),
				'min_internal_links'     => __( 'Minimum internal links per page', 'seo-audit-content-ai-assistant' ),
				'batch_size'             => __( 'Objects per audit slice', 'seo-audit-content-ai-assistant' ),
				'request_timeout'        => __( 'HTTP timeout (seconds)', 'seo-audit-content-ai-assistant' ),
			);

			foreach ( $numbers as $key => $label ) :
				?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td>
						<input type="number" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>" class="small-text" min="0" />
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Core Web Vitals', 'seo-audit-content-ai-assistant' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="psi_api_key"><?php esc_html_e( 'PageSpeed Insights API key', 'seo-audit-content-ai-assistant' ); ?></label></th>
				<td>
					<?php $psi_is_set = '' !== trim( (string) $settings['psi_api_key'] ); ?>
					<input type="password" id="psi_api_key" name="psi_api_key" class="regular-text"
						value="" autocomplete="new-password"
						placeholder="<?php echo esc_attr( $psi_is_set ? __( 'A key is stored — leave blank to keep it', 'seo-audit-content-ai-assistant' ) : __( 'Not set', 'seo-audit-content-ai-assistant' ) ); ?>" />
					<?php if ( $psi_is_set ) : ?>
						<p>
							<label>
								<input type="checkbox" name="psi_api_key_remove" value="1" />
								<?php esc_html_e( 'Remove the stored key', 'seo-audit-content-ai-assistant' ); ?>
							</label>
						</p>
					<?php endif; ?>
					<p class="description">
						<?php esc_html_e( 'With a key, Core Web Vitals are reported from the field data Google holds for real visits to your site. Without one, the plugin falls back to inspecting your markup for the known causes of poor vitals, and labels those findings as diagnostic rather than measured.', 'seo-audit-content-ai-assistant' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'seo-audit-content-ai-assistant' ); ?></button>
		</p>
	</div>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Agent access', 'seo-audit-content-ai-assistant' ); ?></h2>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'REST endpoint', 'seo-audit-content-ai-assistant' ); ?></th>
			<td><code><?php echo esc_html( rest_url( 'seo-agent/v1' ) ); ?></code></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Metadata adapter', 'seo-audit-content-ai-assistant' ); ?></th>
			<td>
				<?php echo esc_html( $seo->label() ); ?>
				<p class="description"><?php esc_html_e( 'Detected automatically. Titles and descriptions are read and written through this plugin, so fixes appear wherever you normally edit them.', 'seo-audit-content-ai-assistant' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Token', 'seo-audit-content-ai-assistant' ); ?></th>
			<td>
				<?php if ( '' !== trim( (string) ( $settings['agent_token_hash'] ?? '' ) ) ) : ?>
					<p><?php esc_html_e( 'A token is configured and required on every API call.', 'seo-audit-content-ai-assistant' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'No token configured. API calls need only a WordPress application password and the plugin\'s own audit capability.', 'seo-audit-content-ai-assistant' ); ?></p>
				<?php endif; ?>

				<?php AdminMenu::form_open( 'generate_token' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Generate a new token', 'seo-audit-content-ai-assistant' ); ?></button>
				</form>

				<p class="description">
					<?php esc_html_e( 'The token is an extra factor on top of normal authentication, never a replacement for it. Only its hash is stored, so a database leak does not expose it.', 'seo-audit-content-ai-assistant' ); ?>
				</p>
			</td>
		</tr>
	</table>
</div>
