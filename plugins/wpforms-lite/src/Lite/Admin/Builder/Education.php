<?php

namespace WPForms\Lite\Admin\Builder;

/**
 * Form Builder changes and enhancements to educate Lite users on what is available in WPForms Pro.
 *
 * @since 1.5.1
 */
class Education {

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.5.1
	 */
	public function hooks() {

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_wpforms_dyk_dismiss', [ $this, 'dyk_ajax_dismiss' ] );

			add_action( 'wp_ajax_wpforms_update_field_captcha', [ $this, 'captcha_field_callback' ] );

			add_action( 'wpforms_field_options_bottom_advanced-options', [ $this, 'geolocation_options' ], 10, 2 );

			add_action( 'wpforms_field_options_after_advanced-options', [ $this, 'field_conditional_logic' ], 10, 2 );
		}

		// Only proceed for the form builder.
		if ( ! wpforms_is_admin_page( 'builder' ) ) {
			return;
		}

		add_action( 'wpforms_field_options_bottom_advanced-options', [ $this, 'geolocation_options' ], 10, 2 );

		add_action( 'wpforms_field_options_after_advanced-options', [ $this, 'field_conditional_logic' ], 10, 2 );

		add_filter( 'wpforms_lite_builder_strings', [ $this, 'js_strings' ] );

		add_action( 'wpforms_builder_enqueues_before', [ $this, 'enqueues' ] );

		add_action( 'wpforms_setup_panel_after', [ $this, 'templates' ] );

		add_filter( 'wpforms_builder_fields_buttons', [ $this, 'fields' ], 50 );

		add_action( 'wpforms_builder_after_panel_sidebar', [ $this, 'settings' ], 100, 2 );

		add_action( 'wpforms_providers_panel_sidebar', [ $this, 'providers' ], 50 );

		add_action( 'wpforms_payments_panel_sidebar', [ $this, 'payments' ], 50 );

		add_action( 'wpforms_builder_settings_notifications_after', [ $this, 'dyk_notifications' ] );

		add_action( 'wpforms_builder_settings_confirmations_after', [ $this, 'dyk_confirmations' ] );
	}

	/**
	 * Localize needed strings.
	 *
	 * @since 1.5.1
	 *
	 * @param array $strings JS strings.
	 *
	 * @return array
	 */
	public function js_strings( $strings ) {

		$strings['upgrade'] = [
			'pro'   => [
				'title'   => esc_html__( 'is a PRO Feature', 'wpforms-lite' ),
				'message' => '<p>' . esc_html__( 'We\'re sorry, the %name% is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'wpforms-lite' ) . '</p>',
				'bonus'   => '<p>' .
					wp_kses(
						__( '<strong>Bonus:</strong> WPForms Lite users get <span>50% off</span> regular price, automatically applied at checkout.', 'wpforms-lite' ),
						[
							'strong' => [],
							'span'   => [],
						]
					) .
				'</p>',
				'doc'     => '<a href="https://wpforms.com/docs/upgrade-wpforms-lite-paid-license/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin&amp;utm_content=upgrade-pro" target="_blank" rel="noopener noreferrer" class="already-purchased">' . esc_html__( 'Already purchased?', 'wpforms-lite' ) . '</a>',
				'button'  => esc_html__( 'Upgrade to PRO', 'wpforms-lite' ),
				'url'     => wpforms_admin_upgrade_link( 'builder-modal', 'upgrade-pro' ),
				'modal'   => wpforms_get_upgrade_modal_text( 'pro' ),
			],
			'elite' => [
				'title'   => esc_html__( 'is an Elite Feature', 'wpforms-lite' ),
				'message' => '<p>' . esc_html__( 'We\'re sorry, the %name% is not available on your plan. Please upgrade to the Elite plan to unlock all these awesome features.', 'wpforms-lite' ) . '</p>',
				'bonus'   => '<p>' .
					wp_kses(
						__( '<strong>Bonus:</strong> WPForms Lite users get <span>50% off</span> regular price, automatically applied at checkout.', 'wpforms-lite' ),
						[
							'strong' => [],
							'span'   => [],
						]
					) .
				'</p>',
				'doc'     => '<a href="https://wpforms.com/docs/upgrade-wpforms-lite-paid-license/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin&amp;utm_content=upgrade-elite" target="_blank" rel="noopener noreferrer" class="already-purchased">' . esc_html__( 'Already purchased?', 'wpforms-lite' ) . '</a>',
				'button'  => esc_html__( 'Upgrade to Elite', 'wpforms-lite' ),
				'url'     => wpforms_admin_upgrade_link( 'builder-modal', 'upgrade-elite' ),
				'modal'   => wpforms_get_upgrade_modal_text( 'elite' ),
			],
		];

		return $strings;
	}

	/**
	 * Load enqueues.
	 *
	 * @since 1.5.1
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-builder-education',
			WPFORMS_PLUGIN_URL . "lite/assets/js/admin/builder-education{$min}.js",
			array( 'jquery', 'jquery-confirm' ),
			WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Display templates.
	 *
	 * @since 1.5.1
	 */
	public function templates() {

		$templates = array(
			array(
				'name'        => esc_html__( 'Request A Quote Form', 'wpforms-lite' ),
				'slug'        => 'request-quote',
				'description' => esc_html__( 'Start collecting leads with this pre-made Request a quote form. You can add and remove fields as needed.', 'wpforms-lite' ),
			),
			array(
				'name'        => esc_html__( 'Donation Form', 'wpforms-lite' ),
				'slug'        => 'donation',
				'description' => esc_html__( 'Start collecting donation payments on your website with this ready-made Donation form. You can add and remove fields as needed.', 'wpforms-lite' ),
			),
			array(
				'name'        => esc_html__( 'Billing / Order Form', 'wpforms-lite' ),
				'slug'        => 'order',
				'description' => esc_html__( 'Collect payments for product and service orders with this ready-made form template. You can add and remove fields as needed.', 'wpforms-lite' ),
			),
		);
		?>

		<div class="wpforms-setup-title">
			<?php esc_html_e( 'Unlock Pre-Made Form Templates', 'wpforms-lite' ); ?>
			<a href="<?php echo esc_url( wpforms_admin_upgrade_link( 'builder-templates' ) ); ?>" target="_blank" rel="noopener noreferrer"
				class="btn-green wpforms-upgrade-link wpforms-upgrade-modal"
				style="text-transform: uppercase;font-size: 13px;font-weight: 700;padding: 5px 10px;vertical-align: text-bottom;">
				<?php esc_html_e( 'Upgrade', 'wpforms-lite' ); ?>
			</a>
		</div>
		<p class="wpforms-setup-desc">
			<?php esc_html_e( 'While WPForms Lite allows you to create any type of form, you can speed up the process by unlocking our other pre-built form templates among other features, so you never have to start from scratch again...', 'wpforms-lite' ); ?>
		</p>
		<div class="wpforms-setup-templates wpforms-clear" style="opacity:0.5;">
			<?php
			$x = 0;
			foreach ( $templates as $template ) {
				$class = 0 === $x % 3 ? 'first ' : '';
				?>
				<div class="wpforms-template upgrade-modal <?php echo sanitize_html_class( $class ); ?>"
					id="wpforms-template-<?php echo sanitize_html_class( $template['slug'] ); ?>">
					<div class="wpforms-template-name wpforms-clear">
						<?php echo esc_html( $template['name'] ); ?>
					</div>
					<div class="wpforms-template-details">
						<p class="desc"><?php echo esc_html( $template['description'] ); ?></p>
					</div>
				</div>
				<?php
				$x ++;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Display fields.
	 *
	 * @since 1.5.1
	 * @since 1.6.4 Added hCaptcha support.
	 *
	 * @param array $fields Form fields.
	 *
	 * @return array
	 */
	public function fields( $fields ) {

		// Add CAPTCHA field to Standard group.
		$captcha_settings = wpforms_get_captcha_settings();
		if ( ! empty( $captcha_settings['provider'] ) ) {
			if ( ! empty( $captcha_settings['site_key'] ) || ! empty( $captcha_settings['secret_key'] ) ) {
				$captcha_name = 'hcaptcha' === $captcha_settings['provider'] ? esc_html__( 'hCaptcha', 'wpforms-lite' ) : esc_html__( 'reCAPTCHA', 'wpforms-lite' );
				$captcha_icon = 'hcaptcha' === $captcha_settings['provider'] ? 'fa-question-circle-o' : 'fa-google';
			} else {
				$captcha_name = esc_html__( 'CAPTCHA', 'wpforms-lite' );
				$captcha_icon = 'fa-question-circle-o';
			}

			$fields['standard']['fields'][] = array(
				'icon'  => $captcha_icon,
				'name'  => $captcha_name,
				'type'  => 'captcha_' . $captcha_settings['provider'],
				'order' => 180,
				'class' => 'not-draggable',
			);
		}

		$fields['fancy']['fields'] = array(
			array(
				'icon'  => 'fa-phone',
				'name'  => esc_html__( 'Phone', 'wpforms-lite' ),
				'type'  => 'phone',
				'order' => '1',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-map-marker',
				'name'  => esc_html__( 'Address', 'wpforms-lite' ),
				'type'  => 'address',
				'order' => '2',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-calendar-o',
				'name'  => esc_html__( 'Date / Time', 'wpforms-lite' ),
				'type'  => 'date-time',
				'order' => '3',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-link',
				'name'  => esc_html__( 'Website / URL', 'wpforms-lite' ),
				'type'  => 'url',
				'order' => '4',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-upload',
				'name'  => esc_html__( 'File Upload', 'wpforms-lite' ),
				'type'  => 'file-upload',
				'order' => '5',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-lock',
				'name'  => esc_html__( 'Password', 'wpforms-lite' ),
				'type'  => 'password',
				'order' => '6',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-files-o',
				'name'  => esc_html__( 'Page Break', 'wpforms-lite' ),
				'type'  => 'pagebreak',
				'order' => '7',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-arrows-h',
				'name'  => esc_html__( 'Section Divider', 'wpforms-lite' ),
				'type'  => 'divider',
				'order' => '8',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-eye-slash',
				'name'  => esc_html__( 'Hidden Field', 'wpforms-lite' ),
				'type'  => 'hidden',
				'order' => '9',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-code',
				'name'  => esc_html__( 'HTML', 'wpforms-lite' ),
				'type'  => 'html',
				'order' => '10',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-star',
				'name'  => esc_html__( 'Rating', 'wpforms-lite' ),
				'type'  => 'rating',
				'order' => '11',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-question-circle',
				'name'  => esc_html__( 'Custom Captcha', 'wpforms-lite' ),
				'type'  => 'captcha',
				'order' => '12',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-pencil',
				'name'  => esc_html__( 'Signature', 'wpforms-lite' ),
				'type'  => 'signature',
				'order' => '13',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-ellipsis-h',
				'name'  => esc_html__( 'Likert Scale', 'wpforms-lite' ),
				'type'  => 'likert_scale',
				'order' => '14',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-tachometer',
				'name'  => esc_html__( 'Net Promoter Score', 'wpforms-lite' ),
				'type'  => 'net_promoter_score',
				'order' => '15',
				'class' => 'upgrade-modal',
			),
		);

		$fields['payment']['fields'] = array(
			array(
				'icon'  => 'fa-file-o',
				'name'  => esc_html__( 'Single Item', 'wpforms-lite' ),
				'type'  => 'payment-single',
				'order' => '1',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-list-ul',
				'name'  => esc_html__( 'Multiple Items', 'wpforms-lite' ),
				'type'  => 'payment-multiple',
				'order' => '2',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-check-square-o',
				'name'  => esc_html__( 'Checkbox Items', 'wpforms-lite' ),
				'type'  => 'payment-checkbox',
				'order' => '3',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-caret-square-o-down',
				'name'  => esc_html__( 'Dropdown Items', 'wpforms-lite' ),
				'type'  => 'payment-select',
				'order' => '4',
				'class' => 'upgrade-modal',
			),
			array(
				'icon'  => 'fa-money',
				'name'  => esc_html__( 'Total', 'wpforms-lite' ),
				'type'  => 'payment-total',
				'order' => '5',
				'class' => 'upgrade-modal',
			),
		);

		return $fields;
	}

	/**
	 * Display geolocation options.
	 *
	 * @since 1.6.5
	 *
	 * @param array  $field    Field data.
	 * @param object $instance Builder instance.
	 */
	public function geolocation_options( $field, $instance ) {

		if ( ! in_array( $field['type'], [ 'text', 'address' ], true ) ) {
			return;
		}

		$instance->field_element(
			'row',
			$field,
			[
				'slug'    => 'enable_address_autocomplete',
				'class'   => 'upgrade-modal',
				'data'    => [
					'name'    => esc_html__( 'Address Autocomplete', 'wpforms-lite' ),
					'licence' => 'pro',
					'message' => esc_html__( 'We\'re sorry, Address Autocomplete is part of the Geolocation Addon and not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'wpforms-lite' ),
				],
				'content' => $instance->field_element(
					'checkbox',
					$field,
					[
						'slug'  => 'enable_address_autocomplete',
						'value' => 0,
						'desc'  => sprintf(
							'%s<span class="wpforms-field-option-education-pro-badge"></span>',
							esc_html__( 'Enable Address Autocomplete', 'wpforms-lite' )
						),
						'attrs' => [
							'disabled' => 'disabled',
						],
					],
					false
				),
			]
		);
	}

	/**
	 * Display conditional logic settings section for fields inside the form builder.
	 *
	 * @since 1.5.5
	 *
	 * @param array  $field    Field data.
	 * @param object $instance Builder instance.
	 */
	public function field_conditional_logic( $field, $instance ) {

		// Certain fields don't support conditional logic.
		if ( in_array( $field['type'], array( 'pagebreak', 'divider', 'hidden' ), true ) ) {
			return;
		}
		?>

		<div class="wpforms-field-option-group">

			<a href="#" class="wpforms-field-option-group-toggle upgrade-modal" data-name="<?php esc_attr_e( 'Conditional Logic', 'wpforms-lite' ); ?>">
				<?php esc_html_e( 'Conditionals', 'wpforms-lite' ); ?> <i class="fa fa-angle-right"></i>
			</a>

		</div>
		<?php
	}

	/**
	 * Display settings panels.
	 *
	 * @since 1.5.1
	 *
	 * @param object $form Current form.
	 * @param string $slug Panel slug.
	 */
	public function settings( $form, $slug ) {

		if ( empty( $form ) || 'settings' !== $slug ) {
			return;
		}

		$settings = array(
			array(
				'name'        => esc_html__( 'Conversational Forms', 'wpforms-lite' ),
				'slug'        => 'conversational-forms',
				'plugin'      => 'wpforms-conversational-forms/wpforms-conversational-forms.php',
				'plugin_slug' => 'wpforms-conversational-forms',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Surveys and Polls', 'wpforms-lite' ),
				'slug'        => 'surveys-polls',
				'plugin'      => 'wpforms-surveys-polls/wpforms-surveys-polls.php',
				'plugin_slug' => 'wpforms-surveys-polls',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Form Pages', 'wpforms-lite' ),
				'slug'        => 'form-pages',
				'plugin'      => 'wpforms-form-pages/wpforms-form-pages.php',
				'plugin_slug' => 'wpforms-form-pages',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Form Locker', 'wpforms-lite' ),
				'slug'        => 'form-locker',
				'plugin'      => 'wpforms-form-locker/wpforms-form-locker.php',
				'plugin_slug' => 'wpforms-form-locker',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Form Abandonment', 'wpforms-lite' ),
				'slug'        => 'form-abandonment',
				'plugin'      => 'wpforms-form-abandonment/wpforms-form-abandonment.php',
				'plugin_slug' => 'wpforms-form-abandonment',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Post Submissions', 'wpforms-lite' ),
				'slug'        => 'post-submissions',
				'plugin'      => 'wpforms-post-submissions/wpforms-post-submissions.php',
				'plugin_slug' => 'wpforms-post-submissions',
				'license'     => 'pro',
			),
			array(
				'name'        => esc_html__( 'Webhooks', 'wpforms-lite' ),
				'slug'        => 'webhooks',
				'plugin'      => 'wpforms-webhooks/wpforms-webhooks.php',
				'plugin_slug' => 'wpforms-webhooks',
				'license'     => 'elite',
			),
		);

		foreach ( $settings as $setting ) {

			/* translators: %s - addon name. */
			$modal_name = sprintf( esc_html__( '%s addon', 'wpforms-lite' ), $setting['name'] );
			printf(
				'<a href="#" class="wpforms-panel-sidebar-section wpforms-panel-sidebar-section-%s upgrade-modal" data-name="%s" data-license="%s">',
				esc_attr( $setting['slug'] ),
				esc_attr( $modal_name ),
				esc_attr( $setting['license'] )
			);
			echo esc_html( $setting['name'] );
			echo '<i class="fa fa-angle-right wpforms-toggle-arrow"></i>';
			echo '</a>';
		}
	}

	/**
	 * Display providers.
	 *
	 * @since 1.5.1
	 */
	public function providers() {

		$providers = wpforms_get_providers_all();

		foreach ( $providers as $provider ) {
			$this->display_single_addon_btn( $provider );
		}
	}

	/**
	 * Display payments.
	 *
	 * @since 1.5.1
	 */
	public function payments() {

		$payments = array(
			array(
				'name'    => esc_html__( 'PayPal Standard', 'wpforms-lite' ),
				'slug'    => 'paypal_standard',
				'img'     => 'addon-icon-paypal.png',
				'license' => 'pro',
			),
			array(
				'name'    => esc_html__( 'Stripe', 'wpforms-lite' ),
				'slug'    => 'stripe',
				'img'     => 'addon-icon-stripe.png',
				'license' => 'pro',
			),
			array(
				'name'    => esc_html__( 'Authorize.Net', 'wpforms-lite' ),
				'slug'    => 'authorize_net',
				'img'     => 'addon-icon-authorize-net.png',
				'license' => 'elite',
			),
		);

		foreach ( $payments as $payment ) {
			$this->display_single_addon_btn( $payment );
		}
	}

	/**
	 * Display a single addon button in a builder.
	 *
	 * @since 1.5.7
	 *
	 * @param array $addon Required keys: name, slug, img.
	 */
	protected function display_single_addon_btn( $addon ) {

		if ( ! isset( $addon['name'], $addon['slug'], $addon['img'], $addon['license'] ) ) {
			return;
		}

		/* translators: %s - addon name. */
		$modal_name = sprintf( esc_html__( '%s addon', 'wpforms-lite' ), $addon['name'] );
		?>

		<a href="#"
		   class="wpforms-panel-sidebar-section icon wpforms-panel-sidebar-section-<?php echo esc_attr( $addon['slug'] ); ?> upgrade-modal"
		   data-name="<?php echo esc_attr( $modal_name ); ?>"
		   data-license="<?php echo esc_attr( $addon['license'] ); ?>">

			<img src="<?php echo esc_attr( WPFORMS_PLUGIN_URL . 'assets/images/' . $addon['img'] ); ?>" alt="">
			<?php echo esc_html( $addon['name'] ); ?>
			<i class="fa fa-angle-right wpforms-toggle-arrow"></i>
		</a>
		<?php
	}

	/**
	 * Targeting on hCaptcha/reCAPTCHA field button in the builder.
	 *
	 * TODO: Lite and Pro Education duplicate this code.
	 *
	 * @since 1.6.4
	 */
	public function captcha_field_callback() {

		// Run a security check.
		check_ajax_referer( 'wpforms-builder', 'nonce' );

		// Check for permissions.
		if ( ! wpforms_current_user_can() ) {
			wp_send_json_error( esc_html__( 'You do not have permission.', 'wpforms-lite' ) );
		}

		// Check for form ID.
		if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
			wp_send_json_error( esc_html__( 'No form ID found.', 'wpforms-lite' ) );
		}

		// Get an actual form data.
		$form_id   = absint( $_POST['id'] );
		$form_data = wpforms()->form->get( $form_id, array( 'content_only' => true ) );

		// Check that CAPTCHA is configured in the settings.
		$captcha_settings = wpforms_get_captcha_settings();
		$captcha_name     = $this->get_captcha_name( $captcha_settings );

		if ( empty( $form_data ) || empty( $captcha_name ) ) {
			wp_send_json_error( esc_html__( 'Something wrong. Please, try again later.', 'wpforms-lite' ) );
		}

		// Prepare result array.
		$data = [
			'current'  => false,
			'cases'    => [
				'not_configured'         => [
					'title'   => esc_html__( 'Heads up!', 'wpforms-lite' ),
					'content' => sprintf(
						wp_kses( /* translators: %1$s - CAPTCHA settings page URL; %2$s - WPForms.com doc URL; %3$s - CAPTCHA name. */
							__( 'The %3$s settings have not been configured yet. Please complete the setup in your <a href="%1$s" target="_blank">WPForms Settings</a>, and check out our <a href="%2$s" target="_blank" rel="noopener noreferrer">step by step tutorial</a> for full details.', 'wpforms-lite' ),
							[
								'a' => [
									'href'   => true,
									'rel'    => true,
									'target' => true,
								],
							]
						),
						esc_url( admin_url( 'admin.php?page=wpforms-settings&view=captcha' ) ),
						'https://wpforms.com/docs/setup-captcha-wpforms/',
						$captcha_name
					),
				],
				'configured_not_enabled' => [
					'title'   => false,
					/* translators: %s - CAPTCHA name. */
					'content' => sprintf( esc_html__( '%s has been enabled for this form. Don\'t forget to save your form!', 'wpforms-lite' ), $captcha_name ),
				],
				'configured_enabled'     => [
					'title'   => false,
					/* translators: %s - CAPTCHA name. */
					'content' => sprintf( esc_html__( 'Are you sure you want to disable %s for this form?', 'wpforms-lite' ), $captcha_name ),
					'cancel'  => true,
				],
			],
			'provider' => $captcha_settings['provider'],
		];

		if ( empty( $captcha_settings['site_key'] ) || empty( $captcha_settings['secret_key'] ) ) {

			// If CAPTCHA is not configured in the WPForms plugin settings.
			$data['current'] = 'not_configured';

		} elseif ( ! isset( $form_data['settings']['recaptcha'] ) || '1' !== $form_data['settings']['recaptcha'] ) {

			// If CAPTCHA is configured in WPForms plugin settings, but wasn't set in form settings.
			$data['current'] = 'configured_not_enabled';

		} else {

			// If CAPTCHA is configured in WPForms plugin and form settings.
			$data['current'] = 'configured_enabled';
		}

		wp_send_json_success( $data );
	}

	/**
	 * Retrieve the CAPTCHA name.
	 *
	 * @since 1.6.4
	 *
	 * @param array $settings The CAPTCHA settings.
	 *
	 * @return string
	 */
	protected function get_captcha_name( $settings ) {

		if ( empty( $settings['provider'] ) ) {
			return '';
		}

		if ( empty( $settings['site_key'] ) && empty( $settings['secret_key'] ) ) {
			return esc_html__( 'CAPTCHA', 'wpforms-lite' );
		}

		if ( 'hcaptcha' === $settings['provider'] ) {
			return esc_html__( 'hCaptcha', 'wpforms-lite' );
		}

		$recaptcha_names = [
			'v2'        => esc_html__( 'Google Checkbox v2 reCAPTCHA', 'wpforms-lite' ),
			'invisible' => esc_html__( 'Google Invisible v2 reCAPTCHA', 'wpforms-lite' ),
			'v3'        => esc_html__( 'Google v3 reCAPTCHA', 'wpforms-lite' ),
		];

		return isset( $recaptcha_names[ $settings['recaptcha_type'] ] ) ? $recaptcha_names[ $settings['recaptcha_type'] ] : '';
	}

	/**
	 * "Did You Know?" Notifications.
	 *
	 * @since 1.5.8
	 */
	public function dyk_notifications() {

		$this->dyk_display(
			'notifications',
			array(
				'desc' => esc_html__( 'You can have multiple notifications with conditional logic.', 'wpforms-lite' ),
			)
		);
	}

	/**
	 * "Did You Know?" Notifications.
	 *
	 * @since 1.5.8
	 */
	public function dyk_confirmations() {

		$this->dyk_display(
			'confirmations',
			array(
				'desc' => esc_html__( 'You can have multiple confirmations with conditional logic.', 'wpforms-lite' ),
			)
		);
	}

	/**
	 * "Did You Know?" display message.
	 *
	 * @since 1.5.8
	 *
	 * @param string $section  Form builder section/area (slug).
	 * @param array  $settings Notice settings array.
	 */
	public function dyk_display( $section, $settings ) {

		$current_user = wp_get_current_user();
		$dismissed    = get_user_meta( $current_user->ID, 'wpforms_dismissed', true );

		// Check if not dismissed.
		if ( ! empty( $dismissed[ 'dyk-builder-' . $section ] ) ) {
			return;
		}

		$translations = array(
			'upgrade_to_pro' => __( 'Upgrade to Pro.', 'wpforms-lite' ),
			'dismiss_title'  => __( 'Dismiss this message.', 'wpforms-lite' ),
			'did_you_know'   => __( 'Did You Know?', 'wpforms-lite' ),
			'learn_more'     => __( 'Learn More', 'wpforms-lite' ),
		);

		$learn_more = ( ! empty( $settings['more'] ) ) ? '<a href="' . esc_url( $settings['more'] ) . '" class="learn-more">' . esc_html( $translations['learn_more'] ) . '</a>' : '';

		printf(
			'<section class="wpforms-dyk">
				<div class="wpforms-dyk-fbox">
					<div class="wpforms-dyk-message"><b>%s</b><br>%s</div>
					<div class="wpforms-dyk-buttons">
						%s
						<a href="%s" target="_blank" rel="noopener noreferrer" class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey">%s</a>
						<button type="button" class="dismiss" title="%s" data-section="%s"/>
					</div>
				</div>
			</section>',
			esc_html( $translations['did_you_know'] ),
			esc_html( $settings['desc'] ),
			$learn_more,  // phpcs:ignore
			esc_url( wpforms_admin_upgrade_link( 'Form Builder DYK', ucfirst( $section ) ) ),
			esc_html( $translations['upgrade_to_pro'] ),
			esc_attr( $translations['dismiss_title'] ),
			esc_attr( $section )
		);
	}

	/**
	 * Ajax handler for dismissing DYK notices.
	 *
	 * @since 1.5.8
	 */
	public function dyk_ajax_dismiss() {

		// Run a security check.
		check_ajax_referer( 'wpforms-builder', 'nonce' );

		// Check for permissions.
		if ( ! wpforms_current_user_can() ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You do not have permission to perform this action.', 'wpforms-lite' ),
				)
			);
		}

		$current_user = wp_get_current_user();
		$dismissed    = get_user_meta( $current_user->ID, 'wpforms_dismissed', true );

		if ( empty( $dismissed ) ) {
			$dismissed = array();
		}

		$section = ! empty( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';

		$dismissed[ 'dyk-builder-' . $section ] = time();

		update_user_meta( $current_user->ID, 'wpforms_dismissed', $dismissed );
		wp_send_json_success();
	}
}
