<div class="meta-button-style">
	<div class="design-button" id="button-design">
		<table class="form-table">
			<p><?php echo esc_html__( 'This styling applies only to the shortcode buttons for this account.', 'wp-whatsapp' ); ?></p>
			<tbody>
				<tr>
					<th scope="row">
						<label for="label"><?php echo esc_html__( 'Button Label', 'wp-whatsapp' ); ?></label>
					</th>
					<td>
						<input type="text" id="label" name="label" value="<?php echo esc_attr( $buttonStyles['label'] ); ?>" placeholder="Need help? Chat via WhatsApp" class="widefat" autocomplete="off">
						<p class="description"><?php echo esc_html__( 'This text applies only on shortcode button. Leave empty to use the default label.', 'wp-whatsapp' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="type"><?php echo esc_html__( 'Button Style', 'wp-whatsapp' ); ?></label></th>
					<td>
						<div class="setting align">
							<div class="button-group button-large" data-setting="align">
								<button class="button btn-round <?php echo ( 'round' === $buttonStyles['type'] ? 'active' : '' ); ?>" value="round" type="button">
									<?php echo esc_html__( 'Round', 'wp-whatsapp' ); ?>
								</button>
								<button class="button btn-square <?php echo ( 'square' === $buttonStyles['type'] ? 'active' : '' ); ?>" value="square" type="button">
									<?php echo esc_html__( 'Square', 'wp-whatsapp' ); ?>
								</button>
							</div>
							<input name="btnType" id="btnType" class="hidden" value="<?php echo esc_attr( $buttonStyles['type'] ); ?>" />
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="backgroundColor"><?php echo esc_html__( 'Button Background Color', 'wp-whatsapp' ); ?></label></th>
					<td>
						<input type="text" id="backgroundColor" name="backgroundColor" value="<?php echo esc_attr( $buttonStyles['backgroundColor'] ); ?>" class="widget-button-color" data-default-color="#2DB742" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="textColor"><?php echo esc_html__( 'Button Text Color', 'wp-whatsapp' ); ?></label></th>
					<td>
						<input type="text" id="textColor" name="textColor" value="<?php echo esc_attr( $buttonStyles['textColor'] ); ?>" class="widget-button-color" data-default-color="#fff" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="preview-button">
		<p><?php echo esc_html__( 'Preview', 'wp-whatsapp' ); ?></p>
		<div id="wa-button"></div>
	</div>
</div>

<script>
	var buttonStyles = <?php echo json_encode( $buttonStyles ); ?>
</script>
<script type="text/template" id="button-preview">
	<div id="njt-wabutton">
		<a href="javascript:;" class="wa__stt_online wa__button <%= buttonStyles.buttonClass %>">
			<% if (_.isEmpty(buttonStyles.avatar)) { %>
				<div class="wa__btn_icon">
					<img src="<?php echo esc_url( NTA_WHATSAPP_PLUGIN_URL . 'assets/img/whatsapp_logo.svg' ); ?>" alt="img"/>
				</div>
			<% } else { %>
				<div class="wa__cs_img">
					<div class="wa__cs_img_wrap" style="background: url(<%= buttonStyles.avatar %>) center center no-repeat; background-size: cover;">
					</div>
				</div>
			<% } %>
			<div class="wa__btn_txt">
			<% if (!_.isEmpty(buttonStyles.title)) { %>
				<div class="wa__cs_info">
					<div class="wa__cs_name"><%= buttonStyles.title %></div>
					<div class="wa__cs_status"><?php echo esc_html__( 'Online', 'wp-whatsapp' ); ?></div>
				</div>
			<% } %>
				<div class="wa__btn_title"><%= buttonStyles.label %></div>
			</div>
		</a>
	</div>
</script>

