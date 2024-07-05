<?php
/**
 * The template for displaying the footer.
 *
 * Contains footer content and the closing of the
 * #main and #page div elements.
 *
 * @package	Total
 * @author Alexander Clarke
 * @copyright Copyright (c) 2014, Symple Workz LLC
 * @link http://www.wpexplorer.com
 * @since Total 1.0
 */
?>

			<?php
			// Main bottom hook
			wpex_hook_main_bottom(); ?>

		</div><!-- #main-content --><?php // main-content opens in header.php ?>
		
		<?php
		// Main after hook
		wpex_hook_main_after(); ?>
		
		<?php
		// Get footer unless disabled
		// See functions/footer-display.php
		if ( wpex_display_footer() ) { ?>
		
			<?php
			// Footer before hook
			// The callout is added to this hook by default
			wpex_hook_footer_before(); ?>
		
			<?php
			// Display footer Widgets if enabled
			if ( wpex_option( 'footer_widgets', '1' ) ) { ?>
				<footer id="footer" class="site-footer">
					<?php
					// Footer top hook
					wpex_hook_footer_top(); ?>
					<div id="footer-inner" class="container clr">
						<div id="footer-row" class="wpex-row clr">
							<?php
							// Footer innner hook
							// The widgets are added to this hook by default
							// See functions/hooks/hooks-default.php
							wpex_hook_footer_inner(); ?>
						</div><!-- .wpex-row -->
					</div><!-- #footer-widgets -->
					<?php
					// Footer bottom hook
					wpex_hook_footer_bottom(); ?>
				</footer><!-- #footer -->
			<?php } // End disable widgets check ?>
			
			<?php
			// Footer after hook
			// The footer bottom area is added to this hook by default
			wpex_hook_footer_after(); ?>
		
		<?php } // Disable footer check ?>

		<?php
		// Bottom wrap hook
		wpex_hook_wrap_bottom(); ?>

	</div><!-- #wrap -->

	<?php
	// After wrap hook
	wpex_hook_wrap_after(); ?>

<?php
// Important WordPress Hook - DO NOT DELETE!
wp_footer(); ?>

<!-- CUSTOM SCROLL TO TOP BUTTON -->
<a class="button-top">
    <span class="ticon ticon-chevron-up" aria-hidden="true"></span>
    <span class="screen-reader-text">Back To Top</span>
</a>

</body>
</html>
