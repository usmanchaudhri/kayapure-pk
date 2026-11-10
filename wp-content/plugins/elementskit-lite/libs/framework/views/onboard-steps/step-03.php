<div class="ekit-onboard-main-header">
	<h1 class="ekit-onboard-main-header--title"><strong><?php echo esc_html__( 'Build A Complete WordPress Website', 'elementskit-lite' ); ?></strong></h1>
	<p class="ekit-onboard-main-header--description"><?php echo esc_html__( 'A fleet of plugins with all types of features you will need for WordPress.', 'elementskit-lite' ); ?></p>
</div>
<div class="ekit-onboard-plugin-list">
	<div class="attr-row">
		<?php
		$pluginStatus = \ElementsKit_Lite\Libs\Framework\Classes\Plugin_Status::instance();
		$plugins      = \ElementsKit_Lite\Libs\Framework\Attr::instance()->utils->get_option( 'settings', array() );

		$elementskit = $pluginStatus->get_status( 'elementskit-lite/elementskit-lite.php' );
		$getgenie = $pluginStatus->get_status( 'getgenie/getgenie.php' );
		$shopengine = $pluginStatus->get_status( 'shopengine/shopengine.php' );
		$metform = $pluginStatus->get_status( 'metform/metform.php' );
		$emailkit = $pluginStatus->get_status( 'emailkit/EmailKit.php' );
		$popupkit = $pluginStatus->get_status( 'popup-builder-block/popup-builder-block.php' );
		$gutenkit = $pluginStatus->get_status( 'gutenkit-blocks-addon/gutenkit-blocks-addon.php' );
		$dynamic_fields_engine = $pluginStatus->get_status( 'rox-dynamic-cpt-fields-engine/rox-dynamic-cpt-fields-engine.php' );
		$rox_appointment_booking = $pluginStatus->get_status( 'rox-appointment-booking/rox-appointment-booking.php' );
		$woocommerce = $pluginStatus->get_status( 'woocommerce/woocommerce.php' );
		$shopengine_pre_check = $woocommerce['status'] == 'activated' ? '' : '';
		?>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $shopengine['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>WooCommerce Builder</3>
					</div>
					<?php if($shopengine['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="shopengine/shopengine.php" name="our_plugins[]" <?php echo esc_attr($shopengine_pre_check); ?>>
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'The ultimate solution to build a complete WooCommerce site in Elementor.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $metform['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Form Builder</3>
					</div>
					<?php if($metform['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="metform/metform.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Most flexible form builder for Elementor with a wide range of features.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<!-- Gutenberg plugins -->
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $gutenkit['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Gutenberg Blocks</3>
					</div>
					<?php if($gutenkit['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="gutenkit-blocks-addon/gutenkit-blocks-addon.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Enhance block capability with page builder features & templates for Gutenberg.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $popupkit['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Popup Builder</3>
					</div>
					<?php if($popupkit['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="popup-builder-block/popup-builder-block.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Design popups that convert, right in your WordPress dashboard.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $emailkit['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Email Customizer</3>
					</div>
					<?php if($emailkit['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="emailkit/EmailKit.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Drag-and-drop email builder for WooCommerce & WordPress.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $getgenie['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>AI Content & SEO Tool</3>
					</div>
					<?php if($getgenie['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="getgenie/getgenie.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Find top keywords, create winning content & track SEO results with AI.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $dynamic_fields_engine['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Dynamic Field Engine</3>
					</div>
					<?php if($dynamic_fields_engine['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="rox-dynamic-cpt-fields-engine/rox-dynamic-cpt-fields-engine.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Build custom post types, fields, taxonomies, and dynamic layouts without coding.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
		<div class="attr-col-lg-3">
			<div class="ekit-onboard-single-plugin <?php echo $rox_appointment_booking['status'] == 'activated' ? 'activated' : ''; ?>">
				<label>
					<div class="ekit-onboard-single-plugin--header">
						<h3>Appointment Booking</3>
					</div>
					<?php if($rox_appointment_booking['status'] !== 'activated') : ?>
						<div class="ekit-onboard-single-plugin--checkbox-wrapper">
							<input type="checkbox" class="ekit-onboard-single-plugin--input" value="rox-appointment-booking/rox-appointment-booking.php" name="our_plugins[]">
						</div>
					<?php endif; ?>
					<p class="ekit-onboard-single-plugin--description">
						<?php echo esc_html__( 'Manage appointments, agents, payments, and calendars from one dashboard.', 'elementskit-lite' ); ?>
					</p>
				</label>
			</div>
		</div>
	</div>
</div>
<div class="ekit-onboard-pagination">
	<a class="ekit-onboard-btn ekit-onboard-pagi-btn prev" href="#">
		<i class="icon icon-left-arrow"></i>
		<?php echo esc_html__( 'Back', 'elementskit-lite' ); ?>
	</a>
	<a class="ekit-onboard-btn ekit-onboard-pagi-btn next continue" href="#">
		<?php echo esc_html__( 'Continue', 'elementskit-lite' ); ?>
	</a>
	<a
	class="ekit-onboard-btn ekit-onboard-pagi-btn next ekit-onboard-unlock-all"
	href="#"
	aria-describedby="ekit-onboard-unlock-all-tooltip"
	onclick="document.querySelectorAll('.ekit-onboard-plugin-list input[type=checkbox]').forEach(cb => cb.checked = true);"
	>
		<?php echo esc_html__( 'Unlock All & Continue', 'elementskit-lite' ); ?>
		<span class="ekit-onboard-tooltip" onclick="event.preventDefault(); event.stopPropagation();">
			<svg class="ekit-onboard-tooltip--icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">
				<circle cx="8" cy="8" r="6.75" stroke="currentColor" stroke-width="1.5"/>
				<path d="M8 7.25V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				<circle cx="8" cy="5" r="0.9" fill="currentColor"/>
			</svg>
			<span class="ekit-onboard-tooltip--content" id="ekit-onboard-unlock-all-tooltip" role="tooltip">
				<strong class="ekit-onboard-tooltip--title"><?php echo esc_html__( 'Unlock all plugins in one click', 'elementskit-lite' ); ?></strong>
				<?php echo esc_html__( 'Installs and activates all listed plugins that aren\'t already active on your site. Existing active plugins remain unchanged.', 'elementskit-lite' ); ?>
			</span>
		</span>
	</a>
</div>
<div class="ekit-onboard-shapes" style="display: none;">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-06.png" alt="" class="shape-06">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-10.png" alt="" class="shape-10">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-11.png" alt="" class="shape-11">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-12.png" alt="" class="shape-12">
	<img src="<?php echo esc_url(self::get_url()); ?>assets/images/onboard/shape-13.png" alt="" class="shape-13">
</div>
