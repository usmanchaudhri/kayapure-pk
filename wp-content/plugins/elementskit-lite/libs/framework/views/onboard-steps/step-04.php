<div class="ekit-onboard-main-header">
	<h1 class="ekit-onboard-main-header--title"><strong><?php echo esc_html__(
		'Watch how to use our top widgets.
Save 1 hour of your learning time.',
		'elementskit-lite'
	); ?></strong></h1>
</div>
<div class="ekit-onboard-tutorial">
	<div class="ekit-onboard-tutorial--btn">
		<a class="ekit-onboard-tutorial--link" data-video_id="VhBl3dHT5SY" href="#"><i class="icon icon-play1"></i></a>
	</div>

	<div class="ekti-admin-video-tutorial-popup">
			<div class="ekti-admin-video-tutorial-iframe"></div>
	</div>
</div>

<div class="ekit-onboard-tut-term">
	<label class="ekit-onboard-tut-term--label">
		<input type="hidden" name="settings[ekit_user_consent_for_banner]" value="no">
		<input type="checkbox" class="ekit-onboard-tut-term--input" name="settings[ekit_user_consent_for_banner]" value="yes">
		<?php echo esc_html__( 'Share diagnostic data and plugin usage details.', 'elementskit-lite' ); ?>
	</label>
	<p class="ekit-onboard-tut-term--helptext active">
		<?php echo esc_html__( 'We collect limited non-sensitive diagnostic data and plugin usage information to improve ElementsKit compatibility with popular plugins and themes. By sharing this information, you may also receive special discounts as a thank-you gesture.', 'elementskit-lite' ); ?>
	</p>
	<p class="ekit-onboard-tut-term--helptext active">
		<?php echo esc_html__( 'We respect your privacy and won’t send unsolicited promotional emails.', 'elementskit-lite' ); ?>
	</p>
	<a class="ekit-onboard-tut-term--link" href="https://wpmet.com/privacy-policy/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'What information is collected?', 'elementskit-lite' ); ?></a>
</div>

<div class="ekit-onboard-pagination">
	<a class="ekit-onboard-btn ekit-onboard-pagi-btn prev" href="#"><i class="icon icon-left-arrow"></i><?php echo esc_html__( 'Back', 'elementskit-lite' ); ?></a>
	<a class="ekit-onboard-btn ekit-onboard-pagi-btn next continue" href="#"><?php echo esc_html__( 'Skip', 'elementskit-lite' ); ?></a>
	<a
	class="ekit-onboard-btn ekit-onboard-pagi-btn next ekit-onboard-agree-next"
	href="#"
	aria-describedby="ekit-onboard-agree-next-tooltip"
	onclick="document.querySelectorAll('.ekit-onboard-tut-term--input').forEach(cb => cb.checked = true);"
	>
		<?php echo esc_html__( 'Agree & Next', 'elementskit-lite' ); ?>
		<span class="ekit-onboard-tooltip" onclick="event.preventDefault(); event.stopPropagation();">
			<svg class="ekit-onboard-tooltip--icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
				<circle cx="8" cy="8" r="6.75" stroke="currentColor" stroke-width="1.5"/>
				<path d="M8 7.25V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				<circle cx="8" cy="5" r="0.9" fill="currentColor"/>
			</svg>
			<span class="ekit-onboard-tooltip--content" id="ekit-onboard-agree-next-tooltip" role="tooltip">
				<strong class="ekit-onboard-tooltip--title"><?php echo esc_html__( 'Share diagnostic data & continue', 'elementskit-lite' ); ?></strong>
				<?php echo esc_html__( 'Agree to share the diagnostic data and plugin usage details.', 'elementskit-lite' ); ?>
			</span>
		</span>
	</a>
</div>
<div class="ekit-onboard-shapes">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-07.png" alt="" class="shape-07">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-14.png" alt="" class="shape-14">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-15.png" alt="" class="shape-15">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-16.png" alt="" class="shape-16">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-17.png" alt="" class="shape-17">
</div>
