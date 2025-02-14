<?php
/**
 * The local SEO settings.
 *
 * @package    RankMath
 * @subpackage RankMath\Local_Seo
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$company = [ [ 'knowledgegraph_type', 'company' ] ];
$person  = [ [ 'knowledgegraph_type', 'person' ] ];

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_type',
		'type'    => 'radio_inline',
		'name'    => esc_html__( 'Person or Company', 'rank-math' ),
		'options' => [
			'person'  => esc_html__( 'Person', 'rank-math' ),
			'company' => esc_html__( 'Organization', 'rank-math' ),
		],
		'desc'    => esc_html__( 'Choose whether the site represents a person or an organization.', 'rank-math' ),
		'default' => 'person',
	]
);

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_name',
		'type'    => 'text',
		'name'    => esc_html__( 'Name', 'rank-math' ),
		'desc'    => esc_html__( 'Your name or company name', 'rank-math' ),
		'default' => get_bloginfo( 'name' ),
	]
);

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_logo',
		'type'    => 'file',
		'name'    => esc_html__( 'Logo', 'rank-math' ),
		'desc'    => __( '<strong>Min Size: 160Χ90px, Max Size: 1920X1080px</strong>.<br /> A squared image is preferred by the search engines.', 'rank-math' ),
		'options' => [ 'url' => false ],
	]
);

$cmb->add_field(
	[
		'id'      => 'url',
		'type'    => 'text_url',
		'name'    => esc_html__( 'URL', 'rank-math' ),
		'desc'    => esc_html__( 'URL of the item.', 'rank-math' ),
		'default' => home_url(),
	]
);

$cmb->add_field(
	[
		'id'   => 'email',
		'type' => 'text',
		'name' => esc_html__( 'Email', 'rank-math' ),
		'desc' => esc_html__( 'Search engines display your email address.', 'rank-math' ),
	]
);

$cmb->add_field(
	[
		'id'   => 'phone',
		'type' => 'text',
		'name' => esc_html__( 'Phone', 'rank-math' ),
		'desc' => esc_html__( 'Search engines may prominently display your contact phone number for mobile users.', 'rank-math' ),
		'dep'  => $person,
	]
);

$cmb->add_field(
	[
		'id'   => 'local_address',
		'type' => 'address',
		'name' => esc_html__( 'Address', 'rank-math' ),
	]
);

$cmb->add_field(
	[
		'id'         => 'local_address_format',
		'type'       => 'textarea_small',
		'name'       => esc_html__( 'Address Format', 'rank-math' ),
		'desc'       => wp_kses_post( __( 'Format used when the address is displayed using the <code>[rank_math_contact_info]</code> shortcode.<br><strong>Available Tags: {address}, {locality}, {region}, {postalcode}, {country}, {gps}</strong>', 'rank-math' ) ),
		'default'    => '{address} {locality}, {region} {postalcode}',
		'classes'    => 'rank-math-address-format',
		'attributes' => [
			'rows'        => 2,
			'placeholder' => '{address} {locality}, {region} {country}. {postalcode}.',
		],
		'dep'        => $company,
	]
);

$cmb->add_field(
	[
		'id'         => 'local_business_type',
		'type'       => 'select',
		'name'       => esc_html__( 'Business Type', 'rank-math' ),
		'options'    => Helper::choices_business_types( true ),
		'attributes' => ( 'data-s2' ),
		'dep'        => $company,
	]
);

$cmb->add_field(
	[
		'id'      => 'opening_hours_format',
		'type'    => 'switch',
		'name'    => esc_html__( 'Opening Hours Format', 'rank-math' ),
		'options' => [
			'off' => '24:00',
			'on'  => '12:00',
		],
		'desc'    => esc_html__( 'Time format used in the contact shortcode.', 'rank-math' ),
		'default' => 'off',
		'dep'     => $company,
	]
);

$opening_hours = $cmb->add_field(
	[
		'id'      => 'opening_hours',
		'type'    => 'group',
		'name'    => esc_html__( 'Opening Hours', 'rank-math' ),
		'desc'    => esc_html__( 'Select opening hours. You can add multiple sets if you have different opening or closing hours on some days or if you have a mid-day break. Times are specified using 24:00 time.', 'rank-math' ),
		'options' => [
			'add_button'    => esc_html__( 'Add time', 'rank-math' ),
			'remove_button' => esc_html__( 'Remove', 'rank-math' ),
		],
		'dep'     => $company,
		'classes' => 'cmb-group-text-only',
	]
);

$cmb->add_group_field(
	$opening_hours,
	[
		'id'      => 'day',
		'type'    => 'select',
		'options' => [
			'Monday'    => esc_html__( 'Monday', 'rank-math' ),
			'Tuesday'   => esc_html__( 'Tuesday', 'rank-math' ),
			'Wednesday' => esc_html__( 'Wednesday', 'rank-math' ),
			'Thursday'  => esc_html__( 'Thursday', 'rank-math' ),
			'Friday'    => esc_html__( 'Friday', 'rank-math' ),
			'Saturday'  => esc_html__( 'Saturday', 'rank-math' ),
			'Sunday'    => esc_html__( 'Sunday', 'rank-math' ),
		],
	]
);

$cmb->add_group_field(
	$opening_hours,
	[
		'id'          => 'time',
		'type'        => 'text',
		'attributes'  => [ 'placeholder' => esc_html__( 'e.g. 09:00-17:00', 'rank-math' ) ],
		'time_format' => 'H:i',
	]
);

$phones = $cmb->add_field(
	[
		'id'      => 'phone_numbers',
		'type'    => 'group',
		'name'    => esc_html__( 'Phone Number', 'rank-math' ),
		'desc'    => esc_html__( 'Search engines may prominently display your contact phone number for mobile users.', 'rank-math' ),
		'options' => [
			'add_button'    => esc_html__( 'Add number', 'rank-math' ),
			'remove_button' => esc_html__( 'Remove', 'rank-math' ),
		],
		'dep'     => $company,
		'classes' => 'cmb-group-text-only',
	]
);

$cmb->add_group_field(
	$phones,
	[
		'id'      => 'type',
		'type'    => 'select',
		'options' => Helper::choices_phone_types(),
		'default' => 'customer_support',
	]
);

$cmb->add_group_field(
	$phones,
	[
		'id'         => 'number',
		'type'       => 'text',
		'attributes' => [ 'placeholder' => esc_html__( 'Format: +1-401-555-1212', 'rank-math' ) ],
	]
);

$cmb->add_field(
	[
		'id'   => 'price_range',
		'type' => 'text',
		'name' => esc_html__( 'Price Range', 'rank-math' ),
		'desc' => esc_html__( 'The price range of the business, for example $$$.', 'rank-math' ),
		'dep'  => $company,
	]
);

$about_page    = Helper::get_settings( 'titles.local_seo_about_page' );
$about_options = [ '' => __( 'Select Page', 'rank-math' ) ];

if ( $about_page ) {
	$about_options[ $about_page ] = get_the_title( $about_page );
}

$cmb->add_field(
	[
		'id'         => 'local_seo_about_page',
		'type'       => 'select',
		'options'    => $about_options,
		'name'       => esc_html__( 'About Page', 'rank-math' ),
		'desc'       => esc_html__( 'Select a page on your site where you want to show the LocalBusiness meta data.', 'rank-math' ),
		'attributes' => ( 'data-s2-pages' ),
	]
);

$contact_page    = Helper::get_settings( 'titles.local_seo_contact_page' );
$contact_options = [ '' => __( 'Select Page', 'rank-math' ) ];

if ( $contact_page ) {
	$contact_options[ $contact_page ] = get_the_title( $contact_page );
}

$cmb->add_field(
	[
		'id'         => 'local_seo_contact_page',
		'type'       => 'select',
		'options'    => $contact_options,
		'name'       => esc_html__( 'Contact Page', 'rank-math' ),
		'desc'       => esc_html__( 'Select a page on your site where you want to show the LocalBusiness meta data.', 'rank-math' ),
		'attributes' => ( 'data-s2-pages' ),
	]
);

$cmb->add_field(
	[
		'id'         => 'maps_api_key',
		'type'       => 'text',
		'name'       => esc_html__( 'Google Maps API Key', 'rank-math' ),
		/* translators: %s expands to "Google Maps Embed API" https://developers.google.com/maps/documentation/embed/ */
		'desc'       => sprintf( esc_html__( 'An API Key is required to display embedded Google Maps on your site. Get it here: %s', 'rank-math' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . __( 'Google Maps Embed API', 'rank-math' ) . '</a>' ),
		'dep'        => $company,
		'attributes' => [ 'type' => 'password' ],
	]
);

$cmb->add_field(
	[
		'id'    => 'geo',
		'type'  => 'text',
		'name'  => esc_html__( 'Geo Coordinates', 'rank-math' ),
		'desc'  => esc_html__( 'Latitude and longitude values separated by comma.', 'rank-math' ),
		'dep'   => $company,
		'after' => '<strong style="margin-top:20px; display:block; text-align:right;">' . sprintf( __( 'Multiple Locations are available in the %s.', 'rank-math' ), '<a href="https://rankmath.com/prcing/?utm_source=Plugin&utm_medium=Multiple%20Location%20Notice&utm_campaign=WP" target="_blank">PRO Version</a>' ) . '</strong>',
	]
);
