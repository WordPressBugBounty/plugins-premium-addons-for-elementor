<?php
/**
 * AI Abilities tab.
 *
 * Lists and controls the Premium Addons abilities exposed to AI agents.
 */

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Abilities\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$abilities_list       = Bootstrap::get_instance()->get_abilities_catalog();
$abilities_categories = Bootstrap::get_categories();

$abilities_settings = Admin_Helper::get_ai_abilities_settings();
$disabled_abilities = array_fill_keys( $abilities_settings['disabled_abilities'], true );
$abilities_by_cat   = array();

foreach ( $abilities_list as $ability ) {
	$abilities_by_cat[ $ability['category'] ][] = $ability;
}

?>

<div class="pa-section-content">
	<div class="pa-mcp-config">

		<h2 class="pa-mcp-step-heading">
			<?php esc_html_e( 'AI Abilities', 'premium-addons-for-elementor' ); ?>
		</h2>

		<p class="pa-mcp-step-desc">
			<?php esc_html_e( 'Choose which Premium Addons abilities AI agents can call through REST and the MCP server.', 'premium-addons-for-elementor' ); ?>
		</p>

		<div class="pa-ai-abilities-status" role="status"></div>

			<?php $cat_open = true; ?>

			<?php foreach ( $abilities_categories as $cat_slug => $category ) : ?>

				<?php
				if ( empty( $abilities_by_cat[ $cat_slug ] ) ) {
					continue;
				}

				$category_abilities = $abilities_by_cat[ $cat_slug ];
				$enabled_count      = 0;

				foreach ( $category_abilities as $ability ) {
					if ( ! isset( $disabled_abilities[ $ability['full_name'] ] ) ) {
						++$enabled_count;
					}
				}

				$all_enabled     = count( $category_abilities ) === $enabled_count;
				$some_enabled    = 0 < $enabled_count && ! $all_enabled;
				$total_count     = count( $category_abilities );

				if ( $all_enabled ) {
					$count_state = 'is-all';
				} elseif ( 0 === $enabled_count ) {
					$count_state = 'is-none';
				} else {
					$count_state = 'is-some';
				}

				$panel_id        = 'pa-ability-cat-' . $cat_slug;
				$expanded        = $cat_open ? 'true' : 'false';
				$cat_open        = false;
				$category_toggle = sprintf(
					/* translators: %s: ability category label. */
					__( 'Enable all %s abilities', 'premium-addons-for-elementor' ),
					$category['label']
				);
				?>

				<div class="pa-mcp-ability-cat">

					<div class="pa-mcp-ability-cat-header">

						<div class="pa-mcp-ability-cat-heading">
							<h3 class="pa-mcp-ability-cat-title">
								<button type="button" class="pa-mcp-ability-cat-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>"<?php echo '' !== $category['description'] ? ' aria-describedby="' . esc_attr( $panel_id . '-desc' ) . '"' : ''; ?>>
									<span class="pa-mcp-ability-cat-icon" aria-hidden="true"></span>
									<?php echo esc_html( $category['label'] ); ?>
								</button>
							</h3>

							<?php if ( '' !== $category['description'] ) : ?>
								<p id="<?php echo esc_attr( $panel_id . '-desc' ); ?>" class="pa-mcp-ability-cat-desc"><?php echo esc_html( $category['description'] ); ?></p>
							<?php endif; ?>
						</div>

						<span class="pa-mcp-ability-cat-count <?php echo esc_attr( $count_state ); ?>" data-cat="<?php echo esc_attr( $cat_slug ); ?>">
							<span aria-hidden="true">
								<?php
								printf(
									/* translators: 1: number of enabled abilities, 2: total number of abilities. */
									esc_html__( '%1$s/%2$s on', 'premium-addons-for-elementor' ),
									'<span class="pa-count-enabled">' . esc_html( $enabled_count ) . '</span>',
									esc_html( $total_count )
								);
								?>
							</span>
							<span class="screen-reader-text">
								<?php
								printf(
									/* translators: 1: number of enabled abilities, 2: total number of abilities. */
									esc_html( _n( '%1$s of %2$s ability enabled', '%1$s of %2$s abilities enabled', $total_count, 'premium-addons-for-elementor' ) ),
									'<span class="pa-count-enabled-sr">' . esc_html( $enabled_count ) . '</span>',
									esc_html( $total_count )
								);
								?>
							</span>
						</span>

						<label class="switch pa-ai-ability-cat-switch">
							<input type="checkbox" data-cat="<?php echo esc_attr( $cat_slug ); ?>" data-indeterminate="<?php echo esc_attr( $some_enabled ? '1' : '0' ); ?>" aria-label="<?php echo esc_attr( $category_toggle ); ?>" <?php checked( $all_enabled ); ?>>
							<span class="slider round pa-control"></span>
						</label>
					</div>

					<div id="<?php echo esc_attr( $panel_id ); ?>" class="pa-mcp-ability-cat-body"<?php echo 'true' === $expanded ? '' : ' hidden'; ?>>

						<ul class="pa-mcp-ability-list">

							<?php foreach ( $category_abilities as $ability ) : ?>

								<?php $enabled = ! isset( $disabled_abilities[ $ability['full_name'] ] ); ?>

								<li class="pa-mcp-ability">
									<label class="switch pa-ai-ability-switch">
										<input type="checkbox" data-ability="<?php echo esc_attr( $ability['full_name'] ); ?>" data-cat="<?php echo esc_attr( $cat_slug ); ?>" aria-label="<?php echo esc_attr( $ability['label'] ); ?>" <?php checked( $enabled ); ?>>
										<span class="slider round pa-control"></span>
									</label>

									<div class="pa-mcp-ability-content">
										<div class="pa-mcp-ability-head">
											<span class="pa-mcp-ability-name"><?php echo esc_html( $ability['label'] ); ?></span>
											<?php if ( $ability['readonly'] ) : ?>
												<span class="pa-mcp-ability-badge"><?php esc_html_e( 'Read-only', 'premium-addons-for-elementor' ); ?></span>
											<?php endif; ?>
										</div>
										<p class="pa-mcp-ability-desc"><?php echo esc_html( $ability['description'] ); ?></p>
									</div>
								</li>

							<?php endforeach; ?>

						</ul>

					</div>

				</div>

			<?php endforeach; ?>

	</div>
</div>
