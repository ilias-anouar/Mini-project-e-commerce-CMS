<script type="text/template" id="tmpl-wc-avatax-sync-modal">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1>{{{data.title}}}</h1>
					<# if ( ! data.batch_enabled ) { #>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-avatax' ); ?></span>
					</button>
					<# } #>
				</header>
				<article>{{{data.body}}}</article>
				<footer>
					<div class="inner">
						<# if ( data.cancel ) { #>
						<button id="btn-cancel" class="button button-large button-secondary modal-close">{{{data.cancel}}}</button>
						<# } #>
						<# if ( data.action ) { #>
						<button id="btn-ok" class="button button-large button-primary {{{data.button_class}}}">{{{data.action}}}</button>
						<# } #>
						<# if ( ! data.cancel && ! data.action ) { #>
						<button id="btn-close" class="button button-large button-secondary modal-close"><?php esc_html_e( 'Close', 'woocommerce-avatax' ); ?></button>
						<# } #>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
