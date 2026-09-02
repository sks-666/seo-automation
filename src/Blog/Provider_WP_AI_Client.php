<?php

namespace SEOAgent\Blog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Text and image generation through the AI Client that ships in WordPress
 * 7.0 core.
 *
 * This is the preferred path: the site owner picks and configures a provider
 * once, under Settings -> Connectors, and core owns the credentials. The
 * plugin never sees an API key and never names a vendor. The direct
 * Anthropic / OpenAI / DeepSeek providers remain only as the fallback for
 * sites below WordPress 7.0, where wp_ai_client_prompt() does not exist.
 *
 * Every core call is guarded by function_exists() and by the builder's own
 * is_supported_for_*() checks, so this class is inert — never fatal — on an
 * older WordPress.
 */
class Provider_WP_AI_Client implements AI_Provider_Interface {

	/**
	 * Whether this WordPress ships the core AI Client at all.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * A throwaway builder used purely for capability probing. The core docs
	 * are explicit that is_supported_for_*() makes no API calls.
	 *
	 * @param string $prompt Prompt text.
	 * @return object|null
	 */
	protected function builder( $prompt ) {
		if ( ! self::is_available() ) {
			return null;
		}

		$builder = wp_ai_client_prompt( $prompt );

		return is_object( $builder ) ? $builder : null;
	}

	public function get_name() {
		return __( 'WordPress AI (core)', 'nexcove-seo-audit-content-assistant' );
	}

	/**
	 * "Configured" here means core has a connected provider that can do text,
	 * which is the equivalent of an API key being present for the direct
	 * providers.
	 */
	public function is_configured() {
		$builder = $this->builder( 'ping' );

		return $builder && $builder->is_supported_for_text_generation();
	}

	public function supports_images() {
		$builder = $this->builder( 'ping' );

		return $builder && $builder->is_supported_for_image_generation();
	}

	public function generate_text( $system_prompt, $user_prompt, $args = array() ) {
		$builder = $this->builder( $user_prompt );

		if ( ! $builder ) {
			return new \WP_Error(
				'theblog_not_configured',
				__( 'This site does not have the WordPress AI Client (WordPress 6.9 or earlier). Choose a direct AI provider instead.', 'nexcove-seo-audit-content-assistant' )
			);
		}

		$args = wp_parse_args( $args, array( 'max_tokens' => 4000 ) );

		$builder = $builder->using_max_tokens( (int) $args['max_tokens'] );

		if ( '' !== trim( (string) $system_prompt ) ) {
			$builder = $builder->using_system_instruction( (string) $system_prompt );
		}

		// Matches the direct providers: only send a temperature when the
		// caller asked for one, since some model IDs reject it outright.
		if ( isset( $args['temperature'] ) ) {
			$builder = $builder->using_temperature( (float) $args['temperature'] );
		}

		if ( ! $builder->is_supported_for_text_generation() ) {
			return new \WP_Error(
				'theblog_not_configured',
				__( 'No AI provider is connected in WordPress. Connect one under Settings -> Connectors, then try again.', 'nexcove-seo-audit-content-assistant' )
			);
		}

		$text = $builder->generate_text();

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$text = (string) $text;

		if ( '' === trim( $text ) ) {
			return new \WP_Error( 'theblog_empty_response', __( 'The AI provider returned an empty response.', 'nexcove-seo-audit-content-assistant' ) );
		}

		return $text;
	}

	/**
	 * Generate a featured image.
	 *
	 * Core returns a File object, which may be a remote URL or inline data.
	 * The remote case is returned as-is; the inline case is written to a
	 * temporary file and its path returned, which Featured_Image sideloads.
	 *
	 * @param string $prompt Image prompt.
	 * @param array  $args   Unused; kept for interface parity.
	 * @return string|\WP_Error Image URL, or an absolute local file path.
	 */
	public function generate_image( $prompt, $args = array() ) {
		$builder = $this->builder( $prompt );

		if ( ! $builder ) {
			return new \WP_Error(
				'theblog_not_configured',
				__( 'This site does not have the WordPress AI Client (WordPress 6.9 or earlier). Choose a direct image provider instead.', 'nexcove-seo-audit-content-assistant' )
			);
		}

		if ( ! $builder->is_supported_for_image_generation() ) {
			return new \WP_Error(
				'theblog_unsupported',
				__( 'The AI provider connected in WordPress cannot generate images.', 'nexcove-seo-audit-content-assistant' )
			);
		}

		$file = $builder->generate_image();

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( ! is_object( $file ) ) {
			return new \WP_Error( 'theblog_empty_response', __( 'The AI provider returned no image.', 'nexcove-seo-audit-content-assistant' ) );
		}

		if ( method_exists( $file, 'isRemote' ) && $file->isRemote() ) {
			$url = (string) $file->getUrl();

			if ( '' !== $url ) {
				return $url;
			}
		}

		return $this->write_inline_image( $file );
	}

	/**
	 * Persist an inline (base64) image to a temporary file.
	 *
	 * The file is named with a real image extension because
	 * media_handle_sideload() validates the extension against the contents.
	 *
	 * @param object $file Core AI Client File object.
	 * @return string|\WP_Error Absolute path to the temporary file.
	 */
	protected function write_inline_image( $file ) {
		$base64 = method_exists( $file, 'getBase64Data' ) ? $file->getBase64Data() : null;

		if ( empty( $base64 ) ) {
			return new \WP_Error( 'theblog_empty_response', __( 'The AI provider returned no usable image data.', 'nexcove-seo-audit-content-assistant' ) );
		}

		$binary = base64_decode( $base64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding the image bytes the AI Client handed back inline, not obfuscated code.

		if ( false === $binary || '' === $binary ) {
			return new \WP_Error( 'theblog_empty_response', __( 'The AI provider returned an image that could not be decoded.', 'nexcove-seo-audit-content-assistant' ) );
		}

		$mime = method_exists( $file, 'getMimeType' ) ? (string) $file->getMimeType() : 'image/png';

		$extensions = array(
			'image/png'  => 'png',
			'image/jpeg' => 'jpg',
			'image/webp' => 'webp',
			'image/gif'  => 'gif',
		);

		if ( ! isset( $extensions[ $mime ] ) ) {
			return new \WP_Error(
				'theblog_unsupported',
				sprintf(
					/* translators: %s: MIME type returned by the AI provider. */
					__( 'The AI provider returned an unsupported image type (%s).', 'nexcove-seo-audit-content-assistant' ),
					$mime
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			return new \WP_Error( 'theblog_filesystem', __( 'The generated image could not be saved: WordPress has no direct filesystem access.', 'nexcove-seo-audit-content-assistant' ) );
		}

		$path = trailingslashit( get_temp_dir() ) . 'seo-generated-image-' . wp_generate_password( 12, false ) . '.' . $extensions[ $mime ];

		if ( ! $wp_filesystem->put_contents( $path, $binary, FS_CHMOD_FILE ) ) {
			return new \WP_Error( 'theblog_filesystem', __( 'The generated image could not be written to the temporary directory.', 'nexcove-seo-audit-content-assistant' ) );
		}

		return $path;
	}
}
