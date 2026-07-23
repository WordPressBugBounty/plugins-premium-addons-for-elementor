<?php
/**
 * Configure MCP Server — first accordion item of the AI Abilities tab.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Admin\Includes\MCP_Settings;
use PremiumAddons\Includes\Abilities\Connection_Log;

$mcp = MCP_Settings::get_instance();

// Process a pasted password first so the connection details can be shown inline.
$password_state = $mcp->maybe_handle_password_forms();
$used_password  = $password_state['existing_password'];
$used_error     = $password_state['existing_error'];

$pw_status         = MCP_Settings::app_passwords_status();
$profile_url       = admin_url( 'profile.php#application-passwords-section' );
$mcp_endpoint      = rest_url( 'premium-addons/mcp' );
$mcp_endpoint_host = MCP_Settings::get_endpoint_host( $mcp_endpoint );
$mcp_scheme        = MCP_Settings::endpoint_scheme( $mcp_endpoint );
$mcp_is_local      = MCP_Settings::is_local_host( $mcp_endpoint_host );

// Keep the form on this tab after submitting (tabs are routed by URL hash).
$form_action = esc_url( admin_url( 'admin.php?page=' . self::$page_slug . '#tab=ai-abilities' ) );

// A client this user already connected turns the setup steps into a reference
// they open on purpose, instead of a wall of instructions on every visit.
// $mcp_state and $is_configured come from ai-abilities.php, which includes this
// file into its own scope after reading the connection state once.
$state_ago   = $is_configured ? human_time_diff( $mcp_state['time'] ) : '';
$setup_steps = PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp-setup-steps.php';
?>

		<?php if ( $is_configured ) : ?>
			<div class="notice notice-success inline pa-mcp-connected-notice">
				<p>
					<strong>
						<?php
						if ( Connection_Log::STATE_ACTIVE === $mcp_state['state'] ) {
							printf(
								/* translators: 1: number of open sessions, 2: human-readable time difference. */
								esc_html( _n( 'Active now — %1$s session · last activity %2$s ago', 'Active now — %1$s sessions · last activity %2$s ago', $mcp_state['count'], 'premium-addons-for-elementor' ) ),
								esc_html( number_format_i18n( $mcp_state['count'] ) ),
								esc_html( $state_ago )
							);
						} else {
							printf(
								/* translators: %s: human-readable time difference. */
								esc_html__( 'Connected — last connected %s ago.', 'premium-addons-for-elementor' ),
								esc_html( $state_ago )
							);
						}
						?>
					</strong>
				</p>
				<p>
					<a href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Manage or revoke access', 'premium-addons-for-elementor' ); ?>
					</a>
				</p>
			</div>

			<?php // Stays open after a submission so the form's own result is not hidden. ?>
			<details class="pa-mcp-setup-fold"<?php echo null !== $used_password || null !== $used_error ? ' open' : ''; ?>>
				<summary><?php esc_html_e( 'Connect another client or reconnect', 'premium-addons-for-elementor' ); ?></summary>
				<?php include $setup_steps; ?>
			</details>
		<?php else : ?>
			<?php include $setup_steps; ?>
		<?php endif; ?>

		<div class="pa-mcp-endpoint">
			<div class="pa-mcp-endpoint-field">
				<label class="pa-mcp-field-label" for="pa-mcp-endpoint-url">
					<?php esc_html_e( 'MCP endpoint', 'premium-addons-for-elementor' ); ?>
				</label>
				<input type="text" id="pa-mcp-endpoint-url" class="regular-text" value="<?php echo esc_attr( $mcp_endpoint ); ?>" readonly>
			</div>
			<button type="button" class="button pa-mcp-copy" data-pa-mcp-copy="pa-mcp-endpoint-url" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>">
				<?php esc_html_e( 'Copy endpoint', 'premium-addons-for-elementor' ); ?>
			</button>
		</div>

		<?php if ( MCP_Settings::looks_like_production( $mcp_endpoint ) ) : ?>
			<div class="pa-mcp-advisory notice notice-warning inline">
				<p><?php esc_html_e( 'This endpoint appears to be a production site. The application password acts as your administrator account, so use a dedicated password and revoke it if it is exposed.', 'premium-addons-for-elementor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( 'http' === $mcp_scheme ) : ?>
			<div class="pa-mcp-advisory pa-mcp-http-warning notice notice-error inline">
				<p><?php esc_html_e( 'Basic credentials travel in clear over HTTP. Use an HTTPS endpoint before connecting an AI client.', 'premium-addons-for-elementor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php
		// "Connect Your AI Client" — shown only right after a password is pasted,
		// never on a normal page load, since the connection details embed the secret.
		$connect_password = $used_password;

		if ( null !== $connect_password ) :
			$mcp_username = wp_get_current_user()->user_login;
			$configs      = $mcp->build_client_configs( $mcp_endpoint, $mcp_username, $connect_password );
			?>

			<hr class="pa-mcp-divider">

			<div class="pa-mcp-connect">

				<h4 class="pa-mcp-step-heading">
					<span class="pa-mcp-step-badge">2</span>
					<?php esc_html_e( 'Connect Your AI Client', 'premium-addons-for-elementor' ); ?>
				</h4>

				<p class="pa-mcp-step-desc">
					<?php esc_html_e( 'Pick your AI client and copy its configuration. Every client also includes a plain-English prompt you can give to its agent.', 'premium-addons-for-elementor' ); ?>
				</p>

				<div class="pa-mcp-alias-field">
					<label class="pa-mcp-field-label" for="pa-mcp-alias">
						<?php esc_html_e( 'Client alias', 'premium-addons-for-elementor' ); ?>
						<span><?php esc_html_e( '(local name in your config)', 'premium-addons-for-elementor' ); ?></span>
					</label>
					<input type="text" id="pa-mcp-alias" class="regular-text" pattern="[A-Za-z0-9_-]+" value="<?php echo esc_attr( MCP_Settings::DEFAULT_SERVER_NAME ); ?>">
					<p class="description"><?php esc_html_e( 'This only names the server inside your client configuration; the Premium Addons server ID is fixed.', 'premium-addons-for-elementor' ); ?></p>
				</div>

				<div class="pa-mcp-clients-nav">
					<?php
					$is_first = true;

					foreach ( $configs as $client_key => $config ) :
						?>
						<button type="button" class="pa-mcp-client-tab<?php echo esc_attr( $is_first ? ' is-active' : '' ); ?>" data-pa-mcp-panel="pa-mcp-panel-<?php echo esc_attr( $client_key ); ?>">
							<?php echo esc_html( (string) $config['label'] ); ?>
						</button>
						<?php
						$is_first = false;
					endforeach;
					?>
				</div>

				<div class="pa-mcp-client-panels">
					<?php
					$is_first = true;

					foreach ( $configs as $client_key => $config ) :
						?>
						<div class="pa-mcp-client-panel" id="pa-mcp-panel-<?php echo esc_attr( $client_key ); ?>"<?php echo esc_attr( $is_first ? '' : ' hidden' ); ?>>
							<?php if ( null !== $config['code'] ) : ?>
								<p class="pa-mcp-hint"><?php echo esc_html( (string) $config['hint'] ); ?></p>

								<?php
								$parts = explode( MCP_Settings::NAME_TOKEN, (string) $config['code'] );
								// phpcs:disable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent -- inline PHP tags keep literal whitespace out of the rendered <pre>.
								?>
								<pre class="pa-mcp-connect-prompt" id="pa-mcp-code-<?php echo esc_attr( $client_key ); ?>"><?php
									echo implode(
										'<span class="pa-mcp-alias">' . esc_html( MCP_Settings::DEFAULT_SERVER_NAME ) . '</span>',
										array_map( 'esc_html', $parts )
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
								?></pre>
								<?php // phpcs:enable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent ?>

								<p>
									<button type="button" class="button button-primary pa-mcp-copy" data-pa-mcp-copy="pa-mcp-code-<?php echo esc_attr( $client_key ); ?>" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>">
										<?php esc_html_e( 'Copy configuration', 'premium-addons-for-elementor' ); ?>
									</button>
								</p>

								<?php if ( null !== $config['deeplink'] ) : ?>
									<p><a class="button pa-mcp-deeplink" href="<?php echo esc_url( (string) $config['deeplink'], array( 'cursor' ) ); ?>"><?php esc_html_e( 'Install in Cursor', 'premium-addons-for-elementor' ); ?></a></p>
									<p class="description pa-mcp-hint"><?php esc_html_e( 'This installs as premium-addons; rename it later in mcp.json if needed.', 'premium-addons-for-elementor' ); ?></p>
								<?php endif; ?>

								<?php if ( 'bridge' === $config['shape'] && $mcp_is_local && 'https' === $mcp_scheme ) : ?>
									<div class="pa-mcp-advisory pa-mcp-tls-advisory">
										<pre class="pa-mcp-tls-note" id="pa-mcp-tls-note-<?php echo esc_attr( $client_key ); ?>"><?php esc_html_e( 'If your client rejects the certificate, trust your local CA (LocalWP/mkcert). As a last resort, you can set NODE_TLS_REJECT_UNAUTHORIZED=0 for the bridge; understand that this disables certificate checks.', 'premium-addons-for-elementor' ); ?></pre>
										<button type="button" class="button pa-mcp-copy" data-pa-mcp-copy="pa-mcp-tls-note-<?php echo esc_attr( $client_key ); ?>" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>"><?php esc_html_e( 'Copy TLS note', 'premium-addons-for-elementor' ); ?></button>
									</div>
								<?php endif; ?>

								<details class="pa-mcp-agent-prompt">
									<summary><?php esc_html_e( 'Ask your agent to configure it', 'premium-addons-for-elementor' ); ?></summary>
									<?php
									$prompt_parts = explode( MCP_Settings::NAME_TOKEN, (string) $config['prompt'] );
									// phpcs:disable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent -- inline PHP tags keep literal whitespace out of the rendered <pre>.
									?>
									<pre class="pa-mcp-connect-prompt" id="pa-mcp-prompt-<?php echo esc_attr( $client_key ); ?>"><?php
										echo implode(
											'<span class="pa-mcp-alias">' . esc_html( MCP_Settings::DEFAULT_SERVER_NAME ) . '</span>',
											array_map( 'esc_html', $prompt_parts )
										); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
									?></pre>
									<?php // phpcs:enable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent ?>
									<button type="button" class="button pa-mcp-copy" data-pa-mcp-copy="pa-mcp-prompt-<?php echo esc_attr( $client_key ); ?>" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>"><?php esc_html_e( 'Copy agent prompt', 'premium-addons-for-elementor' ); ?></button>
								</details>
							<?php else : ?>
								<?php
								$prompt_parts = explode( MCP_Settings::NAME_TOKEN, (string) $config['prompt'] );
								// phpcs:disable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent -- inline PHP tags keep literal whitespace out of the rendered <pre>.
								?>
								<pre class="pa-mcp-connect-prompt" id="pa-mcp-prompt-<?php echo esc_attr( $client_key ); ?>"><?php
									echo implode(
										'<span class="pa-mcp-alias">' . esc_html( MCP_Settings::DEFAULT_SERVER_NAME ) . '</span>',
										array_map( 'esc_html', $prompt_parts )
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above.
								?></pre>
								<?php // phpcs:enable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent ?>
								<button type="button" class="button button-primary pa-mcp-copy" data-pa-mcp-copy="pa-mcp-prompt-<?php echo esc_attr( $client_key ); ?>" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>"><?php esc_html_e( 'Copy agent prompt', 'premium-addons-for-elementor' ); ?></button>
							<?php endif; ?>
						</div>
						<?php
						$is_first = false;
					endforeach;
					?>
				</div>

				<p class="description pa-mcp-connect-note">
					<?php esc_html_e( 'These connection details contain your application password. Treat them like a password: the config file stores the credential, and anyone with it can act on your site as you. If it is exposed, go to Users → Profile and revoke the application password.', 'premium-addons-for-elementor' ); ?>
				</p>

			</div>
		<?php endif; ?>