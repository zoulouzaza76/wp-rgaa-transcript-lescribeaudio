<?php
/**
 * Plugin Name:       Le Scribe Audio RGAA Transcript
 * Plugin URI:        https://lescribeaudio.com/sous-titrage-accessibilite-rgaa
 * Description:       Bloc Gutenberg et shortcode [rgaa_transcript] pour afficher une transcription textuelle accessible conforme RGAA 4.1 (critères 4.1 et 4.3) à partir d'un fichier .SRT ou .VTT, avec typographie française et recherche instantanée. 100 % local, sans dépendance externe.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Le Scribe Audio
 * Author URI:        https://lescribeaudio.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       le-scribe-audio-rgaa-transcript
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RGAA_LSA_VERSION', '1.0.0' );
define( 'RGAA_LSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RGAA_LSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Applies French non-breaking space typography while preserving URLs and timecodes (14:30).
 *
 * @param string $text Raw text line.
 * @return string Normalized UTF-8 text.
 */
function rgaa_lsa_normalize_french_typography( $text ) {
	if ( ! is_string( $text ) || '' === trim( $text ) ) {
		return '';
	}

	$protected = array();
	$working   = preg_replace_callback(
		'#https?://[^\s\)»>]+#i',
		function ( $matches ) use ( &$protected ) {
			$token               = '__RGAA_URL_' . count( $protected ) . '__';
			$protected[ $token ] = $matches[0];
			return $token;
		},
		$text
	);

	$nnbsp = "\xE2\x80\xAF"; // U+202F Narrow no-break space.
	$nbsp  = "\xC2\xA0";     // U+00A0 No-break space.

	// French guillemets.
	$working = preg_replace( '/«[\s\x{00A0}\x{202F}]*/u', '«' . $nbsp, $working );
	$working = preg_replace( '/[\s\x{00A0}\x{202F}]*»/u', $nbsp . '»', $working );

	// High punctuation ; ? !
	$working = preg_replace( '/([^\s\x{00A0}\x{202F}!?])[\s\x{00A0}\x{202F}]*([;?!])/u', '$1' . $nnbsp . '$2', $working );

	// Colon `:` except inside numeric timecodes like 14:30.
	$working = preg_replace( '/([^\s\x{00A0}\x{202F}\d])[\s\x{00A0}\x{202F}]*:(?!\/)/u', '$1' . $nbsp . ':', $working );
	$working = preg_replace( '/(\d)[\s\x{00A0}\x{202F}]+:(?!\d)/u', '$1' . $nbsp . ':', $working );

	// Collapse repeated ASCII spaces.
	$working = preg_replace( '/[ \t]{2,}/', ' ', $working );

	if ( ! empty( $protected ) ) {
		$working = strtr( $working, $protected );
	}

	return trim( $working );
}

/**
 * Formats seconds into HH:MM:SS.
 *
 * @param int $seconds Total seconds.
 * @return string Formatted timecode.
 */
function rgaa_lsa_format_hms( $seconds ) {
	$safe = max( 0, (int) $seconds );
	$h    = (int) floor( $safe / 3600 );
	$m    = (int) floor( ( $safe % 3600 ) / 60 );
	$s    = $safe % 60;
	return sprintf( '%02d:%02d:%02d', $h, $m, $s );
}

/**
 * Parses a timestamp string (HH:MM:SS,mmm or MM:SS.mmm) into integer seconds.
 *
 * @param string $tc Raw timestamp.
 * @return int Seconds.
 */
function rgaa_lsa_parse_timestamp_sec( $tc ) {
	$parts = explode( ':', str_replace( ',', '.', trim( $tc ) ) );
	$nums  = array_map( 'floatval', $parts );
	if ( 3 === count( $nums ) ) {
		return (int) floor( $nums[0] * 3600 + $nums[1] * 60 + $nums[2] );
	}
	if ( 2 === count( $nums ) ) {
		return (int) floor( $nums[0] * 60 + $nums[1] );
	}
	return 0;
}

/**
 * Parses .SRT, .VTT, or timestamped speaker text into merged speaker turns.
 *
 * @param string $raw             Raw subtitle or transcript text.
 * @param bool   $apply_typo_fr   Whether to apply French typography rules.
 * @return array<int, array{speaker: string, start_sec: int, text: string}>
 */
function rgaa_lsa_parse_transcript_turns( $raw, $apply_typo_fr = true ) {
	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return array();
	}

	$normalized  = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	$lines       = explode( "\n", $normalized );
	$turns       = array();
	$current_sec = 0;

	$tc_line_re    = '/(\d{1,2}:\d{2}:\d{2}(?:[.,]\d{1,3})?|\d{1,2}:\d{2}(?:[.,]\d{1,3})?)\s*-->\s*(?:\d{1,2}:\d{2}:\d{2}|\d{1,2}:\d{2})/';
	$bracket_tc_re = '/^\[(\d{1,2}:\d{2}(?::\d{2})?)\]\s*(.*)$/';
	$vtt_voice_re  = '/^<v\s+([^>]+)>(.*?)(?:<\/v>)?$/i';
	$colon_spk_re  = '/^([A-ZÀ-ÖØ-Ý][A-Za-zÀ-ÖØ-öø-ÿ0-9\s.\'()-]{1,48})\s*:\s+(.+)$/u';

	foreach ( $lines as $raw_line ) {
		$line = trim( $raw_line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/^(?:WEBVTT|NOTE\b|STYLE\b|REGION\b|Kind:|Language:)/i', $line ) ) {
			continue;
		}
		if ( preg_match( '/^\d+$/', $line ) || preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-/i', $line ) ) {
			continue;
		}
		if ( preg_match( $tc_line_re, $line, $m_tc ) ) {
			$current_sec = rgaa_lsa_parse_timestamp_sec( $m_tc[1] );
			continue;
		}

		$working = $line;
		if ( preg_match( $bracket_tc_re, $working, $m_br ) ) {
			$current_sec = rgaa_lsa_parse_timestamp_sec( $m_br[1] );
			$working     = trim( $m_br[2] );
		}

		$speaker = __( 'Intervenant', 'le-scribe-audio-rgaa-transcript' );
		$content = $working;

		if ( preg_match( $vtt_voice_re, $working, $m_v ) ) {
			$speaker = trim( $m_v[1] );
			$content = trim( $m_v[2] );
		} elseif ( preg_match( $colon_spk_re, $working, $m_c ) ) {
			$speaker = trim( $m_c[1] );
			$content = trim( $m_c[2] );
		}

		$content = wp_strip_all_tags( $content );
		$content = preg_replace( '/\s+/u', ' ', trim( $content ) );
		if ( '' === $content ) {
			continue;
		}

		$last_idx = count( $turns ) - 1;
		if ( $last_idx >= 0 && mb_strtolower( $turns[ $last_idx ]['speaker'] ) === mb_strtolower( $speaker ) ) {
			// Skip exact duplicate rolling caption lines.
			if ( mb_strtolower( trim( $turns[ $last_idx ]['text'] ) ) !== mb_strtolower( $content ) ) {
				$turns[ $last_idx ]['text'] = trim( $turns[ $last_idx ]['text'] . ' ' . $content );
			}
		} else {
			$turns[] = array(
				'speaker'   => $speaker,
				'start_sec' => $current_sec,
				'text'      => $content,
			);
		}
	}

	if ( $apply_typo_fr ) {
		foreach ( $turns as &$turn ) {
			$turn['text'] = rgaa_lsa_normalize_french_typography( $turn['text'] );
		}
		unset( $turn );
	}

	return $turns;
}

/**
 * Renders the RGAA 4.1 accessible transcript HTML widget.
 *
 * @param array<string, mixed> $attributes Block or shortcode attributes.
 * @param string               $content    Optional inner shortcode content.
 * @return string Safe HTML output.
 */
function rgaa_lsa_render_transcript( $attributes = array(), $content = '' ) {
	$defaults = array(
		'title'            => __( 'Transcription textuelle intégrale', 'le-scribe-audio-rgaa-transcript' ),
		'rawTranscript'    => '',
		'mediaUrl'         => '',
		'defaultOpen'      => false,
		'includeTimecodes' => true,
		'enableSearch'     => true,
		'frenchTypography' => true,
	);

	$attrs = wp_parse_args( $attributes, $defaults );

	$raw_source = is_string( $attrs['rawTranscript'] ) && '' !== trim( $attrs['rawTranscript'] )
		? $attrs['rawTranscript']
		: ( is_string( $content ) ? $content : '' );

	$turns = rgaa_lsa_parse_transcript_turns( $raw_source, (bool) $attrs['frenchTypography'] );

	if ( empty( $turns ) ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return '<div class="rgaa-lsa-empty">' . esc_html__( 'Importez un fichier .SRT / .VTT ou collez votre transcription dans les réglages du bloc.', 'le-scribe-audio-rgaa-transcript' ) . '</div>';
		}
		return '';
	}

	wp_enqueue_style( 'rgaa-lsa-frontend' );
	if ( ! empty( $attrs['enableSearch'] ) ) {
		wp_enqueue_script( 'rgaa-lsa-frontend' );
	}

	$uid        = wp_unique_id( 'rgaa-transcript-' );
	$title      = sanitize_text_field( (string) $attrs['title'] );
	$media_url  = ! empty( $attrs['mediaUrl'] ) ? esc_url( (string) $attrs['mediaUrl'] ) : '';
	$is_open    = ! empty( $attrs['defaultOpen'] );
	$show_tc    = ! empty( $attrs['includeTimecodes'] );
	$show_find  = ! empty( $attrs['enableSearch'] );
	$turn_count = count( $turns );

	ob_start();
	?>
	<section class="rgaa-lsa-transcript" aria-labelledby="<?php echo esc_attr( $uid . '-heading' ); ?>">
		<details class="rgaa-lsa-details"<?php echo $is_open ? ' open' : ''; ?>>
			<summary class="rgaa-lsa-summary" id="<?php echo esc_attr( $uid . '-heading' ); ?>">
				<span class="rgaa-lsa-badge" aria-hidden="true">RGAA 4.1</span>
				<span class="rgaa-lsa-title"><?php echo esc_html( $title ); ?></span>
				<span class="rgaa-lsa-meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of speaker turns */
							_n( '%d prise de parole', '%d prises de parole', $turn_count, 'le-scribe-audio-rgaa-transcript' ),
							$turn_count
						)
					);
					?>
				</span>
			</summary>

			<div class="rgaa-lsa-body">
				<?php if ( $media_url ) : ?>
					<p class="rgaa-lsa-media-link">
						<a href="<?php echo esc_url( $media_url ); ?>" rel="noopener">
							<?php esc_html_e( 'Accéder à la vidéo ou à l’enregistrement source', 'le-scribe-audio-rgaa-transcript' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<?php if ( $show_find ) : ?>
					<div class="rgaa-lsa-search-row">
						<label class="rgaa-lsa-search-label" for="<?php echo esc_attr( $uid . '-search' ); ?>">
							<?php esc_html_e( 'Rechercher dans la transcription', 'le-scribe-audio-rgaa-transcript' ); ?>
						</label>
						<input
							id="<?php echo esc_attr( $uid . '-search' ); ?>"
							type="search"
							class="rgaa-lsa-search-input"
							data-rgaa-lsa-filter="<?php echo esc_attr( $uid . '-list' ); ?>"
							data-rgaa-lsa-status="<?php echo esc_attr( $uid . '-status' ); ?>"
							placeholder="<?php esc_attr_e( 'Mot-clé, intervenant, délibération…', 'le-scribe-audio-rgaa-transcript' ); ?>"
						/>
						<span id="<?php echo esc_attr( $uid . '-status' ); ?>" class="rgaa-lsa-status" role="status" aria-live="polite"></span>
					</div>
				<?php endif; ?>

				<ol id="<?php echo esc_attr( $uid . '-list' ); ?>" class="rgaa-lsa-list">
					<?php foreach ( $turns as $turn ) : ?>
						<li class="rgaa-lsa-turn">
							<div class="rgaa-lsa-turn-head">
								<?php if ( $show_tc ) : ?>
									<time class="rgaa-lsa-timecode" datetime="<?php echo esc_attr( 'PT' . (int) $turn['start_sec'] . 'S' ); ?>">
										<?php echo esc_html( rgaa_lsa_format_hms( $turn['start_sec'] ) ); ?>
									</time>
								<?php endif; ?>
								<strong class="rgaa-lsa-speaker"><?php echo esc_html( $turn['speaker'] ); ?></strong>
							</div>
							<p class="rgaa-lsa-text"><?php echo esc_html( $turn['text'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</details>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Registers frontend/editor assets and the Gutenberg block.
 */
function rgaa_lsa_init() {
	wp_register_style(
		'rgaa-lsa-frontend',
		RGAA_LSA_PLUGIN_URL . 'assets/style.css',
		array(),
		RGAA_LSA_VERSION
	);

	wp_register_style(
		'rgaa-lsa-editor',
		RGAA_LSA_PLUGIN_URL . 'assets/editor.css',
		array( 'wp-edit-blocks' ),
		RGAA_LSA_VERSION
	);

	wp_register_script(
		'rgaa-lsa-frontend',
		RGAA_LSA_PLUGIN_URL . 'assets/frontend.js',
		array(),
		RGAA_LSA_VERSION,
		true
	);

	wp_register_script(
		'rgaa-lsa-editor',
		RGAA_LSA_PLUGIN_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		RGAA_LSA_VERSION,
		true
	);

	register_block_type(
		RGAA_LSA_PLUGIN_DIR,
		array(
			'render_callback' => 'rgaa_lsa_render_transcript',
		)
	);
}
add_action( 'init', 'rgaa_lsa_init' );

/**
 * Shortcode [rgaa_transcript title="..." media_url="..." open="false" timecodes="true" search="true"]...SRT/VTT...[/rgaa_transcript]
 *
 * @param array<string, mixed> $atts    Shortcode attributes.
 * @param string|null          $content Enclosed SRT/VTT/text.
 * @return string Rendered HTML.
 */
function rgaa_lsa_shortcode( $atts, $content = null ) {
	$parsed = shortcode_atts(
		array(
			'title'     => __( 'Transcription textuelle intégrale', 'le-scribe-audio-rgaa-transcript' ),
			'media_url' => '',
			'open'      => 'false',
			'timecodes' => 'true',
			'search'    => 'true',
			'typo_fr'   => 'true',
		),
		$atts,
		'rgaa_transcript'
	);

	$to_bool = function ( $val ) {
		return in_array( strtolower( trim( (string) $val ) ), array( '1', 'true', 'yes', 'oui', 'on' ), true );
	};

	return rgaa_lsa_render_transcript(
		array(
			'title'            => $parsed['title'],
			'rawTranscript'    => (string) $content,
			'mediaUrl'         => $parsed['media_url'],
			'defaultOpen'      => $to_bool( $parsed['open'] ),
			'includeTimecodes' => $to_bool( $parsed['timecodes'] ),
			'enableSearch'     => $to_bool( $parsed['search'] ),
			'frenchTypography' => $to_bool( $parsed['typo_fr'] ),
		)
	);
}
add_shortcode( 'rgaa_transcript', 'rgaa_lsa_shortcode' );
