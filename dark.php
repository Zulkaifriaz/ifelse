<?php
/**
 * Template: Coming Soon – Dark
 * Dark background, prominent countdown, centered logo.
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
$ifelsepages_show_countdown = (
	! empty( $ifelsepages_s['template_settings']['dark']['countdown_enable'] ) &&
	! empty( $ifelsepages_s['template_settings']['dark']['countdown_date'] ) &&
	ifelsepages_local_to_timestamp( $ifelsepages_s['template_settings']['dark']['countdown_date'] ) > time()
);
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
<body class="ifelsepages-body ifelsepages-dark-body">

<div class="ifelsepages-dark-stage">

	<!-- Animated background particles layer -->
	<div class="ifelsepages-dark-particles" aria-hidden="true">
		<span></span><span></span><span></span><span></span>
		<span></span><span></span><span></span><span></span>
	</div>

	<div class="ifelsepages-dark-content">

		<?php if ( ! empty( $ifelsepages_s['logo_url'] ) ) : ?>
			<div class="ifelsepages-logo">
				<img src="<?php echo esc_url( $ifelsepages_s['logo_url'] ); ?>"
					 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			</div>
		<?php endif; ?>

		<h1 class="ifelsepages-dark-title">
			<?php echo esc_html( $ifelsepages_s['title'] ); ?>
		</h1>

		<?php if ( ! empty( $ifelsepages_s['description'] ) ) : ?>
			<div class="ifelsepages-dark-desc">
				<?php echo wp_kses_post( $ifelsepages_s['description'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $ifelsepages_show_countdown ) : ?>
			<div class="ifelsepages-countdown" id="ifelsepages-countdown" aria-live="polite">
				<div class="ifelsepages-countdown-unit">
					<span class="ifelsepages-countdown-num" id="iep-days">00</span>
					<span class="ifelsepages-countdown-lbl"><?php esc_html_e( 'Days', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
				</div>
				<div class="ifelsepages-countdown-sep" aria-hidden="true">:</div>
				<div class="ifelsepages-countdown-unit">
					<span class="ifelsepages-countdown-num" id="iep-hours">00</span>
					<span class="ifelsepages-countdown-lbl"><?php esc_html_e( 'Hours', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
				</div>
				<div class="ifelsepages-countdown-sep" aria-hidden="true">:</div>
				<div class="ifelsepages-countdown-unit">
					<span class="ifelsepages-countdown-num" id="iep-minutes">00</span>
					<span class="ifelsepages-countdown-lbl"><?php esc_html_e( 'Minutes', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
				</div>
				<div class="ifelsepages-countdown-sep" aria-hidden="true">:</div>
				<div class="ifelsepages-countdown-unit">
					<span class="ifelsepages-countdown-num" id="iep-seconds">00</span>
					<span class="ifelsepages-countdown-lbl"><?php esc_html_e( 'Seconds', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
				</div>
			</div>
		<?php endif; ?>

	</div><!-- .ifelsepages-dark-content -->

</div><!-- .ifelsepages-dark-stage -->

<?php if ( ! empty( $ifelsepages_s['footer_text'] ) ) : ?>
	<footer class="ifelsepages-dark-footer">
		<?php echo esc_html( $ifelsepages_s['footer_text'] ); ?>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
