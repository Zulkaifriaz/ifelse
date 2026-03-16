<?php
/**
 * Template: Maintenance – Warning
 * Neutral technical style. Sends 503 header. No countdown.
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

// Retry time display.
$ifelsepages_show_retry = ! empty( $ifelsepages_s['template_settings']['warning']['show_retry_time'] );
$ifelsepages_retry_hrs  = ! empty( $ifelsepages_s['template_settings']['warning']['retry_hours'] )
	? absint( $ifelsepages_s['template_settings']['warning']['retry_hours'] )
	: 1;
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
<body class="ifelsepages-body ifelsepages-warning-body">

<div class="ifelsepages-warning-wrap">

	<div class="ifelsepages-warning-card">

		<!-- Icon -->
		<div class="ifelsepages-warning-icon" aria-hidden="true">
			<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M28 4L52 48H4L28 4Z" stroke="#E8A020" stroke-width="3" stroke-linejoin="round" fill="rgba(232,160,32,0.08)"/>
				<rect x="26" y="22" width="4" height="14" rx="2" fill="#E8A020"/>
				<circle cx="28" cy="41" r="2.5" fill="#E8A020"/>
			</svg>
		</div>

		<?php if ( ! empty( $ifelsepages_s['logo_url'] ) ) : ?>
			<div class="ifelsepages-logo ifelsepages-warning-logo">
				<img src="<?php echo esc_url( $ifelsepages_s['logo_url'] ); ?>"
					 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			</div>
		<?php endif; ?>

		<h1 class="ifelsepages-warning-title">
			<?php echo esc_html( $ifelsepages_s['title'] ); ?>
		</h1>

		<?php if ( ! empty( $ifelsepages_s['description'] ) ) : ?>
			<div class="ifelsepages-warning-desc">
				<?php echo wp_kses_post( $ifelsepages_s['description'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $ifelsepages_show_retry && $ifelsepages_retry_hrs > 0 ) : ?>
			<div class="ifelsepages-warning-retry">
				<?php
				printf(
					/* translators: %d: number of hours */
					esc_html( _n(
						'We expect to be back in approximately %d hour.',
						'We expect to be back in approximately %d hours.',
						$ifelsepages_retry_hrs,
						'ifelse-pages-coming-soon-and-maintenance-mode'
					) ),
					esc_html( $ifelsepages_retry_hrs )
				);
				?>
			</div>
		<?php endif; ?>

		<div class="ifelsepages-warning-meta">
			<span class="ifelsepages-warning-code">503</span>
			<span class="ifelsepages-warning-sep">–</span>
			<span><?php esc_html_e( 'Service temporarily unavailable', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
		</div>

	</div><!-- .ifelsepages-warning-card -->

</div><!-- .ifelsepages-warning-wrap -->

<?php if ( ! empty( $ifelsepages_s['footer_text'] ) ) : ?>
	<footer class="ifelsepages-warning-footer">
		<?php echo esc_html( $ifelsepages_s['footer_text'] ); ?>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
