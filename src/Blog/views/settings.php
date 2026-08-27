<?php

namespace SEOAgent\Blog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- This template is include()d from inside a class method, so the variables below are function-scoped locals, not globals.

$s = Settings::all();

/**
 * Render a write-only credential field.
 *
 * The stored value is never echoed back into the page: the field renders
 * empty and a blank submission means "keep the stored key". Removal is an
 * explicit checkbox, so saving an unrelated setting can never wipe a key.
 *
 * @param string $name  Setting key / input name.
 * @param string $value Currently stored value.
 */
$theblog_secret_field = static function ( $name, $value ) {
	$is_set = '' !== trim( (string) $value );
	?>
	<input type="password" autocomplete="new-password" class="regular-text"
		id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value=""
		placeholder="<?php echo esc_attr( $is_set ? __( 'A key is stored — leave blank to keep it', 'seo-audit-content-ai-assistant' ) : __( 'Not set', 'seo-audit-content-ai-assistant' ) ); ?>" />
	<?php if ( $is_set ) : ?>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>_remove" value="1" />
				<?php esc_html_e( 'Remove the stored key', 'seo-audit-content-ai-assistant' ); ?>
			</label>
		</p>
	<?php endif; ?>
	<?php
};
?>
<div class="wrap theblog-wrap">
	<h1><?php esc_html_e( 'Content Settings', 'seo-audit-content-ai-assistant' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'theblog_save_settings' ); ?>
		<input type="hidden" name="action" value="theblog_save_settings" />

		<div class="theblog-panel">
			<h2><?php esc_html_e( 'AI Provider', 'seo-audit-content-ai-assistant' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="ai_provider"><?php esc_html_e( 'Text Provider', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<select name="ai_provider" id="ai_provider">
							<?php if ( Provider_WP_AI_Client::is_available() ) : ?>
								<option value="wp_ai_client" <?php selected( $s['ai_provider'], 'wp_ai_client' ); ?>><?php esc_html_e( 'WordPress AI (recommended) — uses the provider connected in WordPress', 'seo-audit-content-ai-assistant' ); ?></option>
							<?php endif; ?>
							<option value="anthropic" <?php selected( $s['ai_provider'], 'anthropic' ); ?>>Anthropic (Claude)</option>
							<option value="openai" <?php selected( $s['ai_provider'], 'openai' ); ?>>OpenAI (GPT)</option>
							<option value="deepseek" <?php selected( $s['ai_provider'], 'deepseek' ); ?>>DeepSeek</option>
						</select>
						<p class="description"><?php esc_html_e( 'Used for research, writing, and SEO metadata.', 'seo-audit-content-ai-assistant' ); ?></p>
						<?php if ( Provider_WP_AI_Client::is_available() ) : ?>
							<p class="description">
								<?php esc_html_e( 'With WordPress AI selected, WordPress holds the credentials and this plugin never stores an API key. Connect a provider under Settings → Connectors. The keys below are only needed if you pick a provider directly.', 'seo-audit-content-ai-assistant' ); ?>
							</p>
						<?php else : ?>
							<p class="description">
								<?php esc_html_e( 'On WordPress 7.0 and later you can let WordPress hold the credentials instead of entering a key here.', 'seo-audit-content-ai-assistant' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="anthropic_api_key"><?php esc_html_e( 'Anthropic API Key', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><?php $theblog_secret_field( 'anthropic_api_key', $s['anthropic_api_key'] ); ?></td>
				</tr>
				<tr>
					<th><label for="anthropic_model"><?php esc_html_e( 'Anthropic Model', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="text" class="regular-text" id="anthropic_model" name="anthropic_model" value="<?php echo esc_attr( $s['anthropic_model'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><?php $theblog_secret_field( 'openai_api_key', $s['openai_api_key'] ); ?></td>
				</tr>
				<tr>
					<th><label for="openai_model"><?php esc_html_e( 'OpenAI Model', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="text" class="regular-text" id="openai_model" name="openai_model" value="<?php echo esc_attr( $s['openai_model'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="deepseek_api_key"><?php esc_html_e( 'DeepSeek API Key', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><?php $theblog_secret_field( 'deepseek_api_key', $s['deepseek_api_key'] ); ?></td>
				</tr>
				<tr>
					<th><label for="deepseek_model"><?php esc_html_e( 'DeepSeek Model', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="deepseek_model" name="deepseek_model" value="<?php echo esc_attr( $s['deepseek_model'] ); ?>" />
						<p class="description"><?php esc_html_e( 'e.g. deepseek-chat (fast, cheap) or deepseek-reasoner. DeepSeek does not offer image generation — pick OpenAI below for featured images if needed.', 'seo-audit-content-ai-assistant' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="theblog-panel">
			<h2><?php esc_html_e( 'Featured Images', 'seo-audit-content-ai-assistant' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="image_provider"><?php esc_html_e( 'Image Provider', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<select name="image_provider" id="image_provider">
							<option value="none" <?php selected( $s['image_provider'], 'none' ); ?>><?php esc_html_e( 'Disabled (skip featured images)', 'seo-audit-content-ai-assistant' ); ?></option>
							<?php if ( Provider_WP_AI_Client::is_available() ) : ?>
								<option value="wp_ai_client" <?php selected( $s['image_provider'], 'wp_ai_client' ); ?>><?php esc_html_e( 'WordPress AI (recommended) — uses the provider connected in WordPress', 'seo-audit-content-ai-assistant' ); ?></option>
							<?php endif; ?>
							<option value="openai" <?php selected( $s['image_provider'], 'openai' ); ?>>OpenAI (DALL·E) — <?php esc_html_e( 'uses the OpenAI key above', 'seo-audit-content-ai-assistant' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="image_model"><?php esc_html_e( 'Image Model', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="text" class="regular-text" id="image_model" name="image_model" value="<?php echo esc_attr( $s['image_model'] ); ?>" /></td>
				</tr>
			</table>
		</div>

		<div class="theblog-panel">
			<h2><?php esc_html_e( 'Autopilot', 'seo-audit-content-ai-assistant' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Enable Autopilot', 'seo-audit-content-ai-assistant' ); ?></th>
					<td>
						<label><input type="checkbox" name="autopilot_enabled" value="1" <?php checked( $s['autopilot_enabled'] ); ?> /> <?php esc_html_e( 'Automatically process due, queued topics on a schedule.', 'seo-audit-content-ai-assistant' ); ?></label>
						<p class="description"><?php esc_html_e( 'Autopilot never publishes directly — every draft still lands in the Review Queue for your approval.', 'seo-audit-content-ai-assistant' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="autopilot_interval"><?php esc_html_e( 'Run Interval', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<select name="autopilot_interval" id="autopilot_interval">
							<option value="hourly" <?php selected( $s['autopilot_interval'], 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'seo-audit-content-ai-assistant' ); ?></option>
							<option value="twicedaily" <?php selected( $s['autopilot_interval'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'seo-audit-content-ai-assistant' ); ?></option>
							<option value="daily" <?php selected( $s['autopilot_interval'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'seo-audit-content-ai-assistant' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="max_posts_per_run"><?php esc_html_e( 'Max Posts per Run', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="number" min="1" max="10" id="max_posts_per_run" name="max_posts_per_run" value="<?php echo esc_attr( $s['max_posts_per_run'] ); ?>" /></td>
				</tr>
			</table>
		</div>

		<div class="theblog-panel">
			<h2><?php esc_html_e( 'Publishing Defaults', 'seo-audit-content-ai-assistant' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="default_category"><?php esc_html_e( 'Default Category', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_categories(
							array(
								'name'             => 'default_category',
								'id'               => 'default_category',
								'selected'         => $s['default_category'],
								'show_option_none' => __( 'Use WordPress default', 'seo-audit-content-ai-assistant' ),
								'hide_empty'       => false,
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th><label for="default_author"><?php esc_html_e( 'Default Author', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_users(
							array(
								'name'             => 'default_author',
								'id'               => 'default_author',
								'selected'         => $s['default_author'],
								'show_option_none' => __( 'Current user at generation time', 'seo-audit-content-ai-assistant' ),
								'who'              => 'authors',
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th><label for="internal_link_limit"><?php esc_html_e( 'Internal Links per Post', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="number" min="0" max="10" id="internal_link_limit" name="internal_link_limit" value="<?php echo esc_attr( $s['internal_link_limit'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="notify_email"><?php esc_html_e( 'Notify Email', 'seo-audit-content-ai-assistant' ); ?></label></th>
					<td><input type="email" class="regular-text" id="notify_email" name="notify_email" value="<?php echo esc_attr( $s['notify_email'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Sent when a new draft is ready for review.', 'seo-audit-content-ai-assistant' ); ?></p></td>
				</tr>
			</table>
		</div>

		<?php submit_button( __( 'Save Settings', 'seo-audit-content-ai-assistant' ) ); ?>
	</form>
</div>
