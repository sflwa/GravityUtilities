<?php
	/**
	 *
	 */
?>

<tr>
	<td>
		<input type="checkbox" id="gform_hide_form_user_submitted" name="form_hide_form_user_submitted"
					 value="1" <?php checked( rgar( $form, 'hideFormUserSubmitted' ), '1', true ) ?> />
		<label
			for="gform_hide_form_user_submitted"><?php _e( 'Hide form if logged-in user already submitted this form', 'gravityformsutility' ) ?></label>
	</td>
</tr>