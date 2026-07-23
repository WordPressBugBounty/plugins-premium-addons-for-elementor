<?php
/**
 * AI Abilities & MCP Config tab.
 *
 */

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Abilities\Bootstrap;
use PremiumAddons\Includes\Abilities\Connection_Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled_elements = self::get_enabled_elements();
$is_enabled       = ! empty( $enabled_elements['premium-ai-abilities'] );

// The Abilities API ships with WordPress 6.9. Without it the switcher is locked
// (same lock styling as pro features) and a bold note prompts updating WordPress.
$abilities_ready = function_exists( 'wp_register_ability' );

$ai_feature        = wp_list_filter( self::get_elements_list()['cat-13']['elements'], array( 'key' => 'premium-ai-abilities' ) );
$ai_feature        = reset( $ai_feature );
$ai_status         = $abilities_ready ? checked( 1, $enabled_elements['premium-ai-abilities'], false ) : 'disabled';
$ai_switcher_class = ( $abilities_ready ? '' : 'pa-wp-ver-slider ' ) . 'slider round pa-control';

if ( $abilities_ready ) {

	$abilities_list       = Bootstrap::get_instance()->get_abilities_catalog();
	$abilities_categories = Bootstrap::get_categories();

	$abilities_settings = Admin_Helper::get_ai_abilities_settings();
	$disabled_abilities = array_fill_keys( $abilities_settings['disabled_abilities'], true );
	$abilities_by_cat   = array();

	foreach ( $abilities_list as $ability ) {
		$abilities_by_cat[ $ability['category'] ][] = $ability;
	}

	// Once a client has actually connected, the step numbering is dropped: the tab
	// stops reading like a wizard the user must walk through again on every visit.
	// Read once here — mcp-config.php is included below into this same scope and
	// reuses both, so the lookup is not repeated on the way down.
	$mcp_state     = Connection_Log::get_state();
	$is_configured = Connection_Log::STATE_NONE !== $mcp_state['state'];

	$pills = array(
		Connection_Log::STATE_ACTIVE    => array(
			'class' => 'is-active',
			'label' => __( 'Active now', 'premium-addons-for-elementor' ),
		),
		Connection_Log::STATE_CONNECTED => array(
			'class' => 'is-connected',
			'label' => __( 'Connected', 'premium-addons-for-elementor' ),
		),
		Connection_Log::STATE_NONE      => array(
			'class' => 'is-idle',
			'label' => __( 'Not connected', 'premium-addons-for-elementor' ),
		),
	);

	$pill = $pills[ $mcp_state['state'] ];
}

?>

<div class="pa-section-content">

	<form action="" method="POST" id="pa-ai-settings" name="pa-ai-settings" class="pa-settings-form">
		<div class="pa-section-outer-wrap pa-ai-enable-card">
			<div class="pa-section-info-wrap">
				<div class="pa-section-info">
					<h4><?php esc_html_e( 'Enable AI Abilities', 'premium-addons-for-elementor' ); ?></h4>
					<p>
						<?php echo esc_html( $ai_feature['desc'] ); ?>
						<?php if ( ! $abilities_ready ) : ?>
							<strong><?php esc_html_e( 'Requires WordPress v6.9+', 'premium-addons-for-elementor' ); ?></strong>
						<?php endif; ?>
					</p>
				</div>

				<div class="pa-section-info-cta">
					<label class="switch">
						<input type="checkbox" id="premium-ai-abilities" pa-element="feature" name="premium-ai-abilities" <?php echo esc_attr( $ai_status ); ?>>
						<span class="<?php echo esc_attr( $ai_switcher_class ); ?>"></span>
					</label>
				</div>
			</div>
		</div>
	</form>

	<?php if ( $abilities_ready ) : ?>

		<?php // Kept outside the accordion so save announcements are not trapped in a collapsed panel. ?>
		<div class="pa-ai-abilities-status" role="status"></div>

		<div class="pa-ai-accordion pa-mcp-config"<?php echo $is_enabled ? '' : ' hidden'; ?>>

			<div class="pa-ai-accordion-item">
				<h3 class="pa-ai-accordion-title">
					<button type="button" class="pa-ai-accordion-toggle" aria-expanded="false" aria-controls="pa-ai-panel-mcp">
						<span class="pa-ai-accordion-icon" aria-hidden="true"></span>
						<?php
						echo $is_configured
							? esc_html__( 'MCP Server', 'premium-addons-for-elementor' )
							: esc_html__( 'Step #1 - Configure MCP Server', 'premium-addons-for-elementor' );
						?>
					</button>

					<?php // Kept outside the button: the connection state is a status, not part of the toggle's name. ?>
					<span class="pa-mcp-status-pill <?php echo esc_attr( $pill['class'] ); ?>"><?php echo esc_html( $pill['label'] ); ?></span>
				</h3>

				<div id="pa-ai-panel-mcp" class="pa-ai-accordion-body" hidden>
					<?php include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp-config.php'; ?>
				</div>
			</div>

			<div class="pa-ai-accordion-item">
				<h3 class="pa-ai-accordion-title">
					<button type="button" class="pa-ai-accordion-toggle" aria-expanded="false" aria-controls="pa-ai-panel-abilities">
						<span class="pa-ai-accordion-icon" aria-hidden="true"></span>
						<?php
						echo $is_configured
							? esc_html__( 'Manage AI Abilities', 'premium-addons-for-elementor' )
							: esc_html__( 'Step #2 - Manage AI Abilities', 'premium-addons-for-elementor' );
						?>
					</button>
				</h3>

				<div id="pa-ai-panel-abilities" class="pa-ai-accordion-body" hidden>

					<p class="pa-mcp-step-desc">
						<?php esc_html_e( 'Choose which Premium Addons abilities AI agents can call through REST and the MCP server.', 'premium-addons-for-elementor' ); ?>
					</p>

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

						$all_enabled  = count( $category_abilities ) === $enabled_count;
						$some_enabled = 0 < $enabled_count && ! $all_enabled;
						$total_count  = count( $category_abilities );

						if ( $all_enabled ) {
							$count_state = 'is-all';
						} elseif ( 0 === $enabled_count ) {
							$count_state = 'is-none';
						} else {
							$count_state = 'is-some';
						}

						$panel_id        = 'pa-ability-cat-' . $cat_slug;
						$category_toggle = sprintf(
							/* translators: %s: ability category label. */
							__( 'Enable all %s abilities', 'premium-addons-for-elementor' ),
							$category['label']
						);
						?>

						<div class="pa-mcp-ability-cat">

							<div class="pa-mcp-ability-cat-header">

								<div class="pa-mcp-ability-cat-heading">
									<h4 class="pa-mcp-ability-cat-title">
										<button type="button" class="pa-mcp-ability-cat-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>"<?php echo '' !== $category['description'] ? ' aria-describedby="' . esc_attr( $panel_id . '-desc' ) . '"' : ''; ?>>
											<span class="pa-mcp-ability-cat-icon" aria-hidden="true"></span>
											<?php echo esc_html( $category['label'] ); ?>
										</button>
									</h4>

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

							<div id="<?php echo esc_attr( $panel_id ); ?>" class="pa-mcp-ability-cat-body" hidden>

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

		</div>

	<?php endif; ?>

</div>
