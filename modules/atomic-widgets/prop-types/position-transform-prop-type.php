<?php

namespace Elementor\Modules\AtomicWidgets\PropTypes;

use Elementor\Modules\AtomicWidgets\PropTypes\Base\Object_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Position_Transform_Prop_Type extends Object_Prop_Type {
	public static function get_key(): string {
		return 'position-transform';
	}

	protected function define_shape(): array {
		return [
			'top' => Size_Prop_Type::make(),
			'right' => Size_Prop_Type::make(),
			'bottom' => Size_Prop_Type::make(),
			'left' => Size_Prop_Type::make(),
		];
	}
}
