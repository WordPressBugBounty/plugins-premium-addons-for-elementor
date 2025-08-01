<?php
/**
 * Premium Button.
 */

namespace PremiumAddons\Widgets;

// Elementor Classes.
use Elementor\Plugin;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;

// PremiumAddons Classes.
use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Helper_Functions;
use PremiumAddons\Includes\Controls\Premium_Post_Filter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * Class Premium_Button
 */
class Premium_Button extends Widget_Base {

	/**
	 * Check if the icon draw is enabled.
	 *
	 * @since 4.9.26
	 * @access private
	 *
	 * @var bool
	 */
	private $is_draw_enabled = null;

	/**
	 * Check Icon Draw Option.
	 *
	 * @since 4.9.26
	 * @access public
	 */
	public function check_icon_draw() {

		if ( null === $this->is_draw_enabled ) {
			$this->is_draw_enabled = Admin_Helper::check_svg_draw( 'premium-button' );
		}

		return $this->is_draw_enabled;
	}

	/**
	 * Retrieve Widget Name.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_name() {
		return 'premium-addon-button';
	}

	/**
	 * Retrieve Widget Title.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_title() {
		return __( 'Button', 'premium-addons-for-elementor' );
	}

	/**
	 * Retrieve Widget Icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string widget icon.
	 */
	public function get_icon() {
		return 'pa-button';
	}

	/**
	 * Retrieve Widget Dependent CSS.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array CSS style handles.
	 */
	public function get_style_depends() {
		return array(
			'pa-glass',
			'pa-btn',
			'premium-addons',
		);
	}

	/**
	 * Retrieve Widget Dependent JS.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array JS script handles.
	 */
	public function get_script_depends() {

		$draw_scripts = $this->check_icon_draw() ? array(
			'pa-tweenmax',
			'pa-motionpath',
		) : array();

		return array_merge(
			$draw_scripts,
			array(
				'lottie-js',
				'pa-glass',
				'premium-addons',
			)
		);
	}

	/**
	 * Retrieve Widget Keywords.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget keywords.
	 */
	public function get_keywords() {
		return array( 'pa', 'premium', 'premium button', 'cta', 'call', 'link', 'btn', 'pa', 'premium' );
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	/**
	 * Retrieve Widget Categories.
	 *
	 * @since 1.5.1
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'premium-elements' );
	}

	/**
	 * Retrieve Widget Support URL.
	 *
	 * @access public
	 *
	 * @return string support URL.
	 */
	public function get_custom_help_url() {
		return 'https://premiumaddons.com/support/';
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Helper_Functions::check_elementor_experiment( 'e_optimized_markup' );
	}

	/**
	 * Register Button controls.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		$draw_icon = $this->check_icon_draw();

		$this->start_controls_section(
			'premium_button_general_section',
			array(
				'label' => __( 'Button', 'premium-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'premium_button_text',
			array(
				'label'       => __( 'Text', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'default'     => __( 'Premium Addons', 'premium-addons-for-elementor' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'premium_button_link_selection',
			array(
				'label'       => __( 'Link Type', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'url'  => __( 'URL', 'premium-addons-for-elementor' ),
					'link' => __( 'Existing Page', 'premium-addons-for-elementor' ),
				),
				'default'     => 'url',
				'label_block' => true,
			)
		);

		$this->add_control(
			'premium_button_link',
			array(
				'label'       => __( 'Link', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array(
					'url' => '#',
				),
				'placeholder' => 'https://premiumaddons.com/',
				'label_block' => true,
				'condition'   => array(
					'premium_button_link_selection' => 'url',
				),
			)
		);

		$this->add_control(
			'premium_button_existing_link',
			array(
				'label'       => __( 'Existing Page', 'premium-addons-for-elementor' ),
				'type'        => Premium_Post_Filter::TYPE,
				'label_block' => true,
				'multiple'    => false,
				'source'      => array( 'post', 'page' ),
				'condition'   => array(
					'premium_button_link_selection' => 'link',
				),
			)
		);

		$this->add_control(
			'premium_button_hover_effect',
			array(
				'label'       => __( 'Hover Effect', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'none',
				'options'     => array(
					'none'   => __( 'None', 'premium-addons-for-elementor' ),
					'style1' => __( 'Slide', 'premium-addons-for-elementor' ),
					'style2' => __( 'Shutter', 'premium-addons-for-elementor' ),
					'style3' => __( 'Icon Fade', 'premium-addons-for-elementor' ),
					'style4' => __( 'Icon Slide', 'premium-addons-for-elementor' ),
					'style5' => __( 'In & Out', 'premium-addons-for-elementor' ),
					'style6' => __( 'Grow', 'premium-addons-for-elementor' ),
					'style7' => __( 'Double Layers', 'premium-addons-for-elementor' ),
					'style8' => __( 'Animated Underline', 'premium-addons-for-elementor' ),
				),
				'separator'   => 'before',
				'label_block' => true,
			)
		);

		$this->add_control(
			'underline_style',
			array(
				'label'       => __( 'Underline Style', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'line1',
				'options'     => array(
					'line1' => __( 'Style 1', 'premium-addons-for-elementor' ),
					'line2' => __( 'Style 2', 'premium-addons-for-elementor' ),
					'line3' => __( 'Style 3', 'premium-addons-for-elementor' ),
					'line4' => __( 'Style 4', 'premium-addons-for-elementor' ),
					'line5' => __( 'Style 5', 'premium-addons-for-elementor' ),
					'line6' => __( 'Style 6', 'premium-addons-for-elementor' ),
					'line7' => __( 'Style 7', 'premium-addons-for-elementor' ),
				),
				'condition'   => array(
					'premium_button_hover_effect' => 'style8',
				),
				'label_block' => true,
			)
		);

		$this->add_responsive_control(
			'line_width',
			array(
				'label'     => __( 'Line Width (%)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_hover_effect' => 'style8',
					'underline_style'             => array( 'line1', 'line3', 'line5' ),
				),
				'default'   => array(
					'size' => 100,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-btn-svg, {{WRAPPER}} .premium-button-line5::before, {{WRAPPER}} .premium-button-line5::after' => 'width: {{SIZE}}%',
				),
			)
		);

		$this->add_responsive_control(
			'line_height',
			array(
				'label'     => __( 'Line Height (PX)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_hover_effect' => 'style8',
					'underline_style!'            => array( 'line1', 'line3', 'line4' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-line2::before, {{WRAPPER}} .premium-button-line5::before, {{WRAPPER}} .premium-button-line5::after, {{WRAPPER}} .premium-button-line6::before, {{WRAPPER}} .premium-button-line7::before' => 'height: {{SIZE}}px',
				),
			)
		);

		$this->add_responsive_control(
			'line_h_position',
			array(
				'label'     => __( 'Line Horizontal Position (%)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_hover_effect' => 'style8',
					'underline_style'             => array( 'line3', 'line5' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-btn-line-wrap, {{WRAPPER}} .premium-button-line5::before, {{WRAPPER}} .premium-button-line5::after' => 'left: {{SIZE}}%',
				),
			)
		);

		$this->add_responsive_control(
			'line_v_position',
			array(
				'label'     => __( 'Line Vertical Position (%)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_hover_effect' => 'style8',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-btn-line-wrap, {{WRAPPER}} .premium-button-line2::before, {{WRAPPER}} .premium-button-line5::before, {{WRAPPER}} .premium-button-line6::before, {{WRAPPER}} .premium-button-line7::before' => 'top: {{SIZE}}%',
					'{{WRAPPER}} .premium-button-line5::after' => 'top: calc( ( {{SIZE}}% + 2px ) + {{line_height.SIZE}}px )',
				),
			)
		);

		$this->add_control(
			'premium_button_style1_dir',
			array(
				'label'       => __( 'Slide Direction', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'bottom',
				'options'     => array(
					'bottom' => __( 'Top to Bottom', 'premium-addons-for-elementor' ),
					'top'    => __( 'Bottom to Top', 'premium-addons-for-elementor' ),
					'left'   => __( 'Right to Left', 'premium-addons-for-elementor' ),
					'right'  => __( 'Left to Right', 'premium-addons-for-elementor' ),
				),
				'condition'   => array(
					'premium_button_hover_effect' => 'style1',
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'premium_button_style2_dir',
			array(
				'label'       => __( 'Shutter Direction', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'shutouthor',
				'options'     => array(
					'shutinhor'    => __( 'Shutter in Horizontal', 'premium-addons-for-elementor' ),
					'shutinver'    => __( 'Shutter in Vertical', 'premium-addons-for-elementor' ),
					'shutoutver'   => __( 'Shutter out Horizontal', 'premium-addons-for-elementor' ),
					'shutouthor'   => __( 'Shutter out Vertical', 'premium-addons-for-elementor' ),
					'scshutoutver' => __( 'Scaled Shutter Vertical', 'premium-addons-for-elementor' ),
					'scshutouthor' => __( 'Scaled Shutter Horizontal', 'premium-addons-for-elementor' ),
					'dshutinver'   => __( 'Tilted Left', 'premium-addons-for-elementor' ),
					'dshutinhor'   => __( 'Tilted Right', 'premium-addons-for-elementor' ),
				),
				'condition'   => array(
					'premium_button_hover_effect' => 'style2',
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'premium_button_style4_dir',
			array(
				'label'       => __( 'Slide Direction', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'bottom',
				'options'     => array(
					'top'    => __( 'Bottom to Top', 'premium-addons-for-elementor' ),
					'bottom' => __( 'Top to Bottom', 'premium-addons-for-elementor' ),
					'left'   => __( 'Left to Right', 'premium-addons-for-elementor' ),
					'right'  => __( 'Right to Left', 'premium-addons-for-elementor' ),
				),
				'label_block' => true,
				'condition'   => array(
					'premium_button_hover_effect' => array( 'style4', 'style7' ),
				),
			)
		);

		$this->add_control(
			'premium_button_style5_dir',
			array(
				'label'       => __( 'Style', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'radialin',
				'options'     => array(
					'radialin'  => __( 'Radial In', 'premium-addons-for-elementor' ),
					'radialout' => __( 'Radial Out', 'premium-addons-for-elementor' ),
					'rectin'    => __( 'Rectangle In', 'premium-addons-for-elementor' ),
					'rectout'   => __( 'Rectangle Out', 'premium-addons-for-elementor' ),
				),
				'condition'   => array(
					'premium_button_hover_effect' => 'style5',
				),
				'label_block' => true,
			)
		);

		$this->add_responsive_control(
			'grow_size',
			array(
				'label'     => __( 'Grow Layer Size', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 500,
					),
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style6',
					'mouse_detect!'               => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button.premium-button-style6:before' => 'width: {{SIZE}}px; height: {{SIZE}}px',
				),
			)
		);

		$this->add_responsive_control(
			'grow_speed',
			array(
				'label'     => __( 'Grow Animation Speed (Sec)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style6',
					'mouse_detect!'               => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button.premium-button-style6:before' => 'transition-duration: {{SIZE}}s',
				),
			)
		);

		$this->add_control(
			'mouse_detect',
			array(
				'label'        => __( 'Detect Mouse Position', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'premium-mouse-detect-',
				'render_type'  => 'template',
				'separator'    => 'after',
				'condition'    => array(
					'premium_button_hover_effect' => 'style6',
				),
			)
		);

		$this->add_control(
			'premium_button_icon_switcher',
			array(
				'label'       => __( 'Icon', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Enable or disable button icon', 'premium-addons-for-elementor' ),
				'condition'   => array(
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$common_conditions = array(
			'premium_button_icon_switcher' => 'yes',
			'premium_button_hover_effect!' => 'style4',
		);

		$this->add_control(
			'icon_type',
			array(
				'label'       => __( 'Icon Type', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'icon'      => __( 'Icon', 'premium-addons-for-elementor' ),
					'animation' => __( 'Lottie Animation', 'premium-addons-for-elementor' ),
					'svg'       => __( 'SVG Code', 'premium-addons-for-elementor' ),
				),
				'default'     => 'icon',
				'label_block' => true,
				'condition'   => $common_conditions,
			)
		);

		$this->add_control(
			'premium_button_icon_selection_updated',
			array(
				'label'            => __( 'Icon', 'premium-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'premium_button_icon_selection',
				'default'          => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
				'condition'        => array_merge(
					$common_conditions,
					array(
						'icon_type' => 'icon',
					)
				),
				'label_block'      => true,
			)
		);

		$this->add_control(
			'custom_svg',
			array(
				'label'       => __( 'SVG Code', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'description' => 'You can use these sites to create SVGs: <a href="https://danmarshall.github.io/google-font-to-svg-path/" target="_blank">Google Fonts</a> and <a href="https://boxy-svg.com/" target="_blank">Boxy SVG</a>',
				'condition'   => array_merge(
					$common_conditions,
					array(
						'icon_type' => 'svg',
					)
				),
			)
		);

		$this->add_control(
			'lottie_url',
			array(
				'label'       => __( 'Animation JSON URL', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'description' => 'Get JSON code URL from <a href="https://lottiefiles.com/" target="_blank">here</a>',
				'label_block' => true,
				'condition'   => array_merge(
					$common_conditions,
					array(
						'icon_type' => 'animation',
					)
				),
			)
		);

		$animation_conds = array(
			'terms' => array(
				array(
					'name'  => 'premium_button_icon_switcher',
					'value' => 'yes',
				),
				array(
					'name'     => 'premium_button_hover_effect',
					'operator' => '!==',
					'value'    => 'style4',
				),
				array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'icon_type',
							'value' => 'animation',
						),
						array(
							'terms' => array(
								array(
									'relation' => 'or',
									'terms'    => array(
										array(
											'name'  => 'icon_type',
											'value' => 'icon',
										),
										array(
											'name'  => 'icon_type',
											'value' => 'svg',
										),
									),
								),
								array(
									'name'  => 'draw_svg',
									'value' => 'yes',
								),
							),
						),
					),
				),
			),
		);

		$this->add_control(
			'draw_svg',
			array(
				'label'       => __( 'Draw Icon', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Enable this option to make the icon drawable. See ', 'premium-addons-for-elementor' ) . '<a href="https://www.youtube.com/watch?v=ZLr0bRe0RAY" target="_blank">tutorial</a>',
				'classes'     => $draw_icon ? '' : 'editor-pa-control-disabled',
				'condition'   => array_merge(
					$common_conditions,
					array(
						'icon_type' => array( 'icon', 'svg' ),
						'premium_button_icon_selection_updated[library]!' => 'svg',
					)
				),
			)
		);

		if ( $draw_icon ) {

			$this->add_control(
				'stroke_width',
				array(
					'label'     => __( 'Path Thickness', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::SLIDER,
					'range'     => array(
						'px' => array(
							'min'  => 0,
							'max'  => 50,
							'step' => 0.1,
						),
					),
					'condition' => array_merge(
						$common_conditions,
						array(
							'icon_type' => array( 'icon', 'svg' ),
						)
					),
					'selectors' => array(
						'{{WRAPPER}} .premium-button-text-icon-wrapper svg:not(.premium-btn-svg) *' => 'stroke-width: {{SIZE}}',
					),
				)
			);

			$this->add_control(
				'svg_sync',
				array(
					'label'     => __( 'Draw All Paths Together', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::SWITCHER,
					'condition' => array_merge(
						$common_conditions,
						array(
							'icon_type' => array( 'icon', 'svg' ),
							'draw_svg'  => 'yes',
						)
					),
				)
			);

			$this->add_control(
				'frames',
				array(
					'label'       => __( 'Speed', 'premium-addons-for-elementor' ),
					'type'        => Controls_Manager::NUMBER,
					'description' => __( 'Larger value means longer animation duration.', 'premium-addons-for-elementor' ),
					'default'     => 5,
					'min'         => 1,
					'max'         => 100,
					'condition'   => array_merge(
						$common_conditions,
						array(
							'icon_type' => array( 'icon', 'svg' ),
							'draw_svg'  => 'yes',
						)
					),
				)
			);

		} else {

			Helper_Functions::get_draw_svg_notice(
				$this,
				'button',
				array_merge(
					$common_conditions,
					array(
						'icon_type' => array( 'icon', 'svg' ),
						'premium_button_icon_selection_updated[library]!' => 'svg',
					)
				)
			);

		}

		$this->add_control(
			'lottie_loop',
			array(
				'label'        => __( 'Loop', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default'      => 'true',
				'conditions'   => $animation_conds,
			)
		);

		$this->add_control(
			'lottie_reverse',
			array(
				'label'        => __( 'Reverse', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'conditions'   => $animation_conds,
			)
		);

		if ( $draw_icon ) {
			$this->add_control(
				'start_point',
				array(
					'label'       => __( 'Start Point (%)', 'premium-addons-for-elementor' ),
					'type'        => Controls_Manager::SLIDER,
					'description' => __( 'Set the point that the SVG should start from.', 'premium-addons-for-elementor' ),
					'default'     => array(
						'unit' => '%',
						'size' => 0,
					),
					'condition'   => array_merge(
						$common_conditions,
						array(
							'icon_type'       => array( 'icon', 'svg' ),
							'draw_svg'        => 'yes',
							'lottie_reverse!' => 'true',
						)
					),
				)
			);

			$this->add_control(
				'end_point',
				array(
					'label'       => __( 'End Point (%)', 'premium-addons-for-elementor' ),
					'type'        => Controls_Manager::SLIDER,
					'description' => __( 'Set the point that the SVG should end at.', 'premium-addons-for-elementor' ),
					'default'     => array(
						'unit' => '%',
						'size' => 0,
					),
					'condition'   => array_merge(
						$common_conditions,
						array(
							'icon_type'      => array( 'icon', 'svg' ),
							'draw_svg'       => 'yes',
							'lottie_reverse' => 'true',
						)
					),

				)
			);

			$this->add_control(
				'svg_hover',
				array(
					'label'        => __( 'Only Play on Hover', 'premium-addons-for-elementor' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'true',
					'condition'    => array_merge(
						$common_conditions,
						array(
							'icon_type' => array( 'icon', 'svg' ),
							'draw_svg'  => 'yes',
						)
					),
				)
			);

			$this->add_control(
				'svg_yoyo',
				array(
					'label'     => __( 'Yoyo Effect', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::SWITCHER,
					'condition' => array_merge(
						$common_conditions,
						array(
							'icon_type'   => array( 'icon', 'svg' ),
							'draw_svg'    => 'yes',
							'lottie_loop' => 'true',
						)
					),
				)
			);
		}

		$this->add_control(
			'slide_icon_type',
			array(
				'label'       => __( 'Icon Type', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => array(
					'icon'      => __( 'Icon', 'premium-addons-for-elementor' ),
					'animation' => __( 'Lottie Animation', 'premium-addons-for-elementor' ),
				),
				'default'     => 'icon',
				'label_block' => true,
				'condition'   => array(
					'premium_button_hover_effect' => 'style4',
				),
			)
		);

		$this->add_control(
			'premium_button_style4_icon_selection_updated',
			array(
				'label'            => __( 'Icon', 'premium-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'premium_button_style4_icon_selection',
				'default'          => array(
					'value'   => 'fas fa-bars',
					'library' => 'fa-solid',
				),
				'condition'        => array(
					'slide_icon_type'             => 'icon',
					'premium_button_hover_effect' => 'style4',
				),
				'label_block'      => true,
			)
		);

		$this->add_control(
			'slide_lottie_url',
			array(
				'label'       => __( 'Animation JSON URL', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
				'description' => 'Get JSON code URL from <a href="https://lottiefiles.com/" target="_blank">here</a>',
				'label_block' => true,
				'condition'   => array(
					'slide_icon_type'             => 'animation',
					'premium_button_hover_effect' => 'style4',
				),
			)
		);

		$this->add_control(
			'premium_button_icon_position',
			array(
				'label'       => __( 'Icon Position', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'before',
				'options'     => array(
					'before' => __( 'Before', 'premium-addons-for-elementor' ),
					'after'  => __( 'After', 'premium-addons-for-elementor' ),
				),
				'label_block' => true,
				'condition'   => array(
					'premium_button_icon_switcher' => 'yes',
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$this->add_responsive_control(
			'premium_button_icon_before_size',
			array(
				'label'      => __( 'Icon Size', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'vw' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_button_icon_switcher' => 'yes',
					'premium_button_hover_effect!' => 'style4',
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button-text-icon-wrapper i' => 'font-size: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .premium-button-text-icon-wrapper svg' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important',
				),
			)
		);

		$this->add_responsive_control(
			'premium_button_icon_style4_size',
			array(
				'label'      => __( 'Icon Size', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'vw' ),
				'condition'  => array(
					'premium_button_hover_effect' => 'style4',
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button-style4-icon-wrapper i' => 'font-size: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .premium-button-style4-icon-wrapper svg' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important',
				),
			)
		);

		$icon_spacing = is_rtl() ? 'left' : 'right';

		$icon_spacing_after = is_rtl() ? 'right' : 'left';

		$this->add_responsive_control(
			'premium_button_icon_before_spacing',
			array(
				'label'     => __( 'Icon Spacing', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'premium_button_icon_position' => 'before',
					'premium_button_hover_effect!' => array( 'style3', 'style4' ),
				),
				'default'   => array(
					'size' => 15,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-text-icon-wrapper i, {{WRAPPER}} .premium-button-text-icon-wrapper svg' => 'margin-' . $icon_spacing . ': {{SIZE}}px',
				),
				'separator' => 'after',
			)
		);

		$this->add_responsive_control(
			'premium_button_icon_after_spacing',
			array(
				'label'     => __( 'Icon Spacing', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'premium_button_icon_position' => 'after',
					'premium_button_hover_effect!' => array( 'style3', 'style4' ),
				),
				'default'   => array(
					'size' => 15,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-text-icon-wrapper i, {{WRAPPER}} .premium-button-text-icon-wrapper svg' => 'margin-' . $icon_spacing_after . ': {{SIZE}}px',
				),
				'separator' => 'after',
			)
		);

		$this->add_responsive_control(
			'premium_button_icon_style3_before_transition',
			array(
				'label'     => __( 'Icon Spacing', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'premium_button_icon_position' => 'before',
					'premium_button_hover_effect'  => 'style3',
				),
				'range'     => array(
					'px' => array(
						'min' => -50,
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-style3-before:hover i, {{WRAPPER}} .premium-button-style3-before:hover svg' => '-webkit-transform: translateX({{SIZE}}{{UNIT}}); transform: translateX({{SIZE}}{{UNIT}})',
				),
			)
		);

		$this->add_responsive_control(
			'premium_button_icon_style3_after_transition',
			array(
				'label'     => __( 'Icon Spacing', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'premium_button_icon_switcher'  => 'yes',
					'premium_button_icon_position!' => 'before',
					'premium_button_hover_effect'   => 'style3',
				),
				'range'     => array(
					'px' => array(
						'min' => -50,
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-style3-after:hover i, {{WRAPPER}} .premium-button-style3-after:hover svg' => '-webkit-transform: translateX({{SIZE}}{{UNIT}}); transform: translateX({{SIZE}}{{UNIT}})',
				),
			)
		);

		$this->add_control(
			'slide_lottie_loop',
			array(
				'label'        => __( 'Loop', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default'      => 'true',
				'condition'    => array(
					'slide_icon_type'             => 'animation',
					'premium_button_hover_effect' => 'style4',
				),
			)
		);

		$this->add_control(
			'slide_lottie_reverse',
			array(
				'label'        => __( 'Reverse', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'condition'    => array(
					'slide_icon_type'             => 'animation',
					'premium_button_hover_effect' => 'style4',
				),
			)
		);

		$this->add_control(
			'premium_button_size',
			array(
				'label'       => __( 'Size', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'lg',
				'options'     => array(
					'sm'    => __( 'Small', 'premium-addons-for-elementor' ),
					'md'    => __( 'Medium', 'premium-addons-for-elementor' ),
					'lg'    => __( 'Large', 'premium-addons-for-elementor' ),
					'block' => __( 'Block', 'premium-addons-for-elementor' ),
				),
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$this->add_responsive_control(
			'premium_button_align',
			array(
				'label'     => __( 'Alignment', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'premium-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'premium-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'premium-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}}',
				),
				'toggle'    => false,
				'default'   => 'center',
				'condition' => array(
					'premium_button_size!' => 'block',
				),
			)
		);

		// Allow admins only to add JS.
		if ( current_user_can( 'administrator' ) ) {
			$this->add_control(
				'premium_button_event_switcher',
				array(
					'label' => __( 'onclick Event', 'premium-addons-for-elementor' ),
					'type'  => Controls_Manager::SWITCHER,
				)
			);

			$this->add_control(
				'premium_button_event_function',
				array(
					'label'     => __( 'Example: myFunction();', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::CODE,
					'dynamic'   => array( 'active' => true ),
					'condition' => array(
						'premium_button_event_switcher' => 'yes',
					),
				)
			);

		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pa_docs',
			array(
				'label' => __( 'Help & Docs', 'premium-addons-for-elementor' ),
			)
		);

		$docs = array(
			'https://premiumaddons.com/docs/button-widget-tutorial' => __( 'Getting started »', 'premium-addons-for-elementor' ),
			'https://premiumaddons.com/docs/how-can-i-open-an-elementor-popup-using-premium-button' => __( 'How to open an Elementor popup using button widget »', 'premium-addons-for-elementor' ),
			'https://premiumaddons.com/docs/how-to-play-pause-a-soundtrack-using-premium-button-widget' => __( 'How to play/pause a soundtrack using button widget »', 'premium-addons-for-elementor' ),
			'https://premiumaddons.com/docs/how-to-use-elementor-widgets-to-navigate-through-carousel-widget-slides/' => __( 'How To Use Premium Button To Navigate Through Carousel Widget Slides »', 'premium-addons-for-elementor' ),
		);

		$doc_index = 1;
		foreach ( $docs as $url => $title ) {

			$doc_url = Helper_Functions::get_campaign_link( $url, 'button-widget', 'wp-editor', 'get-support' );

			$this->add_control(
				'doc_' . $doc_index,
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => sprintf( '<a href="%s" target="_blank">%s</a>', $doc_url, $title ),
					'content_classes' => 'editor-pa-doc',
				)
			);

			++$doc_index;

		}

		$this->end_controls_section();

		$this->start_controls_section(
			'premium_button_style_section',
			array(
				'label' => __( 'Button', 'premium-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'button_width',
			array(
				'label'      => __( 'Width', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 500,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button' => 'width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'premium_button_size!' => 'block',
				),
			)
		);

		if ( $draw_icon ) {
			$this->add_control(
				'svg_color',
				array(
					'label'     => __( 'After Draw Fill Color', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'global'    => false,
					'separator' => 'after',
					'condition' => array(
						'premium_button_icon_switcher' => 'yes',
						'icon_type'                    => array( 'icon', 'svg' ),
						'premium_button_hover_effect!' => 'style4',
						'draw_svg'                     => 'yes',
					),
				)
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'premium_button_typo',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				),
				'selector' => '{{WRAPPER}} .premium-button',
			)
		);

		$this->start_controls_tabs( 'premium_button_style_tabs' );

		$this->start_controls_tab(
			'premium_button_style_normal',
			array(
				'label' => __( 'Normal', 'premium-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'premium_button_text_color_normal',
			array(
				'label'     => __( 'Text Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button .premium-button-text-icon-wrapper span'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'premium_button_icon_color_normal',
			array(
				'label'     => __( 'Icon Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-text-icon-wrapper i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .premium-drawable-icon *, {{WRAPPER}} .premium-button-text-icon-wrapper svg:not([class*="premium-"])' => 'fill: {{VALUE}};',
				),
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'icon_type'                    => array( 'icon', 'svg' ),
					'premium_button_hover_effect!' => array( 'style3', 'style4' ),
				),
			)
		);

		if ( $draw_icon ) {
			$this->add_control(
				'stroke_color',
				array(
					'label'     => __( 'Stroke Color', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'global'    => array(
						'default' => Global_Colors::COLOR_ACCENT,
					),
					'condition' => array(
						'premium_button_icon_switcher' => 'yes',
						'icon_type'                    => array( 'icon', 'svg' ),
						'premium_button_hover_effect!' => array( 'style3', 'style4' ),
					),
					'selectors' => array(
						'{{WRAPPER}} .premium-drawable-icon *, {{WRAPPER}} svg:not([class*="premium-"])' => 'stroke: {{VALUE}};',
					),
				)
			);
		}

		$this->add_control(
			'premium_button_background_normal',
			array(
				'label'     => __( 'Background Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_PRIMARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button, {{WRAPPER}} .premium-button.premium-button-style2-shutinhor:before , {{WRAPPER}} .premium-button.premium-button-style2-shutinver:before , {{WRAPPER}} .premium-button-style5-radialin:before , {{WRAPPER}} .premium-button-style5-rectin:before'  => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_lq_effect',
			array(
				'label'        => __( 'Liquid Glass Effect', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'description'  => sprintf(
					/* translators: 1: `<a>` opening tag, 2: `</a>` closing tag. */
					esc_html__( 'Important: Make sure this element has a semi-transparent background color to see the effect. See all presets from %1$shere%2$s.', 'premium-addons-for-elementor' ),
					'<a href="https://premiumaddons.com/liquid-glass/" target="_blank">',
					'</a>'
				),
				'options'      => array(
					'none'   => __( 'None', 'premium-addons-for-elementor' ),
					'glass1' => __( 'Preset 01', 'premium-addons-for-elementor' ),
					'glass2' => __( 'Preset 02', 'premium-addons-for-elementor' ),
					'glass3' => apply_filters( 'pa_pro_label', __( 'Preset 03 (Pro)', 'premium-addons-for-elementor' ) ),
					'glass4' => apply_filters( 'pa_pro_label', __( 'Preset 04 (Pro)', 'premium-addons-for-elementor' ) ),
					'glass5' => apply_filters( 'pa_pro_label', __( 'Preset 05 (Pro)', 'premium-addons-for-elementor' ) ),
					'glass6' => apply_filters( 'pa_pro_label', __( 'Preset 06 (Pro)', 'premium-addons-for-elementor' ) ),
				),
				'prefix_class' => 'premium-lq__',
				'default'      => 'none',
				'render_type'  => 'template',
				'label_block'  => true,
				'condition'    => array(
					'premium_button_hover_effect!' => array( 'style2', 'style5' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_button_border_normal',
				'selector' => '{{WRAPPER}} .premium-button',
			)
		);

		$this->add_control(
			'premium_button_border_radius_normal',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'button_adv_radius!' => 'yes',
				),
			)
		);

		$this->add_control(
			'button_adv_radius',
			array(
				'label'       => __( 'Advanced Border Radius', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Apply custom radius values. Get the radius value from ', 'premium-addons-for-elementor' ) . '<a href="https://9elements.github.io/fancy-border-radius/" target="_blank">here</a>' . __( '. See ', 'premium-addons-for-elementor' ) . '<a href="https://www.youtube.com/watch?v=S0BJazLHV-M" target="_blank">tutorial</a>',
			)
		);

		$this->add_control(
			'button_adv_radius_value',
			array(
				'label'     => __( 'Border Radius', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => array( 'active' => true ),
				'selectors' => array(
					'{{WRAPPER}} .premium-button' => 'border-radius: {{VALUE}};',
				),
				'condition' => array(
					'button_adv_radius' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'label'     => __( 'Icon Shadow', 'premium-addons-for-elementor' ),
				'name'      => 'premium_button_icon_shadow_normal',
				'selector'  => '{{WRAPPER}} .premium-button-text-icon-wrapper i',
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'icon_type'                    => 'icon',
					'premium_button_hover_effect!' => array( 'style3', 'style4' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'label'    => __( 'Text Shadow', 'premium-addons-for-elementor' ),
				'name'     => 'premium_button_text_shadow_normal',
				'selector' => '{{WRAPPER}} .premium-button-text-icon-wrapper span',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'label'    => __( 'Button Shadow', 'premium-addons-for-elementor' ),
				'name'     => 'premium_button_box_shadow_normal',
				'selector' => '{{WRAPPER}} .premium-button',
			)
		);

		$this->add_responsive_control(
			'premium_button_margin_normal',
			array(
				'label'      => __( 'Margin', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'premium_button_padding_normal',
			array(
				'label'      => __( 'Padding', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button, {{WRAPPER}} .premium-button-line6::after' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'premium_button_style_hover',
			array(
				'label' => __( 'Hover', 'premium-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'premium_button_text_color_hover',
			array(
				'label'     => __( 'Text Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_PRIMARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button:hover .premium-button-text-icon-wrapper span, {{WRAPPER}} .premium-button-line6::after'   => 'color: {{VALUE}};',
				),
				'condition' => array(
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$this->add_control(
			'premium_button_icon_color_hover',
			array(
				'label'     => __( 'Icon Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_PRIMARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button:hover .premium-button-text-icon-wrapper i'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .premium-button:hover .premium-drawable-icon *, {{WRAPPER}} .premium-button:hover svg:not([class*="premium-"])'   => 'fill: {{VALUE}};',
				),
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'icon_type'                    => array( 'icon', 'svg' ),
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$this->add_control(
			'underline_color',
			array(
				'label'     => __( 'Line Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-btn-svg' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .premium-button-line2::before, {{WRAPPER}} .premium-button-line4::before, {{WRAPPER}} .premium-button-line5::before, {{WRAPPER}} .premium-button-line5::after, {{WRAPPER}} .premium-button-line6::before, {{WRAPPER}} .premium-button-line7::before' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style8',
				),
			)
		);

		if ( $draw_icon ) {
			$this->add_control(
				'stroke_color_hover',
				array(
					'label'     => __( 'Stroke Color', 'premium-addons-for-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'global'    => array(
						'default' => Global_Colors::COLOR_ACCENT,
					),
					'condition' => array(
						'premium_button_icon_switcher' => 'yes',
						'icon_type'                    => array( 'icon', 'svg' ),
						'premium_button_hover_effect!' => 'style4',
					),
					'selectors' => array(
						'{{WRAPPER}} .premium-button:hover .premium-drawable-icon *, {{WRAPPER}} .premium-button:hover svg:not([class*="premium-"])' => 'stroke: {{VALUE}};',
					),
				)
			);
		}

		$this->add_control(
			'premium_button_style4_icon_color',
			array(
				'label'     => __( 'Icon Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_PRIMARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-style4-icon-wrapper'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .premium-button-style4-icon-wrapper svg'   => 'fill: {{VALUE}}',
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style4',
					'slide_icon_type'             => 'icon',
				),
			)
		);

		$this->add_control(
			'premium_button_background_hover',
			array(
				'label'     => __( 'Background Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_TEXT,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-none:hover, {{WRAPPER}} .premium-button-style8:hover, {{WRAPPER}} .premium-button-style1:before, {{WRAPPER}} .premium-button-style2-shutouthor:before, {{WRAPPER}} .premium-button-style2-shutoutver:before, {{WRAPPER}} .premium-button-style2-shutinhor, {{WRAPPER}} .premium-button-style2-shutinver, {{WRAPPER}} .premium-button-style2-dshutinhor:before, {{WRAPPER}} .premium-button-style2-dshutinver:before, {{WRAPPER}} .premium-button-style2-scshutouthor:before, {{WRAPPER}} .premium-button-style2-scshutoutver:before, {{WRAPPER}} .premium-button-style3-after:hover, {{WRAPPER}} .premium-button-style3-before:hover, {{WRAPPER}} .premium-button-style4-icon-wrapper, {{WRAPPER}} .premium-button-style5-radialin, {{WRAPPER}} .premium-button-style5-radialout:before, {{WRAPPER}} .premium-button-style5-rectin, {{WRAPPER}} .premium-button-style5-rectout:before, {{WRAPPER}} .premium-button-style6-bg, {{WRAPPER}} .premium-button-style6:before' => 'background-color: {{VALUE}}',
				),
				'condition' => array(
					'premium_button_hover_effect!' => 'style7',

				),
			)
		);

		$this->add_control(
			'first_layer_hover',
			array(
				'label'     => __( 'Layer #1 Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-style7 .premium-button-text-icon-wrapper:before' => 'background-color: {{VALUE}}',
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style7',

				),
			)
		);

		$this->add_control(
			'second_layer_hover',
			array(
				'label'     => __( 'Layer #2 Color', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_TEXT,
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-style7 .premium-button-text-icon-wrapper:after' => 'background-color: {{VALUE}}',
				),
				'condition' => array(
					'premium_button_hover_effect' => 'style7',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_button_border_hover',
				'selector' => '{{WRAPPER}} .premium-button:hover',
			)
		);

		$this->add_control(
			'premium_button_border_radius_hover',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button:hover' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'button_hover_adv_radius!' => 'yes',
				),
			)
		);

		$this->add_control(
			'button_hover_adv_radius',
			array(
				'label'       => __( 'Advanced Border Radius', 'premium-addons-for-elementor' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Apply custom radius values. Get the radius value from ', 'premium-addons-for-elementor' ) . '<a href="https://9elements.github.io/fancy-border-radius/" target="_blank">here</a>' . __( '. See ', 'premium-addons-for-elementor' ) . '<a href="https://www.youtube.com/watch?v=S0BJazLHV-M" target="_blank">tutorial</a>',
			)
		);

		$this->add_control(
			'button_hover_adv_radius_value',
			array(
				'label'     => __( 'Border Radius', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => array( 'active' => true ),
				'selectors' => array(
					'{{WRAPPER}} .premium-button:hover' => 'border-radius: {{VALUE}};',
				),
				'condition' => array(
					'button_hover_adv_radius' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'label'     => __( 'Icon Shadow', 'premium-addons-for-elementor' ),
				'name'      => 'premium_button_icon_shadow_hover',
				'selector'  => '{{WRAPPER}} .premium-button:hover .premium-button-text-icon-wrapper i',
				'condition' => array(
					'premium_button_icon_switcher' => 'yes',
					'icon_type'                    => 'icon',
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'label'     => __( 'Icon Shadow', 'premium-addons-for-elementor' ),
				'name'      => 'premium_button_style4_icon_shadow_hover',
				'selector'  => '{{WRAPPER}} .premium-button:hover .premium-button-style4-icon-wrapper',
				'condition' => array(
					'premium_button_hover_effect' => 'style4',
					'slide_icon_type'             => 'icon',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'label'     => __( 'Text Shadow', 'premium-addons-for-elementor' ),
				'name'      => 'premium_button_text_shadow_hover',
				'selector'  => '{{WRAPPER}} .premium-button:hover .premium-button-text-icon-wrapper span',
				'condition' => array(
					'premium_button_hover_effect!' => 'style4',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'label'    => __( 'Button Shadow', 'premium-addons-for-elementor' ),
				'name'     => 'premium_button_box_shadow_hover',
				'selector' => '{{WRAPPER}} .premium-button:hover',
			)
		);

		$this->add_responsive_control(
			'premium_button_margin_hover',
			array(
				'label'      => __( 'Margin', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button:hover' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'premium_button_padding_hover',
			array(
				'label'      => __( 'Padding', 'premium-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .premium-button:hover' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Render Button output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$this->add_inline_editing_attributes( 'premium_button_text' );

		$button_text = $settings['premium_button_text'];

		if ( 'url' === $settings['premium_button_link_selection'] ) {
			$button_url = $settings['premium_button_link'];
		} else {
			$button_url = get_permalink( $settings['premium_button_existing_link'] );
		}

		$button_size = 'premium-btn-' . $settings['premium_button_size'];

		if ( 'yes' === $settings['premium_button_icon_switcher'] ) {

			$icon_type = $settings['icon_type'];

			if ( 'icon' === $icon_type || 'svg' === $icon_type ) {

				$this->add_render_attribute( 'icon', 'class', 'premium-drawable-icon' );

				if ( 'yes' === $settings['draw_svg'] ) {

					$this->add_render_attribute(
						'button',
						'class',
						array(
							'elementor-invisible',
							'premium-drawer-hover',
						)
					);

					$this->add_render_attribute(
						'icon',
						array(
							'class'            => 'premium-svg-drawer',
							'data-svg-reverse' => $settings['lottie_reverse'],
							'data-svg-loop'    => $settings['lottie_loop'],
							'data-svg-sync'    => $settings['svg_sync'],
							'data-svg-hover'   => $settings['svg_hover'],
							'data-svg-fill'    => $settings['svg_color'],
							'data-svg-frames'  => $settings['frames'],
							'data-svg-yoyo'    => $settings['svg_yoyo'],
							'data-svg-point'   => $settings['lottie_reverse'] ? $settings['end_point']['size'] : $settings['start_point']['size'],
						)
					);

				} else {
					$this->add_render_attribute( 'icon', 'class', 'premium-svg-nodraw' );
				}
			} else {
				$this->add_render_attribute(
					'lottie',
					array(
						'class'               => 'premium-lottie-animation',
						'data-lottie-url'     => $settings['lottie_url'],
						'data-lottie-loop'    => $settings['lottie_loop'],
						'data-lottie-reverse' => $settings['lottie_reverse'],
					)
				);
			}
		}

		if ( 'none' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-none';
		} elseif ( 'style1' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style1-' . $settings['premium_button_style1_dir'];
		} elseif ( 'style2' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style2-' . $settings['premium_button_style2_dir'];
		} elseif ( 'style3' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style3-' . $settings['premium_button_icon_position'];
		} elseif ( 'style4' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style4-' . $settings['premium_button_style4_dir'];

			$slide_icon_type = $settings['slide_icon_type'];

			if ( 'icon' !== $slide_icon_type ) {

				$this->add_render_attribute(
					'slide_lottie',
					array(
						'class'               => 'premium-lottie-animation',
						'data-lottie-url'     => $settings['slide_lottie_url'],
						'data-lottie-loop'    => $settings['slide_lottie_loop'],
						'data-lottie-reverse' => $settings['slide_lottie_reverse'],
					)
				);

			}
		} elseif ( 'style5' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style5-' . $settings['premium_button_style5_dir'];
		} elseif ( 'style6' === $settings['premium_button_hover_effect'] ) {
			$style_dir    = 'premium-button-style6';
			$mouse_detect = $settings['mouse_detect'];
			$this->add_render_attribute( 'style6', 'class', 'premium-button-style6-bg' );
		} elseif ( 'style7' === $settings['premium_button_hover_effect'] ) {
			$style_dir = 'premium-button-style7-' . $settings['premium_button_style4_dir'];
		} else {
			$style_dir = 'premium-button-' . $settings['underline_style'];

			$this->add_render_attribute( 'button', 'data-text', $button_text );
		}

		$this->add_render_attribute(
			'button',
			'class',
			array(
				'premium-button',
				'premium-button-' . $settings['premium_button_hover_effect'],
				$button_size,
				$style_dir,
			)
		);

		if ( ! empty( $button_url ) ) {

			if ( 'url' === $settings['premium_button_link_selection'] ) {
				$this->add_link_attributes( 'button', $button_url );
			} else {
				$this->add_render_attribute( 'button', 'href', $button_url );
			}
		}

		if ( isset( $settings['premium_button_event_switcher'] ) ) {
			$button_event = $settings['premium_button_event_function'];
			if ( 'yes' === $settings['premium_button_event_switcher'] && ! empty( $button_event ) ) {

				if ( Helper_Functions::check_capability( 'unfiltered_html' ) ) {
					$this->add_render_attribute( 'button', 'onclick', ( $button_event ) );
				}
			}
		}

		?>


		<a <?php echo wp_kses_post( $this->get_render_attribute_string( 'button' ) ); ?>>
			<div class="premium-button-text-icon-wrapper">
				<?php if ( 'yes' === $settings['premium_button_icon_switcher'] ) : ?>

					<?php if ( 'before' === $settings['premium_button_icon_position'] && 'style4' !== $settings['premium_button_hover_effect'] ) : ?>
						<?php if ( 'icon' === $icon_type ) : ?>
							<?php

								echo Helper_Functions::get_svg_by_icon(
									$settings['premium_button_icon_selection_updated'],
									$this->get_render_attribute_string( 'icon' )
								);// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							?>
						<?php elseif ( 'svg' === $icon_type ) : ?>
							<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'icon' ) ); ?>>
								<?php $this->print_unescaped_setting( 'custom_svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php else : ?>
							<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'lottie' ) ); ?>></div>
						<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( ! empty( $button_text ) ) : ?>
					<span <?php echo wp_kses_post( $this->get_render_attribute_string( 'premium_button_text' ) ); ?>>
						<?php echo wp_kses_post( $button_text ); ?>
					</span>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['premium_button_icon_switcher'] ) : ?>
					<?php if ( 'after' === $settings['premium_button_icon_position'] && 'style4' !== $settings['premium_button_hover_effect'] ) : ?>
						<?php if ( 'icon' === $icon_type ) : ?>
							<?php
								echo Helper_Functions::get_svg_by_icon(
									$settings['premium_button_icon_selection_updated'],
									$this->get_render_attribute_string( 'icon' )
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						<?php elseif ( 'svg' === $icon_type ) : ?>
							<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'icon' ) ); ?>>
								<?php $this->print_unescaped_setting( 'custom_svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php else : ?>
							<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'lottie' ) ); ?>></div>
						<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( 'style4' === $settings['premium_button_hover_effect'] ) : ?>
				<div class="premium-button-style4-icon-wrapper <?php echo esc_attr( $settings['premium_button_style4_dir'] ); ?>">
					<?php if ( 'icon' === $slide_icon_type ) : ?>
						<?php

							Icons_Manager::render_icon( $settings['premium_button_style4_icon_selection_updated'], array( 'aria-hidden' => 'true' ) );
						?>
					<?php else : ?>
						<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'slide_lottie' ) ); ?>></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( 'style6' === $settings['premium_button_hover_effect'] && 'yes' === $mouse_detect ) : ?>
				<span <?php echo wp_kses_post( $this->get_render_attribute_string( 'style6' ) ); ?>></span>
			<?php endif; ?>

			<?php if ( 'style8' === $settings['premium_button_hover_effect'] ) : ?>
				<?php echo Helper_Functions::get_btn_svgs( $settings['underline_style'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

		</a>


		<?php
	}

	/**
	 * Render Button widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function content_template() {}
}
