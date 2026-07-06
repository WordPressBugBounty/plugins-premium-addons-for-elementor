<?php
/**
 * MCP Configuration tab.
 *
 * Feeds an AI client the connection details for the Premium Addons MCP server.
 * The application password itself is created by the user on their WordPress
 * profile page (Users → Profile → Application Passwords); this tab only takes
 * the pasted value and builds the connect prompt. Rendered by
 * Admin_Helper::render_setting_tabs(), so `self` resolves to Admin_Helper here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Admin\Includes\MCP_Settings;

$mcp = MCP_Settings::get_instance();

// Process a pasted password first so the connect prompt can be shown inline.
$pw_state      = $mcp->maybe_handle_password_forms();
$used_password = $pw_state['existing_password'];
$used_error    = $pw_state['existing_error'];

$pw_status   = MCP_Settings::app_passwords_status();
$profile_url = admin_url( 'profile.php#application-passwords-section' );

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
		<?php endif; ?>

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
					<p><?php esc_html_e( 'Password accepted. Use it in your AI client connection details below.', 'premium-addons-for-elementor' ); ?></p>
				</div>
			<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'The password is used only to fill the connection details below and is never stored on this site.', 'premium-addons-for-elementor' ); ?>
			</p>
		</form>

		<?php
		// "Connect Your AI Client" — shown only right after a password is pasted,
		// never on a normal page load, since the connect prompt embeds the
		// one-time plaintext password.
		$connect_password = $used_password;

		if ( null !== $connect_password ) :

			$mcp_clients  = MCP_Settings::get_supported_clients();
			$mcp_endpoint = rest_url( 'premium-addons/mcp' );
			$mcp_username = wp_get_current_user()->user_login;
			$first_client = reset( $mcp_clients );

			// translators: 1: AI client name, 2: MCP endpoint URL, 3: WordPress username, 4: application password.
			$connect_prompt = __(
				'Connect %1$s to my WordPress site by adding the MCP server below, then list its tools to confirm the connection works:

- Name: premium-addons
- Transport: Streamable HTTP
- URL: %2$s
- Auth: HTTP Basic
- Username: %3$s
- Password: %4$s',
				'premium-addons-for-elementor'
			);
			?>

			<hr class="pa-mcp-divider">

			<div class="pa-mcp-connect">

				<h2 class="pa-mcp-step-heading">
					<span class="pa-mcp-step-badge">2</span>
					<?php esc_html_e( 'Connect Your AI Client', 'premium-addons-for-elementor' ); ?>
				</h2>

				<p class="pa-mcp-step-desc">
					<?php esc_html_e( 'Pick your AI client, copy the prompt, and paste it into the client. The agent adds the Premium Addons MCP server and confirms the connection — no manual config files needed.', 'premium-addons-for-elementor' ); ?>
				</p>

				<div class="pa-mcp-clients-nav" role="tablist" aria-label="<?php esc_attr_e( 'Supported AI clients', 'premium-addons-for-elementor' ); ?>">
					<?php
					$is_first = true;

					foreach ( $mcp_clients as $client_label ) :
						?>
						<button type="button" class="pa-mcp-client-tab<?php echo $is_first ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>" data-pa-mcp-client="<?php echo esc_attr( $client_label ); ?>">
							<?php echo esc_html( $client_label ); ?>
						</button>
						<?php
						$is_first = false;
					endforeach;
					?>
				</div>

				<?php // phpcs:disable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent -- inline PHP tags keep literal whitespace out of the rendered <pre>. ?>
				<pre class="pa-mcp-connect-prompt" id="pa-mcp-connect-prompt"><?php
					printf(
						$connect_prompt, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static translatable template; the four substituted values are escaped individually below.
						'<span class="pa-mcp-client-name">' . esc_html( $first_client ) . '</span>',
						esc_url( $mcp_endpoint ),
						esc_html( $mcp_username ),
						esc_html( $connect_password )
					);
				?></pre>
				<?php // phpcs:enable Squiz.PHP.EmbeddedPhp, Generic.WhiteSpace.ScopeIndent ?>

				<p>
					<button type="button" class="button button-primary pa-mcp-copy" data-pa-mcp-copy="pa-mcp-connect-prompt" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>">
						<?php esc_html_e( 'Copy connect prompt', 'premium-addons-for-elementor' ); ?>
					</button>
				</p>

				<p class="description pa-mcp-connect-note">
					<?php esc_html_e( 'This prompt contains your application password. Treat it like a password — anyone with it can act on your site as you.', 'premium-addons-for-elementor' ); ?>
				</p>

			</div>
		<?php endif; ?>

	</div>
</div>
