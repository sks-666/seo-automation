<?php

namespace SEOAgent\Blog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the configured AI provider(s). Text generation always uses the
 * provider chosen in Settings; image generation may use a different
 * provider since not every text provider can also generate images.
 */
class AI_Client {

	protected static $providers = array();

	/**
	 * Provider keys that talk to a vendor API directly. Used only as the
	 * fallback for sites without the core AI Client.
	 */
	const DIRECT_PROVIDERS = array( 'anthropic', 'openai', 'deepseek' );

	protected static function make( $key ) {
		switch ( $key ) {
			case 'wp_ai_client':
				return new Provider_WP_AI_Client();
			case 'anthropic':
				return new Provider_Anthropic();
			case 'openai':
				return new Provider_OpenAI();
			case 'deepseek':
				return new Provider_DeepSeek();
			default:
				return null;
		}
	}

	protected static function get_cached( $key ) {
		if ( ! isset( self::$providers[ $key ] ) ) {
			self::$providers[ $key ] = self::make( $key );
		}
		return self::$providers[ $key ];
	}

	/**
	 * The provider a fresh install should start on.
	 *
	 * WordPress 7.0+ sites default to the core AI Client, so the site owner
	 * configures a provider once in WordPress itself and this plugin never
	 * handles a vendor API key. Older sites fall back to a direct provider.
	 *
	 * @return string
	 */
	public static function default_provider_key() {
		return Provider_WP_AI_Client::is_available() ? 'wp_ai_client' : 'anthropic';
	}

	/**
	 * First direct provider that actually has a key stored, for use when the
	 * core AI Client is selected but unavailable (a site that has since been
	 * rolled back below WordPress 7.0).
	 *
	 * @return string
	 */
	protected static function first_configured_direct_provider() {
		foreach ( self::DIRECT_PROVIDERS as $key ) {
			$provider = self::get_cached( $key );

			if ( $provider && $provider->is_configured() ) {
				return $key;
			}
		}

		return 'anthropic';
	}

	/**
	 * Provider used for research/writing/SEO text tasks.
	 */
	public static function text_provider() {
		$selected = Settings::get( 'ai_provider', self::default_provider_key() );

		if ( 'wp_ai_client' === $selected && ! Provider_WP_AI_Client::is_available() ) {
			$selected = self::first_configured_direct_provider();
		}

		return self::get_cached( $selected );
	}

	/**
	 * Provider used for featured image generation, if any.
	 */
	public static function image_provider() {
		$configured = Settings::get( 'image_provider', 'none' );
		if ( 'none' === $configured ) {
			return null;
		}

		if ( 'wp_ai_client' === $configured && ! Provider_WP_AI_Client::is_available() ) {
			return null;
		}

		$provider = self::get_cached( $configured );
		return ( $provider && $provider->supports_images() ) ? $provider : null;
	}

	public static function generate_text( $system_prompt, $user_prompt, $args = array() ) {
		$provider = self::text_provider();
		if ( ! $provider ) {
			return new \WP_Error( 'theblog_no_provider', __( 'No AI provider is configured.', 'seo-audit-content-ai-assistant' ) );
		}
		return $provider->generate_text( $system_prompt, $user_prompt, $args );
	}

	/**
	 * Ask the model for JSON and decode it, tolerating markdown code fences,
	 * a preamble/trailer around the JSON, and (retried once) a response
	 * that got cut off because max_tokens was too low for the request.
	 *
	 * @return array|WP_Error
	 */
	public static function generate_json( $system_prompt, $user_prompt, $args = array() ) {
		$raw = self::generate_text( $system_prompt, $user_prompt, $args );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$decoded = self::extract_json( $raw );

		if ( null === $decoded ) {
			// The most common real-world cause is the response getting cut
			// off mid-JSON because max_tokens was too tight for what was
			// asked for. Retry once with a much higher ceiling before
			// giving up — cheaper than failing the whole pipeline stage.
			$retry_args = $args;
			$retry_args['max_tokens'] = max( (int) ( $args['max_tokens'] ?? 4000 ) * 2, 6000 );

			$retry_raw = self::generate_text( $system_prompt, $user_prompt, $retry_args );
			if ( ! is_wp_error( $retry_raw ) ) {
				$decoded = self::extract_json( $retry_raw );
				if ( null !== $decoded ) {
					return $decoded;
				}
				$raw = $retry_raw; // log whichever attempt we have for diagnosis
			}
		}

		if ( null === $decoded ) {
			Logger::error( 'AI response could not be parsed as JSON. Raw response (truncated): ' . substr( $raw, 0, 1500 ) );
			return new \WP_Error( 'theblog_bad_json', __( 'AI response could not be parsed as JSON. See Logs for the raw response.', 'seo-audit-content-ai-assistant' ), $raw );
		}

		return $decoded;
	}

	/**
	 * Strip markdown fences / any surrounding prose and decode the first
	 * balanced {...} object found — more forgiving than a naive greedy
	 * regex, which breaks if the model adds text containing braces before
	 * or after the actual JSON object.
	 *
	 * @return array|null
	 */
	protected static function extract_json( $text ) {
		$cleaned = trim( (string) $text );
		$cleaned = preg_replace( '/^```(json)?/i', '', $cleaned );
		$cleaned = preg_replace( '/```$/', '', trim( $cleaned ) );
		$cleaned = trim( $cleaned );

		$decoded = json_decode( $cleaned, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$start = strpos( $cleaned, '{' );
		if ( false === $start ) {
			return null;
		}

		$depth      = 0;
		$in_string  = false;
		$escaped    = false;
		$end        = null;

		for ( $i = $start, $len = strlen( $cleaned ); $i < $len; $i++ ) {
			$char = $cleaned[ $i ];

			if ( $in_string ) {
				if ( $escaped ) {
					$escaped = false;
				} elseif ( '\\' === $char ) {
					$escaped = true;
				} elseif ( '"' === $char ) {
					$in_string = false;
				}
				continue;
			}

			if ( '"' === $char ) {
				$in_string = true;
			} elseif ( '{' === $char ) {
				$depth++;
			} elseif ( '}' === $char ) {
				$depth--;
				if ( 0 === $depth ) {
					$end = $i;
					break;
				}
			}
		}

		if ( null === $end ) {
			return null;
		}

		$candidate = substr( $cleaned, $start, $end - $start + 1 );
		$decoded   = json_decode( $candidate, true );

		return is_array( $decoded ) ? $decoded : null;
	}
}
