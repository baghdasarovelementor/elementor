<?php

namespace Elementor\Modules\AtomicWidgets;

use Elementor\Core\Base\Module as BaseModule;
use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Elements_Manager;
use Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Settings\Classes_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Settings\Image_Src_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Settings\Image_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Primitive_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Styles\Linked_Dimensions_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers\Styles\Size_Transformer;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers_Registry;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Linked_Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Container;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\Widgets\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Widgets\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Styles\Atomic_Widget_Styles;
use Elementor\Plugin;
use Elementor\Widgets_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Module extends BaseModule {
	const EXPERIMENT_NAME = 'atomic_widgets';

	const PACKAGES = [
		'editor-documents', // TODO: NEED to be removed once the editor will not be dependent on the documents package.
		'editor-panels',
		'editor-editing-panel',
		'editor-style',
	];

	public function get_name() {
		return 'atomic-widgets';
	}

	public function __construct() {
		parent::__construct();

		$this->register_experiment();

		if ( Plugin::$instance->experiments->is_feature_active( self::EXPERIMENT_NAME ) ) {
			Dynamic_Tags_Module::instance()->register_hooks();

			( new Atomic_Widget_Styles() )->register_hooks();

			add_filter( 'elementor/editor/v2/packages', fn( $packages ) => $this->add_packages( $packages ) );
			add_filter( 'elementor/widgets/register', fn( Widgets_Manager $widgets_manager ) => $this->register_widgets( $widgets_manager ) );
			add_action( 'elementor/atomic-widgets/settings/transformers/register', fn ( $transformers ) => $this->register_settings_transformers( $transformers ) );
			add_action( 'elementor/atomic-widgets/styles/transformers/register', fn ( $transformers ) => $this->register_styles_transformers( $transformers ) );
			add_action( 'elementor/elements/elements_registered', function ( $elements_manager ) {
				$this->register_elements( $elements_manager );
			} );

			add_action( 'elementor/editor/after_enqueue_scripts', fn() => $this->enqueue_scripts() );
		}
	}

	private function register_experiment() {
		Plugin::$instance->experiments->add_feature( [
			'name' => self::EXPERIMENT_NAME,
			'title' => esc_html__( 'Atomic Widgets', 'elementor' ),
			'description' => esc_html__( 'Enable atomic widgets.', 'elementor' ),
			'hidden' => true,
			'default' => Experiments_Manager::STATE_INACTIVE,
			'release_status' => Experiments_Manager::RELEASE_STATUS_ALPHA,
		] );
	}

	private function add_packages( $packages ) {
		return array_merge( $packages, self::PACKAGES );
	}

	private function register_widgets( Widgets_Manager $widgets_manager ) {
		$widgets_manager->register( new Atomic_Heading() );
		$widgets_manager->register( new Atomic_Image() );
	}

	private function register_elements( Elements_Manager $elements_manager ) {
		$elements_manager->register_element_type( new Atomic_Container() );
	}

	private function register_settings_transformers( Transformers_Registry $transformers ) {
		// Primitives
		$transformers->register( Boolean_Prop_Type::get_key(), new Primitive_Transformer() );
		$transformers->register( Number_Prop_Type::get_key(), new Primitive_Transformer() );
		$transformers->register( String_Prop_Type::get_key(), new Primitive_Transformer() );

		// Other
		$transformers->register( Classes_Prop_Type::get_key(), new Classes_Transformer() );
		$transformers->register( Image_Prop_Type::get_key(), new Image_Transformer() );
		$transformers->register( Image_Src_Prop_Type::get_key(), new Image_Src_Transformer() );
		$transformers->register( Image_Attachment_Id_Prop_Type::get_key(), new Primitive_Transformer() );
		$transformers->register( Url_Prop_Type::get_key(), new Primitive_Transformer() );
	}

	private function register_styles_transformers( Transformers_Registry $transformers ) {
		// Primitives
		$transformers->register( Boolean_Prop_Type::get_key(), new Primitive_Transformer() );
		$transformers->register( Number_Prop_Type::get_key(), new Primitive_Transformer() );
		$transformers->register( String_Prop_Type::get_key(), new Primitive_Transformer() );

		// Other
		$transformers->register( Linked_Dimensions_Prop_Type::get_key(), new Linked_Dimensions_Transformer() );
		$transformers->register( Size_Prop_Type::get_key(), new Size_Transformer() );
		$transformers->register( Color_Prop_Type::get_key(), new Primitive_Transformer() );
	}

	/**
	 * Enqueue the module scripts.
	 *
	 * @return void
	 */
	private function enqueue_scripts() {
		wp_enqueue_script(
			'elementor-atomic-widgets-editor',
			$this->get_js_assets_url( 'atomic-widgets-editor' ),
			[ 'elementor-editor' ],
			ELEMENTOR_VERSION,
			true
		);
	}
}
