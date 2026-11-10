<?php
global $wp_roles;
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><label for="nta-wa-select-user-roles"><?php echo esc_html__( 'Select User Roles to access', 'wp-whatsapp' ); ?></label></th>
			<td>
				<div style="line-height: 2" class="nta-wa-setting-width nta-wa-list-col4">
					<?php
					foreach ( $wp_roles->roles as $key => $value ) {
						?>
						<?php if ( 'administrator' !== $key ) { ?>
							<span class="list-col4-item">
								<input type="checkbox" class="nta-wa-list-user-restrictions-item" id="<?php echo esc_attr( $key ); ?>" name="userRole[]"
								value="<?php echo esc_attr( $key ); ?>" <?php echo isset( $option[ $key ] ) ? 'checked' : ''; ?>>
								<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['name'] ); ?></label>
							</span>
						<?php } ?>
					<?php } ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<button class="button button-large button-primary wa-save"><span class="wa-save-text"><?php esc_html_e( 'Save Changes', 'wp-whatsapp' ); ?></span></button>