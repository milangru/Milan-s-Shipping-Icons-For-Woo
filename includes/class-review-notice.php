<?php
/**
 * Review Notice Handler
 *
 * Displays an admin notice asking for a review after a certain number of
 * WooCommerce orders have been completed while the plugin was active.
 *
 * Usage:
 * 1. This file lives in includes/class-review-notice.php.
 * 2. In the main plugin file:
 *    require_once plugin_dir_path( __FILE__ ) . 'includes/class-review-notice.php';
 *    new MSIW_Review_Notice();
 * 3. The class listens for 'woocommerce_order_status_completed' itself, so no
 *    extra wiring is needed elsewhere in the plugin.
 *
 * All user-facing strings use the 'milans-shipping-icons-for-woo' text domain
 * and are wrapped in translation functions, so the file is ready for a .pot
 * file and translation via WordPress.org's translation platform (or Loco
 * Translate / Poedit).
 *
 * @package Milans_Shipping_Icons_For_Woo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

class MSIW_Review_Notice {

	/**
	 * Text domain used for all translations in this class.
	 * Matches the Text Domain header in the main plugin file.
	 *
	 * @var string
	 */
	const TEXT_DOMAIN = 'milans-shipping-icons-for-woo';

	/**
	 * Number of completed orders after which the notice is shown.
	 *
	 * @var int
	 */
	private $threshold = 30;

	/**
	 * Number of days to snooze the notice when the user clicks "Remind me later".
	 *
	 * @var int
	 */
	private $snooze_days = 7;

	/**
	 * Plugin slug, used to build the review URL and asset URLs.
	 *
	 * @var string
	 */
	private $plugin_slug = 'milans-shipping-icons-for-woo';

	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_count_order' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_notice_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Counts a completed order towards the review threshold, once per order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_count_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_meta( '_msiw_counted_for_review' ) ) {
			return;
		}

		$count = (int) get_option( 'msiw_completed_order_count', 0 );
		update_option( 'msiw_completed_order_count', $count + 1 );

		$order->update_meta_data( '_msiw_counted_for_review', 'yes' );
		$order->save();
	}

	/**
	 * Checks whether the notice should be displayed.
	 */
	public function maybe_show_notice() {

		// Don't show if the user already responded permanently (rated or dismissed for good).
		if ( get_option( 'msiw_review_dismissed_forever' ) ) {
			return;
		}

		// Don't show while we're in a "snooze" period.
		$snoozed_until = (int) get_option( 'msiw_review_snoozed_until', 0 );
		if ( $snoozed_until && time() < $snoozed_until ) {
			return;
		}

		// Check whether the completed-order threshold has been reached.
		$completed_count = (int) get_option( 'msiw_completed_order_count', 0 );
		if ( $completed_count < $this->threshold ) {
			return;
		}

		// Only show to users who can manage plugins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->render_notice( $completed_count );
	}

	/**
	 * Whether the notice's conditions are currently met (used to decide whether
	 * to enqueue the notice's assets, so they aren't loaded on every admin page).
	 */
	private function should_show_notice() {

		if ( get_option( 'msiw_review_dismissed_forever' ) ) {
			return false;
		}

		$snoozed_until = (int) get_option( 'msiw_review_snoozed_until', 0 );
		if ( $snoozed_until && time() < $snoozed_until ) {
			return false;
		}

		$completed_count = (int) get_option( 'msiw_completed_order_count', 0 );
		if ( $completed_count < $this->threshold ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Enqueues the notice's CSS and JS, only on admin pages where it will render.
	 */
	public function enqueue_assets() {

		if ( ! $this->should_show_notice() ) {
			return;
		}

		// plugin_dir_url( __FILE__ ) returns the URL of *this* file's folder
		// (.../includes/), so the css/js subfolders can be appended directly.
		wp_enqueue_style(
			'msiw-review-notice',
			plugin_dir_url( __FILE__ ) . 'css/review-notice.css',
			array(),
			MSIW_VERSION
		);

		wp_enqueue_script(
			'msiw-review-notice',
			plugin_dir_url( __FILE__ ) . 'js/review-notice.js',
			array(),
			MSIW_VERSION,
			true
		);
	}

	/**
	 * Renders the notice HTML with action buttons.
	 *
	 * @param int $completed_count Current number of completed orders counted.
	 */
	private function render_notice( $completed_count ) {

		$review_url = sprintf(
			'https://wordpress.org/support/plugin/%s/reviews/#new-post',
			$this->plugin_slug
		);

		$base_url = ( isset( $_SERVER['REQUEST_URI'] ) ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();

		$dismiss_url = wp_nonce_url(
			add_query_arg( array( 'msiw_review_action' => 'already_rated' ), $base_url ),
			'msiw_review_notice_action',
			'msiw_nonce'
		);
		?>
		<div class="notice notice-info is-dismissible msiw-review-notice">
			<p>
				<strong>🚀 <?php esc_html_e( 'Nice work!', 'milans-shipping-icons-for-woo' ); ?></strong>
				<?php
				printf(
					/* translators: %d: number of completed orders */
					esc_html__( 'You have completed %d orders using Milan\'s Shipping Icons For Woo. If the plugin has been useful to you, leaving a review on WordPress.org would really help me keep improving it.', 'milans-shipping-icons-for-woo' ),
					intval( $completed_count )
				);
				?>
			</p>
			<p>
				<a
					href="<?php echo esc_url( $review_url ); ?>"
					target="_blank"
					rel="noopener noreferrer"
					class="button button-primary msiw-leave-review-link"
					data-dismiss-url="<?php echo esc_url( $dismiss_url ); ?>"
				>
					<?php esc_html_e( 'Sure, happy to leave a review', 'milans-shipping-icons-for-woo' ); ?> ⭐
				</a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
					<?php esc_html_e( 'I already left a review', 'milans-shipping-icons-for-woo' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msiw_review_action' => 'later' ), $base_url ), 'msiw_review_notice_action', 'msiw_nonce' ) ); ?>" class="button">
					<?php esc_html_e( 'Remind me later', 'milans-shipping-icons-for-woo' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msiw_review_action' => 'never' ), $base_url ), 'msiw_review_notice_action', 'msiw_nonce' ) ); ?>" class="button-link">
					<?php esc_html_e( 'No, thanks', 'milans-shipping-icons-for-woo' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles clicks on the notice buttons (already_rated / later / never).
	 */
	public function handle_notice_actions() {

		if ( empty( $_GET['msiw_review_action'] ) || empty( $_GET['msiw_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_GET['msiw_nonce'] ), 'msiw_review_notice_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_GET['msiw_review_action'] );

		switch ( $action ) {

			case 'already_rated':
			case 'never':
				update_option( 'msiw_review_dismissed_forever', 1 );
				break;

			case 'later':
				update_option( 'msiw_review_snoozed_until', time() + ( $this->snooze_days * DAY_IN_SECONDS ) );
				break;
		}

		// Strip query args from the URL and redirect (clean redirect, no leftover action in the URL).
		wp_safe_redirect( remove_query_arg( array( 'msiw_review_action', 'msiw_nonce' ) ) );
		exit;
	}
}
