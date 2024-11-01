<div class="wrap wctracktum-admin">
	<div class="wctracktum-two-column">
	    <div class="settings-wrap">
	        <h2><?php esc_html_e( 'Tracktum Settings', 'wc-tracktum' ); ?></h2>
	        <form action="" method="POST" id="integration-form">
	        	<div class="integration-wrappers">
	        	
	        	<?php
		        	foreach ( $integrations as $integration ) {
						$name           = $integration->get_name();
						$id             = $integration->get_id();
						$setting_fields = $integration->get_settings();
						$settings       = $integration->get_integration_settings();
						$active         = ( $integration->is_enabled() ) ? 'Deactivate' : 'Activate';
						$border         = ( isset( $integration->multiple ) ) ? 'wctracktum-border' : '';

	                    if ( ! $setting_fields ) {
	                        continue;
	                    }
	        	?>
		        	<div class="integration-wrap">
	                    <div class="integration-name">
	                        <div class="gateway">
								<img src="<?php echo esc_attr( plugins_url( 'assets/images/'. $id .'.png', tracktum_FILE ) )?>" />
								<h3 class="gateway-text"> <?php  esc_html_e( $name,'wc-tracktum'); ?> </h3>
									<label class="switch">
		                                <input type="checkbox" class="toogle-seller"
			                                name="settings[<?php echo esc_attr( $id ); ?>][enabled]"
			                                id="integration-<?php echo esc_attr( $id ); ?>"
			                                data-id="<?php echo esc_attr( $id ); ?>"
			                                value="1" <?php checked( true, $integration->is_enabled() ); ?>
		                                >
		                                <span class="slider" data-id="<?php echo esc_attr( $id ); ?>">
		                                    <span class="integration-tooltip"><?php //echo esc_attr( $active ); ?> </span>
		                                </span>
		                            </label>
	                        </div>
	                    </div>

	                	<div class="integration-settings" id="setting-<?php echo esc_attr( $id ); ?>">
	                		<?php
		                        if ( ! $setting_fields ) {
		                            continue;
		                        }
		                    ?>
		                    <div class="wctracktum-form-group">
		                    	<div class="form-table custom-table" style="display: inline;">
		                    		<?php foreach ( $setting_fields as $key => $field ) { ?>
										<label for="<?php echo esc_attr( $id . '-' . $field['label'] ) ; ?>"> <?php echo esc_html( $field['label'] ); ?> </label>
										<div class="integration-field field-<?php echo esc_attr($key); ?>">
											 <?php
											 	$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
												switch ( $field['type'] ) {
													case 'text':
														$value = isset( $settings[0][ $field['name'] ] ) ? $settings[0][ $field['name'] ] : '';
														printf( '<input type="text" name="settings[%s][%d][%s]" placeholder="%s" value="%s" id="%s"
	                                                        required />',
															esc_attr( $id ),0, esc_attr( $field['name'] ), esc_attr( $placeholder ), esc_attr( $value ),
															esc_attr( $id . '-' . $field['label'] )
														);
														break;
													case 'textarea':
														$value = isset( $settings[ $field['name'] ] ) ? $settings[ $field['name'] ] : '';
														printf( '<textarea name="settings[%s][%s]" placeholder="%s" id="%s" cols="30" rows="3" > %s</textarea>', esc_attr( $id ), esc_attr( $field['name'] ),  esc_attr( $placeholder ), esc_attr( $id . '-' . $field['label'] ),esc_attr( $value ) );
														break;
													case 'checkbox':
														printf( '<input type="checkbox" name="settings[%s][%s]" %s id="%s" required />',
															esc_attr( $id ), esc_attr( $field['name'] ), checked( 'on', $value, false ), esc_attr( $id . '-' . $field['label'] )
														);
														break;
													case 'multicheck':
														foreach ( $field['options'] as $field_key => $option ) {
															$field_name = $field['name'];
															$name    = isset( $option['label_name'] ) ? $option['label_name'] : '';
															$checked = isset( $settings[0][ $field_name ][ $field_key ] ) ? 'on' : '';
														?>
															<label for="<?php echo esc_attr( $id . '-' . $field_key ); ?>">
																<input type="checkbox"
																	name="settings[<?php echo esc_attr( $id ); ?>][0][<?php echo esc_attr( $field['name'] ); ?>][<?php echo esc_attr( $field_key ); ?>]"  <?php checked( 'on', $checked ); ?> />
															
																<?php echo isset( $option['label']) ? esc_attr( $option['label'] ) : esc_attr( $option ); ?>
															</label>
														<?php }
													default:
														break;
												}

												if( isset( $field['help'] ) && !empty($field['help']) ) {
													// echo esc_html( $field['help'] );
												}
											?>
										</div>
									<?php } ?>
								</div>
							</div>
	                    </div>
					</div>

				<?php } ?>
			</div>

				<div class="submit-area">
                	<?php wp_nonce_field( 'wctracktum-settings-action', 'wctracktum-settings-nonce' );?>
               	 	<input type="hidden" name="action" value="wctracktum_save_settings">
               		<button class="button button-primary" id="wctracktum-submit"><?php esc_html_e( 'Save Changes', 'wc-tracktum' ); ?></button>
            	</div>
	        </form>
	    </div>
	</div>
</div>