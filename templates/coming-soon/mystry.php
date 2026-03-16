<?php
/**
 * Template: Coming Soon – Mystry
 * Colorful gradient layout with optional button (no form).
 *
 * Variables available: $ifelsepages_s (settings array)
 *
 * @package IfElsePages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ifelsepages_meta_title = ! empty( $ifelsepages_s['meta_title'] ) ? $ifelsepages_s['meta_title'] : $ifelsepages_s['title'];
$ifelsepages_meta_desc  = ! empty( $ifelsepages_s['meta_description'] ) ? $ifelsepages_s['meta_description'] : '';

// Button from template settings.
$ifelsepages_button_text    = ! empty( $ifelsepages_s['template_settings']['mystry']['button_text'] ) ? $ifelsepages_s['template_settings']['mystry']['button_text'] : 'Contact Us';
$ifelsepages_button_url     = ! empty( $ifelsepages_s['template_settings']['mystry']['button_url'] ) ? $ifelsepages_s['template_settings']['mystry']['button_url'] : '';
$ifelsepages_button_new_tab = ! empty( $ifelsepages_s['template_settings']['mystry']['button_new_tab'] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $ifelsepages_meta_title ); ?></title>
<?php if ( $ifelsepages_meta_desc ) : ?>
<meta name="description" content="<?php echo esc_attr( $ifelsepages_meta_desc ); ?>">
<?php endif; ?>
<meta name="robots" content="noindex, nofollow">
<?php wp_head(); ?>
</head>
<body class="ifelsepages-body ifelsepages-mystry-body">

<div class="ifelsepages-mystry-wrap">

	<!-- Decorative blobs -->
	<div class="ifelsepages-mystry-blob ifelsepages-mystry-blob-1" aria-hidden="true"></div>
	<div class="ifelsepages-mystry-blob ifelsepages-mystry-blob-2" aria-hidden="true"></div>

	<div class="ifelsepages-mystry-content">

		<?php if ( ! empty( $ifelsepages_s['logo_url'] ) ) : ?>
			<div class="ifelsepages-logo ifelsepages-mystry-logo">
				<img src="<?php echo esc_url( $ifelsepages_s['logo_url'] ); ?>"
					 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			</div>
		<?php endif; ?>

		<div class="ifelsepages-mystry-eyebrow">
			<?php esc_html_e( '✦ Something new is coming ✦', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
		</div>

		<h1 class="ifelsepages-mystry-title">
			<?php echo esc_html( $ifelsepages_s['title'] ); ?>
		</h1>

		<?php if ( ! empty( $ifelsepages_s['description'] ) ) : ?>
			<div class="ifelsepages-mystry-desc">
				<?php echo wp_kses_post( $ifelsepages_s['description'] ); ?>
			</div>
		<?php endif; ?>

		<!-- Button (only if URL is not empty) -->
		<?php if ( $ifelsepages_button_url ) : ?>
			<div class="ifelsepages-mystry-button-wrap">
				<a href="<?php echo esc_url( $ifelsepages_button_url ); ?>"
				   class="ifelsepages-mystry-button"
				   <?php echo $ifelsepages_button_new_tab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $ifelsepages_button_text ); ?>
				</a>
			</div>
		<?php endif; ?>

	</div><!-- .ifelsepages-mystry-content -->

</div><!-- .ifelsepages-mystry-wrap -->

<?php if ( ! empty( $ifelsepages_s['footer_text'] ) ) : ?>
	<footer class="ifelsepages-mystry-footer">
		<?php echo esc_html( $ifelsepages_s['footer_text'] ); ?>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
