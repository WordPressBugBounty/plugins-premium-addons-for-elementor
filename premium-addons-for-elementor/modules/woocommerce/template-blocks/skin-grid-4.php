<?php
/**
 * PA Grid Skin.
 *
 * @package PA
 */

namespace PremiumAddons\Modules\Woocommerce\TemplateBlocks;

use PremiumAddons\Modules\Woocommerce\TemplateBlocks\Skin_Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * Class Skin_Grid_4
 */
class Skin_Grid_4 extends Skin_Style {

	/**
	 * Loop Template.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function render_product_template() {

		$settings = self::$settings;

		include PREMIUM_ADDONS_PATH . 'modules/woocommerce/templates/product-4.php';
	}
}
