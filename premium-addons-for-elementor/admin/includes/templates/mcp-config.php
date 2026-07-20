<?php
/**
 * MCP Configuration tab.
 *
 * Feeds an AI client the connection details for the Premium Addons MCP server.
 * The application password itself is created by the user on their WordPress
 * profile page (Users → Profile → Application Passwords); this tab only takes
 * the pasted value and builds one-time connection details. Rendered by
 * Admin_Helper::render_setting_tabs(), so `self` resolves to Admin_Helper here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Admin\Includes\MCP_Settings;

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
$form_action = esc_url( admin_url( 'admin.php?page=' . self::$page_slug . '#tab=mcp-config' ) );
?>

<div class="pa-section-content">
	<div class="pa-mcp-config">

		<h2 class="pa-mcp-step-heading">
			<span class="pa-mcp-step-badge">1</span>
			<?php esc_html_e( 'Create an Application Password', 'premium-addons-for-elementor' ); ?>
		</h2>

		<p class="pa-mcp-step-desc">
			<?php esc_html_e( 'Your AI client authenticates with a WordPress application password. Create one on your profile page, then paste it below to build the connection details.', 'premium-addons-for-elementor' ); ?>
		</p>

		<?php if ( ! $pw_status['available'] ) : ?>
			<div class="notice notice-error inline">
				<p><strong><?php echo esc_html( $pw_status['message'] ); ?></strong></p>
			</div>
		<?php else : ?>

			<ol class="pa-mcp-steps">
				<li>
					<?php
					printf(
						/* translators: 1: opening <a> tag linking to the profile page, 2: closing </a> tag. */
						esc_html__( 'Open your %1$sprofile page%2$s and scroll to the “Application Passwords” section.', 'premium-addons-for-elementor' ),
						'<a href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener noreferrer">', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL is escaped with esc_url().
						'</a>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Enter a name you will recognize (e.g. “Claude on laptop”) in the “New Application Password Name” field.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Click “Add New Application Password”.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Copy the generated password — WordPress shows it only once.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Come back here and paste it below.', 'premium-addons-for-elementor' ); ?></li>
			</ol>

			<form method="post" action="<?php echo $form_action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above. ?>">
				<?php wp_nonce_field( 'pa_mcp_use_existing_password' ); ?>
				<label class="pa-mcp-field-label" for="pa-mcp-existing-password">
					<?php esc_html_e( 'Paste the password value', 'premium-addons-for-elementor' ); ?>
				</label>
				<input type="text" id="pa-mcp-existing-password" name="pa_mcp_existing_password" class="regular-text" autocomplete="off" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
				<button type="submit" name="pa_mcp_use_existing_password" class="button button-primary">
					<?php esc_html_e( 'Use this password', 'premium-addons-for-elementor' ); ?>
				</button>
				<?php if ( null !== $used_error ) : ?>
					<div class="notice notice-error inline">
						<p><?php echo esc_html( $used_error->get_error_message() ); ?></p>
					</div>
				<?php elseif ( null !== $used_password ) : ?>
					<div class="notice notice-success inline">
						<p><?php esc_html_e( 'Connection details generated below.', 'premium-addons-for-elementor' ); ?></p>
					</div>
				<?php endif; ?>
				<p class="description">
					<?php esc_html_e( 'The password is used only to fill the connection details below and is never stored on this site.', 'premium-addons-for-elementor' ); ?>
				</p>
			</form>
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

				<h2 class="pa-mcp-step-heading">
					<span class="pa-mcp-step-badge">2</span>
					<?php esc_html_e( 'Connect Your AI Client', 'premium-addons-for-elementor' ); ?>
				</h2>

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

	</div>
</div>
