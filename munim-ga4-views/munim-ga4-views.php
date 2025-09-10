<?php
/**
 * Plugin Name: Munim GA4 Views (Non‑Blocking)
 * Description: Outputs GA4 page views without blocking render. Uses cached values, hydrates via REST, and refreshes in background.
 * Version: 1.0.0
 * Author: Munim
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Optional: Composer autoload if present next to wp-content or theme.
$__munim_ga4_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $__munim_ga4_autoload ) ) {
	require_once $__munim_ga4_autoload;
}

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;

// -----------------------------
// Configuration via constants/filters
// -----------------------------

if ( ! defined( 'MUNIM_GA4_CACHE_TTL' ) ) {
	define( 'MUNIM_GA4_CACHE_TTL', 6 * HOUR_IN_SECONDS );
}

/**
 * Filters allow overriding property ID and credentials path.
 */
function munim_ga4_get_property_id() {
	$property_id = apply_filters( 'munim_ga4/property_id', '' );
	return is_string( $property_id ) ? $property_id : '';
}

function munim_ga4_get_credentials_path() {
	$default_path = get_template_directory() . '/the-munim-website-d791f347c090.json';
	$path = apply_filters( 'munim_ga4/credentials_path', $default_path );
	return is_string( $path ) ? $path : $default_path;
}

// -----------------------------
// Storage helpers
// -----------------------------

function munim_ga4_transient_key( $post_id ) {
	return 'ga4_views_' . absint( $post_id );
}

function munim_ga4_get_cached_views( $post_id ) {
	$post_id = absint( $post_id );
	$cached = get_transient( munim_ga4_transient_key( $post_id ) );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$meta = get_post_meta( $post_id, '_ga4_views_total', true );
	return (int) $meta;
}

function munim_ga4_last_updated( $post_id ) {
	$ts = (int) get_post_meta( $post_id, '_ga4_views_cached_at', true );
	return $ts > 0 ? $ts : 0;
}

function munim_ga4_set_cached_views( $post_id, $views ) {
	$post_id = absint( $post_id );
	$views = max( 0, (int) $views );
	set_transient( munim_ga4_transient_key( $post_id ), $views, MUNIM_GA4_CACHE_TTL );
	update_post_meta( $post_id, '_ga4_views_total', $views );
	update_post_meta( $post_id, '_ga4_views_cached_at', time() );
}

// -----------------------------
// Shortcode: [ga4_views]
// -----------------------------

function munim_ga4_shortcode( $atts ) {
	if ( is_admin() ) {
		return '';
	}

	$atts = shortcode_atts( array(
		'post_id' => get_the_ID(),
	), $atts, 'ga4_views' );

	$post_id = absint( $atts['post_id'] );
	if ( $post_id <= 0 ) {
		return '';
	}

	$initial = munim_ga4_get_cached_views( $post_id );
	$last_ts = munim_ga4_last_updated( $post_id );
	$is_stale = ( time() - $last_ts ) > MUNIM_GA4_CACHE_TTL;

	$span = sprintf(
		'<span class="munim-ga4-views" data-post-id="%1$d" data-stale="%2$s" aria-live="polite" aria-busy="true">%3$s</span>',
		$post_id,
		$is_stale ? '1' : '0',
		number_format_i18n( (int) $initial )
	);

	return $span;
}
add_shortcode( 'ga4_views', 'munim_ga4_shortcode' );

// -----------------------------
// Frontend script (tiny, deferred)
// -----------------------------

function munim_ga4_enqueue_script() {
	if ( is_admin() ) {
		return;
	}

	$handle = 'munim-ga4-views';
	$src = plugins_url( 'assets/ga4-views.js', __FILE__ );
	wp_register_script( $handle, $src, array(), '1.0.0', array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_script( $handle );
	$endpoint = esc_url_raw( rest_url( '/munim/v1/ga4-views' ) );
	$data = array( 'endpoint' => $endpoint );
	wp_add_inline_script( $handle, 'window.MUNIM_GA4=' . wp_json_encode( $data ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'munim_ga4_enqueue_script' );

// -----------------------------
// REST API endpoint: returns cached views and schedules background refresh if stale
// -----------------------------

function munim_ga4_register_rest() {
	register_rest_route( 'munim/v1', '/ga4-views', array(
		'methods' => 'GET',
		'callback' => 'munim_ga4_rest_get_views',
		'permission_callback' => '__return_true',
		'args' => array(
			'post_id' => array(
				'required' => true,
				'validate_callback' => function ( $param ) {
					return is_numeric( $param ) && (int) $param > 0;
				},
			),
		),
	) );
}
add_action( 'rest_api_init', 'munim_ga4_register_rest' );

function munim_ga4_refresh_lock_key( $post_id ) {
	return 'ga4_refresh_lock_' . absint( $post_id );
}

function munim_ga4_rest_get_views( WP_REST_Request $request ) {
	$post_id = absint( $request->get_param( 'post_id' ) );
	if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
		return new WP_REST_Response( array( 'views' => 0 ), 200 );
	}

	$views = munim_ga4_get_cached_views( $post_id );
	$last_ts = munim_ga4_last_updated( $post_id );
	$is_stale = ( time() - $last_ts ) > MUNIM_GA4_CACHE_TTL;

	if ( $is_stale ) {
		$lock_key = munim_ga4_refresh_lock_key( $post_id );
		if ( false === get_transient( $lock_key ) ) {
			set_transient( $lock_key, 1, 10 * MINUTE_IN_SECONDS );
			wp_schedule_single_event( time() + 5, 'munim_ga4_refresh_views', array( $post_id ) );
		}
	}

	return new WP_REST_Response( array(
		'views' => (int) $views,
		'cached_at' => $last_ts,
		'is_stale' => (bool) $is_stale,
	), 200 );
}

// -----------------------------
// Cron worker: refresh GA4 views in background
// -----------------------------

add_action( 'munim_ga4_refresh_views', 'munim_ga4_refresh_views_worker' );

function munim_ga4_refresh_views_worker( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return;
	}

	$property_id = munim_ga4_get_property_id();
	$credentials = munim_ga4_get_credentials_path();
	if ( '' === $property_id || ! file_exists( $credentials ) ) {
		delete_transient( munim_ga4_refresh_lock_key( $post_id ) );
		return;
	}

	$permalink = get_permalink( $post_id );
	if ( ! $permalink ) {
		delete_transient( munim_ga4_refresh_lock_key( $post_id ) );
		return;
	}

	$path = wp_parse_url( $permalink, PHP_URL_PATH );
	$path = is_string( $path ) ? rtrim( $path, '/' ) : '';
	if ( '' === $path ) {
		$path = '/';
	}

	$publish_date = get_the_date( 'Y-m-d', $post_id );
	if ( ! $publish_date ) {
		$publish_date = '2020-01-01';
	}

	$total_views = 0;

	try {
		if ( class_exists( BetaAnalyticsDataClient::class ) ) {
			$client = new BetaAnalyticsDataClient( array(
				'credentials' => $credentials,
				'transport' => 'rest',
			) );

			$request = new RunReportRequest( array(
				'property' => 'properties/' . $property_id,
				'date_ranges' => array(
					new DateRange( array(
						'start_date' => $publish_date,
						'end_date' => 'today',
					) ),
				),
				'dimensions' => array(
					new Dimension( array( 'name' => 'pagePath' ) ),
				),
				'metrics' => array(
					new Metric( array( 'name' => 'screenPageViews' ) ),
				),
				'dimension_filter' => new FilterExpression( array(
					'filter' => new Filter( array(
						'field_name' => 'pagePath',
						'string_filter' => new StringFilter( array(
							'match_type' => MatchType::BEGINS_WITH,
							'value' => $path,
						) ),
					) ),
				) ),
			) );

			$response = $client->runReport( $request );
			foreach ( $response->getRows() as $row ) {
				$views = (int) $row->getMetricValues()[0]->getValue();
				$total_views += $views;
			}
		}
	} catch ( Exception $e ) {
		// Keep stale value on error; just release lock.
	}

	if ( $total_views > 0 ) {
		munim_ga4_set_cached_views( $post_id, $total_views );
	}

	delete_transient( munim_ga4_refresh_lock_key( $post_id ) );
}

