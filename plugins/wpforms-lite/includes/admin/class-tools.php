<?php

use WPForms\Logger\Log;

/**
 * Tools admin page class.
 *
 * @since 1.3.9
 */
class WPForms_Tools {

	/**
	 * The current active tab.
	 *
	 * @since 1.3.9
	 *
	 * @var string
	 */
	public $view;

	/**
	 * Template code if generated.
	 *
	 * @since 1.3.9
	 *
	 * @var string
	 */
	private $template = false;

	/**
	 * Registered importers.
	 *
	 * @since 1.4.2
	 *
	 * @var array
	 */
	public $importers = array();

	/**
	 * Available forms for a specific importer.
	 *
	 * @since 1.4.2
	 *
	 * @var array
	 */
	public $importer_forms = array();

	/**
	 * The available forms.
	 *
	 * @since 1.3.9
	 *
	 * @var array
	 */
	public $forms = false;

	/**
	 * The core views.
	 *
	 * @since 1.4.3
	 *
	 * @var array
	 */
	public $views = array();

	/**
	 * Primary class constructor.
	 *
	 * @since 1.3.9
	 */
	public function __construct() {

		// Maybe load tools page.
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Determining if the user is viewing the tools page, if so, party on.
	 *
	 * @since 1.3.9
	 */
	public function init() {

		// Check what page we are on.
		$page = isset( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:ignore WordPress.Security

		// Only load if we are actually on the settings page.
		if ( 'wpforms-tools' !== $page ) {
			return;
		}

		$this->init_view();

		if ( empty( $this->view ) ) {
			return;
		}

		// This is required to catch all manual "Cancel" and "Run" events performed for hooks.
		if ( 'action-scheduler' === $this->view && class_exists( 'ActionScheduler_AdminView' ) ) {
			ActionScheduler_AdminView::instance()->process_admin_ui();
		}

		if ( 'logs' === $this->view ) {
			$this->logs_controller();
		}

		if ( in_array( $this->view, array( 'import', 'importer' ), true ) ) {
			$this->import_controller();
		}

		// Retrieve available forms.
		$this->forms = wpforms()->form->get( '', [ 'orderby' => 'title' ] );

		add_action( 'wpforms_tools_init', [ $this, 'import_export_process' ] );
		add_action( 'wpforms_admin_page', [ $this, 'output' ] );
		add_action( 'admin_init', [ $this, 'register_logs_setting' ] );

		// Hook for addons.
		do_action( 'wpforms_tools_init' );
	}

	/**
	 * Init current view.
	 *
	 * @since 1.6.3
	 */
	private function init_view() {

		$this->register_views();
		$view_ids = call_user_func_array( 'array_merge', $this->views );

		// Determine the current active settings tab.
		$this->view = ! empty( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// If the user tries to load an invalid view - fallback to the first available.
		if (
			! in_array( $this->view, $view_ids, true ) &&
			! has_action( 'wpforms_tools_display_tab_' . sanitize_key( $this->view ) )
		) {
			$this->view = reset( $view_ids );
		}
	}

	/**
	 * Controller for Tools -> Import tab.
	 *
	 * @since 1.6.3
	 */
	private function import_controller() {

		// If we're on the an import related tab, then build a list of
		// all available importers.
		$this->importers = apply_filters( 'wpforms_importers', $this->importers );

		// Get all forms for the previous form provider.
		if ( ! empty( $_GET['provider'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$provider             = sanitize_key( $_GET['provider'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->importer_forms = apply_filters( "wpforms_importer_forms_{$provider}", $this->importer_forms );
		}

		// Load the Underscores templates for importers.
		add_action( 'admin_print_scripts', array( $this, 'importer_templates' ) );
	}

	/**
	 * Register views for Tools menu.
	 *
	 * @since 1.6.3
	 */
	private function register_views() {

		$views = array();

		if ( wpforms_current_user_can( 'create_forms' ) ) {
			$views[ esc_html__( 'Import', 'wpforms-lite' ) ] = array( 'import', 'importer' );
		}

		if ( wpforms_current_user_can( array( 'view_forms', 'view_entries' ) ) ) {
			$views[ esc_html__( 'Export', 'wpforms-lite' ) ] = array( 'export' );
		}

		if ( wpforms_current_user_can() ) {
			$views[ esc_html__( 'System Info', 'wpforms-lite' ) ] = array( 'system' );
		}

		if ( wpforms_current_user_can() && class_exists( 'ActionScheduler_AdminView' ) ) {
			$views[ esc_html__( 'Scheduled Actions', 'wpforms-lite' ) ] = array( 'action-scheduler' );
		}

		if ( wpforms_current_user_can() ) {
			$views[ esc_html__( 'Logs', 'wpforms-lite' ) ] = array( 'logs' );
		}

		// Define the core views for the tools tab.
		$this->views = apply_filters( 'wpforms_tools_views', $views );
	}

	/**
	 * Build the output for the Tools admin page.
	 *
	 * @since 1.3.9
	 */
	public function output() {

		$show_nav = false;
		foreach ( $this->views as $view ) {
			if ( in_array( $this->view, (array) $view, true ) ) {
				$show_nav = true;
				break;
			}
		}
		?>

		<div id="wpforms-tools" class="wrap wpforms-admin-wrap wpforms-tools-tab-<?php echo esc_attr( $this->view ); ?>">

			<?php
			if ( $show_nav ) {
				echo '<ul class="wpforms-admin-tabs">';
				foreach ( $this->views as $label => $view ) {
					$view  = (array) $view;
					$class = in_array( $this->view, $view, true ) ? 'active' : '';
					echo '<li>';
						printf(
							'<a href="%1$s" class="%2$s">%3$s</a>',
							esc_url( admin_url( 'admin.php?page=wpforms-tools&view=' . sanitize_key( $view[0] ) ) ),
							sanitize_html_class( $class ),
							esc_html( $label )
						);
					echo '</li>';
				}
				echo '</ul>';
			}
			?>

			<h1 class="wpforms-h1-placeholder"></h1>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wpforms_notice'] ) && 'forms-imported' === $_GET['wpforms_notice'] ) {
				?>
				<div class="updated notice is-dismissible">
					<p>
						<?php
						printf(
							wp_kses( /* translators: %s - Forms list page URL. */
								__( 'Import was successfully finished. You can go and <a href="%s">check your forms</a>.', 'wpforms-lite' ),
								[ 'a' => [ 'href' => [] ] ]
							),
							esc_url( admin_url( 'admin.php?page=wpforms-overview' ) )
						);
						?>
					</p>
				</div>
				<?php
			}
			?>

			<div class="wpforms-admin-content wpforms-admin-settings">
				<?php
				switch ( $this->view ) {
					case 'system':
						$this->system_info_tab();
						break;
					case 'export':
						$this->export_tab();
						break;
					case 'importer':
						$this->importer_tab();
						break;
					case 'import':
						$this->import_tab();
						break;
					case 'action-scheduler':
						$this->action_scheduler_tab();
						break;
					case 'logs':
						$this->logs_tab();
						break;
					default:
						do_action( 'wpforms_tools_display_tab_' . sanitize_key( $this->view ) );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Import tab contents.
	 *
	 * @since 1.4.2
	 */
	public function import_tab() {

		?>

		<div class="wpforms-setting-row tools">
			<h4><?php esc_html_e( 'WPForms Import', 'wpforms-lite' ); ?></h4>
			<p><?php esc_html_e( 'Select a WPForms export file.', 'wpforms-lite' ); ?></p>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=wpforms-tools&view=import' ) ); ?>">
				<div class="wpforms-file-upload">
					<input type="file" name="file" id="wpforms-tools-form-import" class="inputfile" data-multiple-caption="{count} <?php esc_attr_e( 'files selected', 'wpforms-lite' ); ?>" accept=".json" />
					<label for="wpforms-tools-form-import">
						<span class="fld"><span class="placeholder"><?php esc_html_e( 'No file chosen', 'wpforms-lite' ); ?></span></span>
						<strong class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey">
							<i class="fa fa-upload" aria-hidden="true"></i> <?php esc_html_e( 'Choose a file&hellip;', 'wpforms-lite' ); ?>
						</strong>
					</label>
				</div>
				<br>
				<input type="hidden" name="action" value="import_form">
				<button type="submit" name="submit-importexport" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'Import', 'wpforms-lite' ); ?></button>
				<?php wp_nonce_field( 'wpforms_import_nonce', 'wpforms-tools-importexport-nonce' ); ?>
			</form>
		</div>

		<div class="wpforms-setting-row tools" id="wpforms-importers">
			<h4><?php esc_html_e( 'Import from Other Form Plugins', 'wpforms-lite' ); ?></h4>
			<p><?php esc_html_e( 'Not happy with other WordPress contact form plugins?', 'wpforms-lite' ); ?></p>
			<p><?php esc_html_e( 'WPForms makes it easy for you to switch by allowing you import your third-party forms with a single click.', 'wpforms-lite' ); ?></p>

			<div class="wpforms-importers-wrap">
				<?php if ( empty( $this->importers ) ) { ?>
					<p><?php esc_html_e( 'No form importers are currently enabled.', 'wpforms-lite' ); ?> </p>
				<?php } else { ?>
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
						<span class="choicesjs-select-wrap">
							<select class="choicesjs-select" name="provider" required>
								<option value="" placeholder><?php esc_html_e( 'Select previous contact form plugin...', 'wpforms-lite' ); ?></option>
								<?php
								foreach ( $this->importers as $importer ) {
									$status = '';
									if ( empty( $importer['installed'] ) ) {
										$status = esc_html__( 'Not Installed', 'wpforms-lite' );
									} elseif ( empty( $importer['active'] ) ) {
										$status = esc_html__( 'Not Active', 'wpforms-lite' );
									}
									printf(
										'<option value="%s" %s>%s %s</option>',
										esc_attr( $importer['slug'] ),
										! empty( $status ) ? 'disabled' : '',
										esc_html( $importer['name'] ),
										! empty( $status ) ? '(' . $status . ')' : ''
									);
								}
								?>
							</select>
						</span>
						<br />
						<input type="hidden" name="page" value="wpforms-tools">
						<input type="hidden" name="view" value="importer">
						<button type="submit" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'Import', 'wpforms-lite' ); ?></button>
					</form>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Importer tab contents.
	 *
	 * @since 1.4.2
	 */
	public function importer_tab() {

		$slug     = ! empty( $_GET['provider'] ) ? sanitize_key( $_GET['provider'] ) : '';
		$provider = $this->importers[ $slug ];
		?>

		<div class="wpforms-setting-row tools wpforms-clear section-heading no-desc">
			<div class="wpforms-setting-field">
				<h4><?php esc_html_e( 'Form Import', 'wpforms-lite' ); ?></h4>
			</div>
		</div>

		<div id="wpforms-importer-forms">
			<div class="wpforms-setting-row tools">
				<p><?php esc_html_e( 'Select the forms you would like to import.', 'wpforms-lite' ); ?></p>

				<div class="checkbox-multiselect-columns">
					<div class="first-column">
						<h5 class="header"><?php esc_html_e( 'Available Forms', 'wpforms-lite' ); ?></h5>

						<ul>
							<?php
							if ( empty( $this->importer_forms ) ) {
								echo '<li>' . esc_html__( 'No forms found.', 'wpforms-lite' ) . '</li>';
							} else {
								foreach ( $this->importer_forms as $id => $form ) {
									printf(
										'<li><label><input type="checkbox" name="forms[]" value="%s">%s</label></li>',
										esc_attr( $id ),
										sanitize_text_field( $form )
									);
								}
							}
							?>
						</ul>

						<?php if ( ! empty( $this->importer_forms ) ) : ?>
							<a href="#" class="all"><?php esc_html_e( 'Select All', 'wpforms-lite' ); ?></a>
						<?php endif; ?>

					</div>
					<div class="second-column">
						<h5 class="header"><?php esc_html_e( 'Forms to Import', 'wpforms-lite' ); ?></h5>
						<ul></ul>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $this->importer_forms ) ) : ?>
				<p class="submit">
					<button class="wpforms-btn wpforms-btn-md wpforms-btn-orange" id="wpforms-importer-forms-submit"><?php esc_html_e( 'Import', 'wpforms-lite' ); ?></button>
				</p>
			<?php endif; ?>
		</div>

		<div id="wpforms-importer-analyze">
			<p class="process-analyze">
				<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
				<?php
				printf(
					/* translators: %1$s - current forms counter; %2$s - total forms counter; %3$s - provider name. */
					esc_html__( 'Analyzing %1$s of %2$s forms from %3$s.', 'wpforms-lite' ),
					'<span class="form-current">1</span>',
					'<span class="form-total">0</span>',
					sanitize_text_field( $provider['name'] )
				);
				?>
			</p>
			<div class="upgrade">
				<h5><?php esc_html_e( 'Heads Up!', 'wpforms-lite' ); ?></h5>
				<p><?php esc_html_e( 'One or more of your forms contain fields that are not available in WPForms Lite. To properly import these fields, we recommend upgrading to WPForms Pro.', 'wpforms-lite' ); ?></p>
				<p><?php esc_html_e( 'You can continue with the import without upgrading, and we will do our best to match the fields. However, some of them will be omitted due to compatibility issues.', 'wpforms-lite' ); ?></p>
				<p>
					<a href="<?php echo esc_url( wpforms_admin_upgrade_link( 'tools-import' ) ); ?>" target="_blank" rel="noopener noreferrer" class="wpforms-btn wpforms-btn-md wpforms-btn-orange wpforms-upgrade-modal"><?php esc_html_e( 'Upgrade to WPForms Pro', 'wpforms-lite' ); ?></a>
					<a href="#" class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey" id="wpforms-importer-continue-submit"><?php esc_html_e( 'Continue Import without Upgrading', 'wpforms-lite' ); ?></a>
				</p>
				<hr>
				<p><?php esc_html_e( 'Below is the list of form fields that may be impacted:', 'wpforms-lite' ); ?></p>
			</div>
		</div>

		<div id="wpforms-importer-process">

			<p class="process-count">
				<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
				<?php
				printf(
					/* translators: %1$s - current forms counter; %2$s - total forms counter; %3$s - provider name. */
					esc_html__( 'Importing %1$s of %2$s forms from %3$s.', 'wpforms-lite' ),
					'<span class="form-current">1</span>',
					'<span class="form-total">0</span>',
					sanitize_text_field( $provider['name'] )
				);
				?>
			</p>

			<p class="process-completed">
				<?php
				printf(
					/* translators: %s - number of imported forms. */
					esc_html__( 'Congrats, the import process has finished! We have successfully imported %s forms. You can review the results below.', 'wpforms-lite' ),
					'<span class="forms-completed"></span>'
				);
				?>
			</p>

			<div class="status"></div>

		</div>
		<?php
	}

	/**
	 * Various Underscores templates for form importing.
	 *
	 * @since 1.4.2
	 */
	public function importer_templates() {

		?>

		<script type="text/html" id="tmpl-wpforms-importer-upgrade">
			<# _.each( data, function( item, key ) { #>
				<ul>
					<li class="form">{{ item.name }}</li>
					<# _.each( item.fields, function( val, key ) { #>
						<li>{{ val }}</li>
					<# }) #>
				</ul>
			<# }) #>
		</script>
		<script type="text/html" id="tmpl-wpforms-importer-status-error">
			<div class="item">
				<div class="wpforms-clear">
					<span class="name">
						<i class="status-icon fa fa-times" aria-hidden="true"></i>
						{{ data.name }}
					</span>
				</div>
				<p>{{ data.msg }}</p>
			</div>
		</script>
		<script type="text/html" id="tmpl-wpforms-importer-status-update">
			<div class="item">
				<div class="wpforms-clear">
					<span class="name">
						<# if ( ! _.isEmpty( data.upgrade_omit ) ) { #>
							<i class="status-icon fa fa-exclamation-circle" aria-hidden="true"></i>
						<# } else if ( ! _.isEmpty( data.upgrade_plain ) ) { #>
							<i class="status-icon fa fa-exclamation-triangle" aria-hidden="true"></i>
						<# } else if ( ! _.isEmpty( data.unsupported ) ) { #>
							<i class="status-icon fa fa-info-circle" aria-hidden="true"></i>
						<# } else { #>
							<i class="status-icon fa fa-check" aria-hidden="true"></i>
						<# } #>
						{{ data.name }}
					</span>
					<span class="actions">
						<a href="{{ data.edit }}" target="_blank"><?php esc_html_e( 'Edit', 'wpforms-lite' ); ?></a>
						<span class="sep">|</span>
						<a href="{{ data.preview }}" target="_blank"><?php esc_html_e( 'Preview', 'wpforms-lite' ); ?></a>
					</span>
				</div>
				<# if ( ! _.isEmpty( data.upgrade_omit ) ) { #>
					<p><?php esc_html_e( 'The following fields are available in PRO and were not imported:', 'wpforms-lite' ); ?></p>
					<ul>
						<# _.each( data.upgrade_omit, function( val, key ) { #>
							<li>{{ val }}</li>
						<# }) #>
					</ul>
				<# } #>
				<# if ( ! _.isEmpty( data.upgrade_plain ) ) { #>
					<p><?php esc_html_e( 'The following fields are available in PRO and were imported as text fields:', 'wpforms-lite' ); ?></p>
					<ul>
						<# _.each( data.upgrade_plain, function( val, key ) { #>
							<li>{{ val }}</li>
						<# }) #>
					</ul>
				<# } #>
				<# if ( ! _.isEmpty( data.unsupported ) ) { #>
					<p><?php esc_html_e( 'The following fields are not supported and were not imported:', 'wpforms-lite' ); ?></p>
					<ul>
						<# _.each( data.unsupported, function( val, key ) { #>
							<li>{{ val }}</li>
						<# }) #>
					</ul>
				<# } #>
				<# if ( ! _.isEmpty( data.upgrade_plain ) || ! _.isEmpty( data.upgrade_omit ) ) { #>
				<p>
					<?php esc_html_e( 'Upgrade to the PRO plan to import these fields.' ); ?><br><br>
					<a href="<?php echo esc_url( wpforms_admin_upgrade_link( 'tools-import' ) ); ?>" class="wpforms-btn wpforms-btn-orange wpforms-btn-md wpforms-upgrade-modal" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Upgrade Now', 'wpforms-lite' ); ?>
					</a>
				</p>
				<# } #>
			</div>
		</script>
		<?php
	}

	/**
	 * Export tab contents.
	 *
	 * @since 1.4.2
	 */
	public function export_tab() {

		do_action( 'wpforms_admin_tools_export_top' );

		if ( $this->forms ) {
			$this->export_tab_html();
		}

		do_action( 'wpforms_admin_tools_export_bottom' );
	}

	/**
	 * Export tab contents.
	 *
	 * @since 1.5.8
	 */
	public function export_tab_html() {

		?>

		<div class="wpforms-setting-row tools">

			<h4 id="form-export"><?php esc_html_e( 'Form Export', 'wpforms-lite' ); ?></h4>

			<p><?php esc_html_e( 'Form exports files can be used to create a backup of your forms or to import forms into another site.', 'wpforms-lite' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpforms-tools&view=export' ) ); ?>">
				<?php
				if ( ! empty( $this->forms ) ) {
					echo '<span class="choicesjs-select-wrap">';
						echo '<select id="wpforms-tools-form-export" class="choicesjs-select" name="forms[]" multiple>';
							printf( '<option value="" placeholder>%s</option>', esc_html__( 'Select Form(s)', 'wpforms-lite' ) );
							foreach ( $this->forms as $form ) {
								printf( '<option value="%d">%s</option>', absint( $form->ID ), esc_html( $form->post_title ) );
							}
						echo '</select>';
					echo '</span>';
				} else {
					echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'wpforms-lite' ) . '</p>';
				}
				?>
				<br>
				<input type="hidden" name="action" value="export_form">
				<?php wp_nonce_field( 'wpforms_import_nonce', 'wpforms-tools-importexport-nonce' ); ?>
				<button type="submit" name="submit-importexport" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'Export', 'wpforms-lite' ); ?></button>
			</form>
		</div>

		<div class="wpforms-setting-row tools">

			<h4 id="template-export"><?php esc_html_e( 'Form Template Export', 'wpforms-lite' ); ?></h4>

			<?php
			if ( $this->template ) {
				echo '<p>' . esc_html__( 'The following code can be used to register your custom form template. Copy and paste the following code to your theme\'s functions.php file or include it within an external file.', 'wpforms-lite' ) . '<p>';
				echo '<p>' .
					sprintf(
						wp_kses(
							/* translators: %s - WPForms.com docs URL. */
							__( 'For more information <a href="%s" target="_blank" rel="noopener noreferrer">see our documentation</a>.', 'wpforms-lite' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
							)
						),
						'https://wpforms.com/docs/how-to-create-a-custom-form-template/'
					) .
					'<p>';
				echo '<textarea class="info-area" readonly>' . esc_textarea( $this->template ) . '</textarea><br>';
			}
			?>

			<p><?php esc_html_e( 'Select a form to generate PHP code that can be used to register a custom form template.', 'wpforms-lite' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpforms-tools&view=export#template-export' ) ); ?>">
				<?php
				if ( ! empty( $this->forms ) ) {
					echo '<span class="choicesjs-select-wrap">';
						echo '<select id="wpforms-tools-form-template" class="choicesjs-select" name="form">';
						printf( '<option value="" placeholder>%s</option>', esc_html__( 'Select a Template', 'wpforms-lite' ) );
							foreach ( $this->forms as $form ) {
								printf( '<option value="%d">%s</option>', absint( $form->ID ), esc_html( $form->post_title ) );
							}
						echo '</select>';
					echo '</span>';
				} else {
					echo '<p>' . esc_html__( 'You need to create a form before you can generate a template.', 'wpforms-lite' ) . '</p>';
				}
				?>
				<br>
				<input type="hidden" name="action" value="export_template">
				<?php wp_nonce_field( 'wpforms_import_nonce', 'wpforms-tools-importexport-nonce' ); ?>
				<button type="submit" name="submit-importexport" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'Export Template', 'wpforms-lite' ); ?></button>
			</form>

		</div>

		<?php
	}

	/**
	 * System Info tab contents.
	 *
	 * @since 1.3.9
	 */
	public function system_info_tab() {

		if ( ! wpforms_current_user_can() ) {
			return;
		}

		?>

		<div class="wpforms-setting-row tools">
			<h4 id="form-export"><?php esc_html_e( 'System Information', 'wpforms-lite' ); ?></h4>
			<textarea readonly="readonly" class="info-area"><?php echo $this->get_system_info(); ?></textarea>
		</div>

		<div class="wpforms-setting-row tools">
			<h4 id="ssl-verify"><?php esc_html_e( 'Test SSL Connections', 'wpforms-lite' ); ?></h4>
			<p><?php esc_html_e( 'Click the button below to verify your web server can perform SSL connections successfully.', 'wpforms-lite' ); ?></p>
			<button type="button" id="wpforms-ssl-verify" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"><?php esc_html_e( 'Test Connection', 'wpforms-lite' ); ?></button>
		</div>

		<?php
	}

	/**
	 * Controller for Tools -> Logs tab.
	 *
	 * @since 1.6.3
	 */
	private function logs_controller() {

		$log   = wpforms()->get( 'log' );
		$nonce = filter_input( INPUT_POST, '_wpforms_logs_settings_nonce', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $nonce, 'wpforms-logs-settings' ) ) {
			$settings                = get_option( 'wpforms_settings' );
			$was_enabled             = ! empty( $settings['logs-enable'] ) ? $settings['logs-enable'] : 0;
			$settings['logs-enable'] = filter_input( INPUT_POST, 'logs-enable', FILTER_VALIDATE_BOOLEAN );
			$logs_types              = filter_input( INPUT_POST, 'logs-types', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			$logs_user_roles         = filter_input( INPUT_POST, 'logs-user-roles', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			$logs_users              = filter_input( INPUT_POST, 'logs-users', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY );
			if ( $was_enabled ) {
				$settings['logs-types']      = $logs_types ? $logs_types : [];
				$settings['logs-user-roles'] = $logs_user_roles ? $logs_user_roles : [];
				$settings['logs-users']      = $logs_users ? array_map( 'absint', $logs_users ) : [];
			}
			update_option( 'wpforms_settings', $settings );
			if ( ! empty( $settings['logs-enable'] ) ) {
				$log->create_table();
			}
		}
		$logs_list_table = $log->get_list_table();
		$logs_list_table->process_admin_ui();
	}

	/**
	 * Logs tab contents.
	 *
	 * @since 1.6.3
	 */
	private function logs_tab() {

		$log      = wpforms()->get( 'log' );
		$settings = get_option( 'wpforms_settings' );
		?>
		<form action="" method="POST">
			<?php wp_nonce_field( 'wpforms-logs-settings', '_wpforms_logs_settings_nonce' ); ?>
			<div class="wpforms-setting-row tools">
				<h3><?php esc_html_e( 'Logs', 'wpforms-lite' ); ?></h3>
				<p><?php esc_html_e( 'On this page, you can enable and configure the logging functionality while debugging behavior of various parts of the plugin, including forms and entries processing.', 'wpforms-lite' ); ?></p>
			</div>
			<div class="wpforms-setting-row tools wpforms-setting-row-checkbox wpforms-clear" id="wpforms-setting-row-logs-enable">
				<div class="wpforms-setting-label">
					<label for="wpforms-setting-logs-enable"><?php esc_html_e( 'Enable Logs', 'wpforms-lite' ); ?></label>
				</div>
				<div class="wpforms-setting-field">
					<input
						type="checkbox"
						id="wpforms-setting-logs-enable"
						name="logs-enable"
						value="1"
						<?php checked( ! empty( $settings['logs-enable'] ) ); ?>
					>
					<p class="desc">
						<?php esc_html_e( 'Check this if you would like to start logging WPForms-related events. This is recommended only while debugging.', 'wpforms-lite' ); ?>
					</p>
				</div>
			</div>
			<?php if ( ! empty( $settings['logs-enable'] ) ) { ?>
				<div class="wpforms-setting-row tools wpforms-setting-row-select wpforms-clear" id="wpforms-setting-row-log-types">
					<div class="wpforms-setting-label">
						<label for="wpforms-setting-logs-types"><?php esc_html_e( 'Log Types', 'wpforms-lite' ); ?></label>
					</div>
					<div class="wpforms-setting-field">
						<span class="choicesjs-select-wrap">
							<select id="wpforms-setting-logs-types" class="choicesjs-select" name="logs-types[]" multiple="multiple">
								<?php
								$log_types = ! empty( $settings['logs-types'] ) ? $settings['logs-types'] : [];
								foreach ( Log::get_log_types() as $slug => $name ) {
									?>
									<option
										value="<?php echo esc_attr( $slug ); ?>"
										<?php selected( in_array( $slug, $log_types, true ) ); ?>
									>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php } ?>
							</select>
						</span>
						<p class="desc"><?php esc_html_e( 'Select the types of events you want to log. Everything is logged by default.', 'wpforms-lite' ); ?></p>
					</div>
				</div>
				<div class="wpforms-setting-row tools wpforms-setting-row-select wpforms-clear" id="wpforms-setting-row-log-user-roles">
					<div class="wpforms-setting-label">
						<label for="wpforms-setting-logs-user-roles"><?php esc_html_e( 'User Roles', 'wpforms-lite' ); ?></label>
					</div>
					<div class="wpforms-setting-field">
						<span class="choicesjs-select-wrap">
							<?php
							$logs_user_roles = ! empty( $settings['logs-user-roles'] ) ? $settings['logs-user-roles'] : [];
							$roles           = wp_list_pluck( get_editable_roles(), 'name' );
							?>
							<select id="wpforms-setting-logs-user-roles" class="choicesjs-select" name="logs-user-roles[]" multiple="multiple">
								<?php foreach ( $roles as $slug => $name ) { ?>
									<option
										value="<?php echo esc_attr( $slug ); ?>"
										<?php selected( in_array( $slug, $logs_user_roles, true ) ); ?>
									>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php } ?>
							</select>
							<span class="hidden" id="wpforms-setting-logs-user-roles-selectform-spinner"><i class="fa fa-cog fa-spin fa-lg"></i></span>
						</span>
						<p class="desc"><?php esc_html_e( 'Select the user roles you want to log. All roles are logged by default.', 'wpforms-lite' ); ?></p>
					</div>
				</div>
				<div class="wpforms-setting-row tools wpforms-setting-row-select wpforms-clear" id="wpforms-setting-row-log-users">
					<div class="wpforms-setting-label">
						<label for="wpforms-setting-logs-users"><?php esc_html_e( 'Users', 'wpforms-lite' ); ?></label>
					</div>
					<div class="wpforms-setting-field">
						<span class="choicesjs-select-wrap">
							<select id="wpforms-setting-logs-users" class="choicesjs-select" name="logs-users[]" multiple="multiple">
								<?php
								$users      = get_users(
									[
										'fields' => [ 'ID', 'display_name' ],
									]
								);
								$users      = wp_list_pluck( $users, 'display_name', 'ID' );
								$logs_users = ! empty( $settings['logs-users'] ) ? $settings['logs-users'] : [];
								foreach ( $users as $slug => $name ) {
									?>
									<option
										value="<?php echo esc_attr( $slug ); ?>"
										<?php selected( in_array( $slug, $logs_users, true ) ); ?>
									>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php } ?>
							</select>
							<span class="hidden" id="wpforms-setting-logs-users-selectform-spinner"><i class="fa fa-cog fa-spin fa-lg"></i></span>
						</span>
						<p class="desc"><?php esc_html_e( 'Log events for specific users only. All users are logged by default.', 'wpforms-lite' ); ?></p>
					</div>
				</div>
			<?php } ?>
			<p class="submit">
				<button type="submit" class="wpforms-btn wpforms-btn-md wpforms-btn-orange" name="wpforms-settings-submit">
					<?php esc_html_e( 'Save Settings', 'wpforms-lite' ); ?>
				</button>
			</p>
		</form>
		<?php
		$logs_list_table = $log->get_list_table();

		if ( ! $logs_list_table->table_exists() ) {
			return;
		}

		if ( ! empty( $settings['logs-enable'] ) || $logs_list_table->get_total() ) {
			$logs_list_table->display_page();
		}
	}

	/**
	 * Scheduled Actions tab contents.
	 *
	 * @since 1.6.1
	 */
	public function action_scheduler_tab() {

		if ( ! class_exists( 'ActionScheduler_AdminView' ) ) {
			return;
		}

		ActionScheduler_AdminView::instance()->render_admin_ui();
	}

	/**
	 * Import/Export processing.
	 *
	 * @since 1.3.9
	 */
	public function import_export_process() {

		// Check for triggered save.
		if (
			empty( $_POST['wpforms-tools-importexport-nonce'] ) ||
			empty( $_POST['action'] ) ||
			! isset( $_POST['submit-importexport'] )
		) {
			return;
		}

		// Check for valid nonce and permission.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpforms-tools-importexport-nonce'] ) ), 'wpforms_import_nonce' ) ) {
			return;
		}

		// Import Form(s).
		if ( 'import_form' === $_POST['action'] && ! empty( $_FILES['file']['tmp_name'] ) ) {
			$this->import_process();
		}

		// Export Form(s).
		if ( 'export_form' === $_POST['action'] && ! empty( $_POST['forms'] ) ) {
			$this->export_process();
		}

		// Export form template.
		if ( 'export_template' === $_POST['action'] && ! empty( $_POST['form'] ) ) {
			$this->export_template_process();
		}
	}

	/**
	 * Import processing.
	 *
	 * @since 1.5.8
	 */
	protected function import_process() {

		if ( ! wpforms_current_user_can( 'create_forms' ) ) {
			return;
		}

		// Add filter of the link rel attr to avoid JSON damage.
		add_filter( 'wp_targeted_link_rel', '__return_empty_string', 50, 1 );

		$ext = isset( $_FILES['file']['name'] ) ? strtolower( pathinfo( sanitize_text_field( wp_unslash( $_FILES['file']['name'] ) ), PATHINFO_EXTENSION ) ) : '';

		if ( 'json' !== $ext ) {
			wp_die(
				esc_html__( 'Please upload a valid .json form export file.', 'wpforms-lite' ),
				esc_html__( 'Error', 'wpforms-lite' ),
				array(
					'response' => 400,
				)
			);
		}

		$tmp_name = isset( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( $_FILES['file']['tmp_name'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- wp_unslash() breaks upload on Windows.
		$forms    = json_decode( \WPForms\Helpers\File::remove_utf8_bom( file_get_contents( $tmp_name ) ), true );

		if ( empty( $forms ) || ! is_array( $forms ) ) {
			wp_die(
				esc_html__( 'Form data cannot be imported.', 'wpforms-lite' ),
				esc_html__( 'Error', 'wpforms-lite' ),
				array(
					'response' => 400,
				)
			);
		}

		foreach ( $forms as $form ) {

			$title  = ! empty( $form['settings']['form_title'] ) ? $form['settings']['form_title'] : '';
			$desc   = ! empty( $form['settings']['form_desc'] ) ? $form['settings']['form_desc'] : '';
			$new_id = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_status'  => 'publish',
					'post_type'    => 'wpforms',
					'post_excerpt' => $desc,
				)
			);
			if ( $new_id ) {
				$form['id'] = $new_id;
				wp_update_post(
					array(
						'ID'           => $new_id,
						'post_content' => wpforms_encode( $form ),
					)
				);
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=wpforms-tools&view=importexport&wpforms_notice=forms-imported' ) );
		exit;
	}

	/**
	 * Export processing.
	 *
	 * @since 1.5.8
	 */
	protected function export_process() {

		if ( ! wpforms_current_user_can( 'create_forms' ) ) {
			return;
		}

		$export = array();
		$forms  = get_posts(
			array(
				'post_type'     => 'wpforms',
				'no_found_rows' => true,
				'nopaging'      => true,
				'post__in'      => isset( $_POST['forms'] ) ? array_map( 'intval', $_POST['forms'] ) : array(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		foreach ( $forms as $form ) {
			$export[] = wpforms_decode( $form->post_content );
		}

		ignore_user_abort( true );

		wpforms_set_time_limit();

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpforms-form-export-' . current_time( 'm-d-Y' ) . '.json' );
		header( 'Expires: 0' );

		echo wp_json_encode( $export );
		exit;
	}

	/**
	 * Export template processing.
	 *
	 * @since 1.5.8
	 */
	protected function export_template_process() {

		if ( ! wpforms_current_user_can( 'create_forms' ) ) {
			return;
		}

		if ( ! isset( $_POST['form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$form_data = wpforms()->form->get(
			absint( $_POST['form'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			array( 'content_only' => true )
		);

		if ( ! $form_data ) {
			return;
		}

		// Define basic data.
		$name  = sanitize_text_field( $form_data['settings']['form_title'] );
		$desc  = sanitize_text_field( $form_data['settings']['form_desc'] );
		$slug  = sanitize_key( str_replace( ' ', '_', $form_data['settings']['form_title'] ) );
		$class = 'WPForms_Template_' . $slug;

		// Format template field and settings data.
		$data                     = $form_data;
		$data['meta']['template'] = $slug;
		$data['fields']           = wpforms_array_remove_empty_strings( $data['fields'] );
		$data['settings']         = wpforms_array_remove_empty_strings( $data['settings'] );

		unset( $data['id'] );

		$data = var_export( $data, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$data = str_replace( '  ', "\t", $data );
		$data = preg_replace( '/([\t\r\n]+?)array/', 'array', $data );

		// Build the final template string.
		$this->template = <<<EOT
if ( class_exists( 'WPForms_Template', false ) ) :
/**
 * {$name}
 * Template for WPForms.
 */
class {$class} extends WPForms_Template {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Template name
		\$this->name = '{$name}';

		// Template slug
		\$this->slug = '{$slug}';

		// Template description
		\$this->description = '{$desc}';

		// Template field and settings
		\$this->data = {$data};
	}
}
new {$class};
endif;
EOT;
	}

	/**
	 * Get system information.
	 *
	 * Based on a function from Easy Digital Downloads by Pippin Williamson.
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/admin/tools.php#L470
	 *
	 * @since 1.3.9
	 *
	 * @return string
	 */
	public function get_system_info() {

		global $wpdb;

		// Get theme info.
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		$return = '### Begin System Info ###' . "\n\n";

		// WPForms info.
		$activated = get_option( 'wpforms_activated', array() );
		$return   .= '-- WPForms Info' . "\n\n";
		if ( ! empty( $activated['pro'] ) ) {
			$date    = $activated['pro'] + ( get_option( 'gmt_offset' ) * 3600 );
			$return .= 'Pro:                      ' . date_i18n( esc_html__( 'M j, Y @ g:ia' ), $date ) . "\n";
		}
		if ( ! empty( $activated['lite'] ) ) {
			$date    = $activated['lite'] + ( get_option( 'gmt_offset' ) * 3600 );
			$return .= 'Lite:                     ' . date_i18n( esc_html__( 'M j, Y @ g:ia' ), $date ) . "\n";
		}

		// Now the basics...
		$return .= "\n" . '-- Site Info' . "\n\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		// WordPress configuration.
		$return .= "\n" . '-- WordPress Configuration' . "\n\n";
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$return .= 'Active Theme:             ' . $theme . "\n";
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";
		// Only show page specs if front page is set to 'page'.
		if ( get_option( 'show_on_front' ) === 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id  = get_option( 'page_for_posts' );

			$return .= 'Page On Front:            ' . ( 0 != $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$return .= 'Page For Posts:           ' . ( 0 != $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}
		$return .= 'ABSPATH:                  ' . ABSPATH . "\n";
		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'WPFORMS_DEBUG:            ' . ( defined( 'WPFORMS_DEBUG' ) ? WPFORMS_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

		// @todo WPForms configuration/specific details.
		$return .= "\n" . '-- WordPress Uploads/Constants' . "\n\n";
		$return .= 'WP_CONTENT_DIR:           ' . ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR ? WP_CONTENT_DIR : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'WP_CONTENT_URL:           ' . ( defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL ? WP_CONTENT_URL : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'UPLOADS:                  ' . ( defined( 'UPLOADS' ) ? UPLOADS ? UPLOADS : 'Disabled' : 'Not set' ) . "\n";

		$uploads_dir = wp_upload_dir();

		$return .= 'wp_uploads_dir() path:    ' . $uploads_dir['path'] . "\n";
		$return .= 'wp_uploads_dir() url:     ' . $uploads_dir['url'] . "\n";
		$return .= 'wp_uploads_dir() basedir: ' . $uploads_dir['basedir'] . "\n";
		$return .= 'wp_uploads_dir() baseurl: ' . $uploads_dir['baseurl'] . "\n";

		// Get plugins that have an update.
		$updates = get_plugin_updates();

		// Must-use plugins.
		// NOTE: MU plugins can't show updates!
		$muplugins = get_mu_plugins();
		if ( count( $muplugins ) > 0 && ! empty( $muplugins ) ) {
			$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

			foreach ( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}
		}

		// WordPress active plugins.
		$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
				continue;
			}
			$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		// WordPress inactive plugins.
		$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( in_array( $plugin_path, $active_plugins, true ) ) {
				continue;
			}
			$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		if ( is_multisite() ) {
			// WordPress Multisite active plugins.
			$return .= "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );
				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}
				$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}
		}

		// Server configuration (really just versions).
		$return .= "\n" . '-- Webserver Configuration' . "\n\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		// PHP configs... now we're getting to the important stuff.
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		// PHP extensions and such.
		$return .= "\n" . '-- PHP Extensions' . "\n\n";
		$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient', false ) ? 'Installed' : 'Not Installed' ) . "\n";
		$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

		// Session stuff.
		$return .= "\n" . '-- Session Configuration' . "\n\n";
		$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

		// The rest of this is only relevant if session is enabled.
		if ( isset( $_SESSION ) ) {
			$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
			$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
			$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
			$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
			$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
		}

		$return .= "\n" . '### End System Info ###';

		return $return;
	}
}

new WPForms_Tools();
