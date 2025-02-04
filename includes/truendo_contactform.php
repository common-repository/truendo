<?php
/**
** A base module for [truendo]
**/

/* form_tag handler */

add_action( 'wpcf7_init', 'wpcf7_add_form_tag_truendo', 10, 0 );

function wpcf7_add_form_tag_truendo() {
	wpcf7_add_form_tag( 'truendo',
		'wpcf7_truendo_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function wpcf7_truendo_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	if ( $tag->has_option( 'invert' ) ) {
		$class .= ' invert';
	}

	if ( $tag->has_option( 'optional' ) ) {
		$class .= ' optional';
	}

	$atts = array(
		'class' => trim( $class ),
	);

	$item_atts = array();

	$item_atts['type'] = 'checkbox';
	$item_atts['name'] = $tag->name;
	$item_atts['value'] = '1';
	$item_atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$item_atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	if ( $tag->has_option( 'default:on' ) ) {
		$item_atts['checked'] = 'checked';
	}

	$item_atts['class'] = $tag->get_class_option();
	$item_atts['id'] = $tag->get_id_option();

	$item_atts = wpcf7_format_atts( $item_atts );

	$content = empty( $tag->content )
		? (string) reset( $tag->values )
		: $tag->content;

	$content = trim( $content );

	if ( $content ) {
		$html = sprintf(
			'<span class="wpcf7-list-item"><label><input %1$s /><span class="wpcf7-list-item-label">%2$s</span></label></span>',
			$item_atts, $content );
	} else {
		$html = sprintf(
			'<span class="wpcf7-list-item"><input %1$s /></span>',
			$item_atts );
	}

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><span %2$s>%3$s</span>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $html, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_truendo',
	'wpcf7_truendo_validation_filter', 10, 2 );

function wpcf7_truendo_validation_filter( $result, $tag ) {
	if ( ! wpcf7_truendo_as_validation() ) {
		return $result;
	}

	if ( $tag->has_option( 'optional' ) ) {
		return $result;
	}

	$name = $tag->name;
	$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

	$invert = $tag->has_option( 'invert' );

	if ( $invert and $value
	or ! $invert and ! $value ) {
		$result->invalidate( $tag, wpcf7_get_message( 'accept_terms' ) );
	}

	return $result;
}


/* truendo filter */

add_filter( 'wpcf7_truendo', 'wpcf7_truendo_filter', 10, 2 );

function wpcf7_truendo_filter( $accepted, $submission ) {
	$tags = wpcf7_scan_form_tags( array( 'type' => 'truendo' ) );

	foreach ( $tags as $tag ) {
		$name = $tag->name;

		if ( empty( $name ) ) {
			continue;
		}

		$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

		$content = empty( $tag->content )
			? (string) reset( $tag->values )
			: $tag->content;

		$content = trim( $content );

		if ( $value and $content ) {
			$submission->add_consent( $name, $content );
		}

		if ( $tag->has_option( 'optional' ) ) {
			continue;
		}

		$invert = $tag->has_option( 'invert' );

		if ( $invert and $value
		or ! $invert and ! $value ) {
			$accepted = false;
		}
	}

	return $accepted;
}

add_filter( 'wpcf7_form_class_attr',
	'wpcf7_truendo_form_class_attr', 10, 1 );

function wpcf7_truendo_form_class_attr( $class ) {
	if ( wpcf7_truendo_as_validation() ) {
		return $class . ' wpcf7-truendo-as-validation';
	}

	return $class;
}

function wpcf7_truendo_as_validation() {
	if ( ! $contact_form = wpcf7_get_current_contact_form() ) {
		return false;
	}

	return $contact_form->is_true( 'truendo_as_validation' );
}

add_filter( 'wpcf7_mail_tag_replaced_truendo',
	'wpcf7_truendo_mail_tag', 10, 4 );

function wpcf7_truendo_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
	$form_tag = $mail_tag->corresponding_form_tag();

	if ( ! $form_tag ) {
		return $replaced;
	}

	if ( ! empty( $submitted ) ) {
		$replaced = __( 'Consented', 'contact-form-7' );
	} else {
		$replaced = __( 'Not consented', 'contact-form-7' );
	}

	$content = empty( $form_tag->content )
		? (string) reset( $form_tag->values )
		: $form_tag->content;

	if ( ! $html ) {
		$content = wp_strip_all_tags( $content );
	}

	$content = trim( $content );

	if ( $content ) {
		/* translators: 1: 'Consented' or 'Not consented', 2: conditions */
		$replaced = sprintf(
			_x( '%1$s: %2$s', 'mail output for truendo checkboxes',
				'contact-form-7' ),
			$replaced,
			$content );
	}

	return $replaced;
}


/* Tag generator */

add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_truendo', 35, 0 );

function wpcf7_add_tag_generator_truendo() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'truendo', __( 'truendo', 'contact-form-7' ),
		'wpcf7_tag_generator_truendo' );
}

function wpcf7_tag_generator_truendo( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'truendo';

	$description = __( "Generate a form-tag for an truendo checkbox. For more details, see %s.", 'contact-form-7' );

	$desc_link = wpcf7_link( __( 'https://contactform7.com/truendo-checkbox/', 'contact-form-7' ), __( 'truendo Checkbox', 'contact-form-7' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-content' ); ?>"><?php echo esc_html( __( 'Condition', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="content" class="oneline large-text" id="<?php echo esc_attr( $args['content'] . '-content' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></legend>
		<label><input type="checkbox" name="optional" class="option" checked="checked" /> <?php echo esc_html( __( 'Make this checkbox optional', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>
</div>
<?php
}
