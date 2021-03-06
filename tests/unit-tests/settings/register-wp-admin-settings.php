<?php

/**
 * Settings API: WP-Admin Helper Tests
 * Tests the helper class that makes settings (currently present in wp-admin)
 * avaiable to the REST API.
 *
 * @package WooCommerce\Tests\Settings
 * @since 2.7.0
 */
class WC_Tests_Register_WP_Admin_Settings extends WC_Unit_Test_Case {

	/**
	 * @var WC_Settings_Page $page
	 */
	protected $page;

	/**
	 * Initialize a WC_Settings_Page for testing
	 */
	public function setUp() {
		parent::setUp();

		$mock_page = $this->getMock( 'WC_Settings_General' );

		$mock_page
			->expects( $this->any() )
			->method( 'get_id' )
			->will( $this->returnValue( 'page-id' ) );

		$mock_page
			->expects( $this->any() )
			->method( 'get_label' )
			->will( $this->returnValue( 'Page Label' ) );

		$this->page = $mock_page;
	}

	/**
	 * @since 2.7.0
	 * @covers WC_Register_WP_Admin_Settings::__construct
	 */
	public function test_constructor() {
		$settings = new WC_Register_WP_Admin_Settings( $this->page );

		$this->assertEquals( has_filter( 'woocommerce_settings_groups', array( $settings, 'register_group' ) ), 10 );
		$this->assertEquals( has_filter( 'woocommerce_settings-' . $this->page->get_id(), array( $settings, 'register_settings' ) ), 10 );
	}

	/**
	 * @since 2.7.0
	 * @covers WC_Register_WP_Admin_Settings::register_group
	 */
	public function test_register_group() {
		$settings = new WC_Register_WP_Admin_Settings( $this->page );

		$existing = array(
			'id'    => 'existing-id',
			'label' => 'Existing Group',
		);
		$initial  = array( $existing );
		$expected = array(
			$existing,
			array(
				'id'    => $this->page->get_id(),
				'label' => $this->page->get_label(),
			),
		);
		$actual = $settings->register_group( $initial );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 2.7.0
	 */
	public function register_setting_provider() {
		return array(
			// No "id" case
			array(
				array(
					'type' => 'some-type-with-no-id',
				),
				false,
			),
			// All optional properties except 'desc_tip'
			array(
				array(
					'id'      => 'setting-id',
					'type'    => 'select',
					'title'   => 'Setting Name',
					'desc'    => 'Setting Description',
					'default' => 'one',
					'options' => array( 'one', 'two' ),
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'default'     => 'one',
					'options'     => array( 'one', 'two' ),
				),
			),
			// Boolean 'desc_tip' defaulting to 'desc' value
			array(
				array(
					'id'       => 'setting-id',
					'type'     => 'select',
					'title'    => 'Setting Name',
					'desc'     => 'Setting Description',
					'desc_tip' => true,
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'tip'         => 'Setting Description',
				),
			),
			// String 'desc_tip'
			array(
				array(
					'id'       => 'setting-id',
					'type'     => 'select',
					'title'    => 'Setting Name',
					'desc'     => 'Setting Description',
					'desc_tip' => 'Setting Tip',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'tip'         => 'Setting Tip',
				),
			),
			// Empty 'title' and 'desc'
			array(
				array(
					'id'       => 'setting-id',
					'type'     => 'select',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => '',
					'description' => '',
				),
			),
		);
	}

	/**
	 * @since 2.7.0
	 * @dataProvider register_setting_provider
	 * @covers WC_Register_WP_Admin_Settings::register_setting
	 */
	public function test_register_setting( $input, $expected ) {
		$settings = new WC_Register_WP_Admin_Settings( $this->page );

		$actual = $settings->register_setting( $input );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 2.7.0
	 * @covers WC_Register_WP_Admin_Settings::register_settings
	 */
	public function test_register_settings_one_section() {
		$this->page
			->expects( $this->any() )
			->method( 'get_sections' )
			->will( $this->returnValue( array() ) );

		$this->page
			->expects( $this->once() )
			->method( 'get_settings' )
			->with( $this->equalTo( 0 ) )
			->will( $this->returnValue( array() ) );

		$settings = new WC_Register_WP_Admin_Settings( $this->page );

		$expected = array();
		$actual   = $settings->register_settings( array() );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 2.7.0
	 * @covers WC_Register_WP_Admin_Settings::register_settings
	 */
	public function test_register_settings() {
		$this->page
			->expects( $this->any() )
			->method( 'get_sections' )
			->will( $this->returnValue( array() ) );

		$settings = array(
			array(
				'id'   => 'setting-1',
				'type' => 'text',
			),
			array(
				'type' => 'no-id',
			),
			array(
				'id'   => 'setting-2',
				'type' => 'textarea',
			),
		);

		$this->page
			->expects( $this->any() )
			->method( 'get_settings' )
			->will( $this->returnValue( $settings ) );

		$settings = new WC_Register_WP_Admin_Settings( $this->page );

		$expected = array(
			array(
				'id'          => 'setting-1',
				'type'        => 'text',
				'label'       => '',
				'description' => '',
			),
			array(
				'id'          => 'setting-2',
				'type'        => 'textarea',
				'label'       => '',
				'description' => '',
			),
		);
		$actual = $settings->register_settings( array() );

		$this->assertEquals( $expected, $actual );
	}
}
