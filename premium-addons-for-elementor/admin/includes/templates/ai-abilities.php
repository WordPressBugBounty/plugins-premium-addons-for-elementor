<?php
/**
 * AI Abilities tab.
 *
 * Lists the Premium Addons abilities exposed to AI agents through the MCP
 * server, grouped by ability category. Both lists are pulled live from the
 * WordPress Abilities API registry, so they stay in sync as abilities ship.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Abilities/categories the AI Abilities feature registers are namespaced under this prefix.
$pa_ability_prefix = 'premium-addons/';

// The Abilities API ships with WordPress 6.9. This tab is only reachable when the
// feature is enabled (which itself requires 6.9+), but guard in case core is older.
$pa_categories = function_exists( 'wp_get_ability_categories' ) ? wp_get_ability_categories() : array();
$pa_abilities  = function_exists( 'wp_get_abilities' ) ? wp_get_abilities() : array();

// Bucket Premium Addons abilities by their category slug. Other plugins' abilities
// live in the same registry, so scope to our prefix.
$pa_abilities_by_cat = array();

foreach ( $pa_abilities as $pa_ability ) {

	if ( 0 !== strpos( $pa_ability->get_name(), $pa_ability_prefix ) ) {
		continue;
	}

	$pa_abilities_by_cat[ $pa_ability->get_category() ][] = $pa_ability;
}
?>

<div class="pa-section-content">
	<div class="pa-mcp-config">

		<h2 class="pa-mcp-step-heading">
			<?php esc_html_e( 'AI Abilities', 'premium-addons-for-elementor' ); ?>
		</h2>

		<p class="pa-mcp-step-desc">
			<?php esc_html_e( 'Premium Addons exposes a set of abilities that AI agents can call through the MCP server — each one a typed, permission-gated action over your site.', 'premium-addons-for-elementor' ); ?>
		</p>

		<?php if ( empty( $pa_abilities_by_cat ) ) : ?>

			<p class="pa-mcp-step-desc">
				<?php esc_html_e( 'No abilities are available yet. As abilities are added, each will be listed here with a description of what it does and the permission it requires.', 'premium-addons-for-elementor' ); ?>
			</p>

		<?php else : ?>

			<?php $pa_cat_open = true; // First category with abilities starts expanded. ?>

			<?php foreach ( $pa_categories as $pa_category ) : ?>

				<?php
				$pa_cat_slug = $pa_category->get_slug();

				// Skip categories that hold no Premium Addons abilities.
				if ( empty( $pa_abilities_by_cat[ $pa_cat_slug ] ) ) {
					continue;
				}

				$pa_cat_desc  = $pa_category->get_description();
				$pa_panel_id  = 'pa-ability-cat-' . $pa_cat_slug;
				$pa_expanded  = $pa_cat_open ? 'true' : 'false';
				$pa_cat_open  = false;
				?>

				<div class="pa-mcp-ability-cat">

					<h3 class="pa-mcp-ability-cat-title">
						<button type="button" class="pa-mcp-ability-cat-toggle" aria-expanded="<?php echo esc_attr( $pa_expanded ); ?>" aria-controls="<?php echo esc_attr( $pa_panel_id ); ?>">
							<span class="pa-mcp-ability-cat-label"><?php echo esc_html( $pa_category->get_label() ); ?></span>
							<span class="pa-mcp-ability-cat-icon" aria-hidden="true"></span>
						</button>
					</h3>

					<div id="<?php echo esc_attr( $pa_panel_id ); ?>" class="pa-mcp-ability-cat-body"<?php echo 'true' === $pa_expanded ? '' : ' hidden'; ?>>

						<?php if ( '' !== $pa_cat_desc ) : ?>
							<p class="pa-mcp-ability-cat-desc"><?php echo esc_html( $pa_cat_desc ); ?></p>
						<?php endif; ?>

						<ul class="pa-mcp-ability-list">

							<?php foreach ( $pa_abilities_by_cat[ $pa_cat_slug ] as $pa_ability ) : ?>

								<?php
								$pa_annotations = $pa_ability->get_meta_item( 'annotations', array() );
								$pa_readonly    = ! empty( $pa_annotations['readonly'] );
								?>

								<li class="pa-mcp-ability">
									<div class="pa-mcp-ability-head">
										<span class="pa-mcp-ability-name"><?php echo esc_html( $pa_ability->get_label() ); ?></span>
										<?php if ( $pa_readonly ) : ?>
											<span class="pa-mcp-ability-badge"><?php esc_html_e( 'Read-only', 'premium-addons-for-elementor' ); ?></span>
										<?php endif; ?>
									</div>
									<p class="pa-mcp-ability-desc"><?php echo esc_html( $pa_ability->get_description() ); ?></p>
								</li>

							<?php endforeach; ?>

						</ul>

					</div>

				</div>

			<?php endforeach; ?>

		<?php endif; ?>

	</div>
</div>
