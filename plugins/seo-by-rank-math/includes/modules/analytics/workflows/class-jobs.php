<?php
/**
 * Jobs.
 *
 * @since      1.0.54
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Analytics\Workflow;

use Exception;
use RankMath\Helper;
use RankMath\Google\Api;
use RankMath\Analytics\DB;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;
use RankMath\Analytics\Watcher;

defined( 'ABSPATH' ) || exit;

/**
 * Jobs class.
 */
class Jobs {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Jobs
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Jobs ) ) {
			$instance = new Jobs();
			$instance->hooks();
		}

		return $instance;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		$this->action( 'rank_math/analytics/flat_posts', 'do_flat_posts' );
		$this->action( 'rank_math/analytics/flat_posts_completed', 'flat_posts_completed' );
		$this->action( 'rank_math/analytics/clear_cache', 'clear_cache', 99 );
		add_action( 'rank_math/analytics/sync_sitemaps', [ Api::get(), 'sync_sitemaps' ] );

		// Daily Tasks.
		$this->action( 'rank_math/analytics/data_fetch', 'data_fetch' );

		// Data Fetcher.
		$this->action( 'rank_math/analytics/get_console_data', 'get_console_data' );
	}

	/**
	 * Perform these tasks daily.
	 */
	public function data_fetch() {
		$this->check_for_missing_dates( 'console' );
	}

	/**
	 * Perform post check.
	 */
	public function flat_posts_completed() {
		$rows = DB::objects()
			->selectCount( 'id' )
			->getVar();


		if ( ! empty( $rows ) ) {
			return;
		}

		Workflow::kill_workflows();
	}

	/**
	 * Process posts.
	 *
	 * @param  array $ids Posts ids to process.
	 */
	public function do_flat_posts( $ids ) {
		foreach ( $ids as $id ) {
			Watcher::get()->update_post_info( $id );
		}
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache() {
		global $wpdb;

		// Delete all useless data from gsc.
		$wpdb->get_results( "DELETE FROM {$wpdb->prefix}rank_math_analytics_gsc WHERE page NOT IN ( SELECT page from {$wpdb->prefix}rank_math_analytics_objects )" );

		delete_transient( 'rank_math_analytics_data_info' );
		DB::purge_cache();
		DB::delete_data_log();
		$this->calculate_stats();

		update_option( 'rank_math_analytics_last_updated', time() );
	}

	/**
	 * Get analytics data.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_console_data( $date ) {
		set_time_limit( 300 );

		$rows = Api::get()->get_search_analytics( $date, $date, [ 'query', 'page' ] );
		if ( empty( $rows ) ) {
			return;
		}

		$rows = \array_map( [ $this, 'normalize_query_page_data' ], $rows );

		try {
			DB::add_query_page_bulk( $date, $rows );
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Check for missing dates.
	 *
	 * @param string $action Action to perform.
	 */
	private function check_for_missing_dates( $action ) {
		$count = 1;
		$hook  = "get_{$action}_data";
		$start = Helper::get_midnight( time() + DAY_IN_SECONDS );

		for ( $current = 1; $current <= 90; $current++ ) {
			$date = date_i18n( 'Y-m-d', $start - ( DAY_IN_SECONDS * $current ) );
			if ( ! DB::date_exists( $date, $action ) ) {
				$count++;
				as_schedule_single_action(
					time() + ( 60 * ( $count / 2 ) ),
					'rank_math/analytics/' . $hook,
					[ $date ],
					'rank-math'
				);
			}
		}

		// Clear cache.
		if ( $count > 1 ) {
			Workflow::add_clear_cache( time() + ( 60 * ( ( $count + 1 ) / 2 ) ) );
		}
	}

	/**
	 * Calculate stats.
	 */
	private function calculate_stats() {
		$ranges = [
			'-7 days',
			'-15 days',
			'-30 days',
			'-3 months',
			'-6 months',
			'-1 year',
		];

		foreach ( $ranges as $range ) {
			Stats::get()->set_date_range( $range );
			Stats::get()->get_top_keywords();
		}
	}

	/**
	 * Normalize console data.
	 *
	 * @param array $row Single row item.
	 *
	 * @return array
	 */
	protected function normalize_query_page_data( $row ) {
		$row          = $this->normalize_data( $row );
		$row['query'] = $row['keys'][0];
		$row['page']  = $row['keys'][1];

		unset( $row['keys'] );

		return $row;
	}

	/**
	 * Normalize console data.
	 *
	 * @param array $row Single row item.
	 *
	 * @return array
	 */
	private function normalize_data( $row ) {
		$row['ctr']      = round( $row['ctr'] * 100, 2 );
		$row['position'] = round( $row['position'], 2 );

		return $row;
	}
}
