<?php

if ( ! class_exists( 'GF_Field' ) ) {
	die();
}

/**
 * @since 2.0
 */
class GF_Field_Business_Hours extends GF_Field {

	public $type = 'business_hours';

	public function __construct( array $data = array() ) {
		parent::__construct( $data );

		// Default label
		$this->label = __('Business Hours', 'gravity-forms-business-hours');

		$this->add_hooks();
	}

	/**
	 * Add filters to improve GravityView output
	 *
	 * @since 2.0
	 */
	private function add_hooks() {

		if ( function_exists( 'gravityview_strip_whitespace' ) ) {
			add_filter( 'gravityforms_business_hours_output_template', 'gravityview_strip_whitespace' );
		}

	}

	/**
	 * Set the field value on the Entries page
	 * @param  [type] $value    [description]
	 * @param  [type] $form_id  [description]
	 * @param  [type] $field_id [description]
	 * @param  array  $entry    GF entry array
	 * @return [type]           [description]
	 */
	public function business_hours_entries( $value, $form_id, $field_id, $entry = array() ) {

		$form = GFAPI::get_form( $form_id );

		return $this->display_entry_field_value( $value, $this, $entry, $form );
	}

	/**
	 * Limit the settings available for the field
	 *
	 * @return array
	 */
	function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'visibility_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
			'conditional_logic_field_setting',
			'css_class_setting',
		);
	}

	/**
	 * Modify the name of the field type in the Form Editor
	 * @return string       Field type string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Business Hours', 'gravity-forms-business-hours' );
	}

	/**
	 * Generate the field input HTML
	 *
	 * @param array $form
	 * @param string $value
	 * @param null $entry
	 *
	 * @return string
	 */
	function get_field_input( $form, $value = '', $entry = null ) {

		$is_form_editor = $this->is_form_editor();
		$is_ajax  = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		// In the editor, just show the placeholder
		if ( $is_form_editor || $is_ajax ) {
			return "<div class='gf-html-container gf-business-hours-container'><span class='gf_blockheader'><i class='dashicons dashicons-clock'></i> " . __('Business Hours', 'gravity-forms-business-hours') . '</span><span>' . __('This is a content placeholder. Preview this form to view the Business Hours field.', 'gravity-forms-business-hours') . "</span></div>";
		}

		$return = '';

		$list = gf_business_hours_get_list_from_value( $value );

		$list_string = '';

		// Populate existing list items
		if (!empty($list)) {

			foreach ($list as $list_value) {
				$list_string .= '<div class="business_hours_list_item">';

				$list_string .= '<strong>' . $list_value['daylabel'] . '</strong>';
				$list_string .= '<span>' . $list_value['fromtimelabel'] . '</span>';
				$list_string .= ' - ';
				$list_string .= '<span>' . $list_value['totimelabel'] . '</span>';
				$list_string .= '<a href="#" class="business_hours_remove_button"><i class="dashicons dashicons-dismiss"></i></a>';
				$list_string .= '</div>';
			}
		}

		$return .= '
				<div rel="business_hours_' . $this->id . '" class="business_hours field_setting business_hours_item" >
					<input type="hidden" name="input_' . $this->id . '" value=\'' . json_encode( $list ) . '\' />
						<div class="business_hours_list">' . $list_string . '</div>
					   <div class="business_hours_add_form">
							';
		$return .= $this->get_day_select();

		/**
		 * Change the default start time.
		 * @param string $time Time in 'H:i' format
		 */
		$default_start_time = apply_filters( 'gravityforms_business_hours_default_start_time', '09:00' );

		/**
		 * Change the default end time
		 * @param string $time Time in 'H:i' format
		 */
		$default_end_time = apply_filters( 'gravityforms_business_hours_default_end_time', '17:00' );

		$return .= gf_business_hours_get_times_select('item_fromtime', $default_start_time, false);
		$return .= gf_business_hours_get_times_select('item_totime', $default_end_time, true);

		$return .= '<a href="#" class="button gform_button business_hours_add_button"><i class="dashicons dashicons-plus-alt"></i> ' . __('Add Hours', 'gravity-forms-business-hours') . '</a>
					   </div>
				</div>
			';

		return $return;
	}

	/**
	 * Build a select field with the full name of the day as the value and abreviation as the label
	 * @return string HTML <select> field
	 */
	private function get_day_select() {

		$output = '<select class="item_day">';

		$days = gf_business_hours_get_days();

		foreach ($days as $key => $value) {
			$output .= '<option value="'.esc_attr( $key ).'">'.esc_html( $value ).'</option>';
		}

		$output .= '</select>';

		return $output;
	}


	/**
	 * Encode the field value
	 * @param  [type] $value [description]
	 * @param  [type] $lead  [description]
	 * @param  [type] $field [description]
	 * @param  [type] $form  [description]
	 * @return [type]        [description]
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		$is_already_json = json_decode( $value );

		// Don't double-encode
		if( is_null( $is_already_json ) ) {
			return json_encode( $value );
		}

		return $value;
	}

	/**
	 * Validate the field value.
	 *
	 * @param  mixed $value  Field value
	 * @param  array $form   Gravity Forms form array
	 * @return void
	 */
	public function validate( $value, $form ) {

		$json_value = json_decode( $value );

		if( $this->isRequired && ( empty( $value ) || $value === 'null' || is_null( $json_value ) ) ) {
			$this->failed_validation = true;
			$this->validation_message = __('This field is required.', 'gravity-forms-business-hours');
		}
	}

	/**
	 * Show the business hours in the Admin
	 *
	 * @param array|string $value
	 * @param array $entry
	 * @param string $field_id
	 * @param array $columns
	 * @param array $form
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {

		if ( is_array( $value ) ) {
			return '';
		}

		$value = $this->display_entry_field_value( $value, $this );

		return parent::get_value_entry_list( $value, $entry, $field_id, $columns, $form );
	}

	/**
	 * Display the populated field value - not editable
	 *
	 * @param  mixed $value  Field value
	 * @param  array $field  Gravity Forms field array
	 * @param  array $lead  Gravity Forms entry array
	 * @param  array $form   Gravity Forms form array
	 * @return string        HTML output of the field
	 */
	private function display_entry_field_value($value, $field, $lead = array(), $form = array() ) {

		$content = $value;

		$days = gf_business_hours_get_days();

		$filled_days = array();

		$list = gf_business_hours_get_list_from_value( $value );

		if (!empty($list) && is_array($list)) {

			/**
			 * @link https://schema.org/LocalBusiness
			 */
			$content = '<div class="business_hours_list_item" itemscope itemtype="https://schema.org/LocalBusiness">';

			foreach ($list as $time_span) {

				// Mark this day as open, so closed days can be processed below.
				$filled_days[] = $time_span['day'];

				/**
				 * Generate schema.org markup
				 * @link https://schema.org/openingHours
				 */
				$datetime = sprintf( '%s %s-%s', substr($time_span['day'], 0, 2), $time_span['fromtime'], str_replace('+', '', $time_span['totime'] ) );

				$output_template = '
					<div class="business-hours business-hours-open">
						<time itemprop="openingHoursSpecification" itemscope itemtype="https://schema.org/OpeningHoursSpecification" datetime="{{datetime}}">
							<strong itemprop="dayOfWeek" itemscope itemtype="https://schema.org/DayOfWeek" rel="{{daylabel}}"><span itemprop="name" content="{{day}}">{{daylabel}}</span></strong>
							<span itemprop="opens" content="{{fromtime}}">{{fromtimelabel}}</span> - <span itemprop="closes" content="{{totime}}">{{totimelabel}}</span>
							{{open_label}}
						</time>
					</div>';

				$replacements = array(
					'{{datetime}}' => $datetime,
					'{{day}}' => $time_span['day'],
					'{{daylabel}}' => (isset( $days[$time_span['day']] ) ? $days[$time_span['day']] : $time_span['day'] ),
					'{{fromtime}}' => $time_span['fromtime'],
					'{{fromtimelabel}}' => ( isset( $time_span['fromtimelabel'] ) ? $time_span['fromtimelabel'] : $time_span['fromtime'] ),
					'{{totime}}' => $time_span['totime'],
					'{{totimelabel}}' => ( isset( $time_span['totimelabel'] ) ? $time_span['totimelabel'] : $time_span['totime'] ),
					'{{open_label}}' => $this->get_open_label( $time_span ),
				);

				/**
				 * Modify the output of the open days. Data inside {{brackets}} will be replaced with the appropriate values.
				 * @param string $output_template HTML code
				 * @param  array $time_span description
				 * @param  array $replacements Default values to replace with
				 */
				$output_template = apply_filters( 'gravityforms_business_hours_output_template', $output_template, $time_span, $replacements );

				// Replace the keys ({{placeholders}}) with the data values
				$item_output = str_replace( array_keys( $replacements ), array_values( $replacements), $output_template );

				// Add to output
				$content .= $item_output;

			}

			// Array of days that are set
			$filled_days = array_unique( $filled_days );

			// Find what days aren't entered
			$empty_days = array_diff( array_keys($days), $filled_days );

			if( !empty( $empty_days ) ) {

				// And set them as closed
				foreach( $empty_days as $day ) {

					$output_template = '
						<div class="business-hours business-hours-closed">
							<strong>{{day}}</strong> <span>{{closed_label}}</span>
						</div>';

					$replacements = array(
						'{{day}}' => $days[ $day ], // Custom value at key of full day name
						'{{closed_label}}' => __('Closed', 'gravity-forms-business-hours'),
					);

					/**
					 * Modify the output of the open days. Data inside {{brackets}} will be replaced with the appropriate values.
					 * @param string $output_template HTML code
					 * @param  string $day Day of the week value
					 * @param  array $replacements Default values to replace with
					 */
					$output_template = apply_filters( 'gravityforms_business_hours_output_closed_template', $output_template, $days[ $day ], $replacements );

					// Replace the keys ({{placeholders}}) with the data values
					$item_output = str_replace( array_keys( $replacements ), array_values( $replacements), $output_template );

					// Add to output
					$content .= $item_output;

				}
			}

			$content .= "</div>";
		}

		return $content;
	}

	/**
	 * Generate the Open Now lael
	 * @param  array $time_span Time span array
	 * @return string            HTML output, if open. Empty string if not.
	 */
	private function get_open_label( $time_span ) {

		$output = '';

		if( gf_business_hours_is_open_now( $time_span ) ) {

			$output = '<span class="open_label">';
			$output .= esc_html__('Open Now', 'gravity-forms-business-hours');
			$output .= '</span>';
		}

		/**
		 * Modify the label for Open now
		 * @var string
		 */
		$output = apply_filters('gravityforms_business_hours_open_label', $output, $time_span );

		return $output;
	}

	function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		$value = $this->display_entry_field_value( $value, $this );

		$value = parent::get_value_entry_detail( $value, $currency, $use_text, $format, $media ); // TODO: Change the autogenerated stub

		// Don't nl2br() the HTML we send
		$value = str_replace( array( '<br />', '<br>' ), '', $value );

		return $value;
	}

	function allow_html() {
		return true;
	}

	function get_allowable_tags( $form_id = null ) {
		return '<div><time><strong><span>';
	}

	/**
	 * Returns the field button properties for the form editor. The array contains two elements:
	 * 'group' => 'standard_fields' // or  'advanced_fields', 'post_fields', 'pricing_fields'
	 * 'text'  => 'Button text'
	 *
	 * Built-in fields don't need to implement this because the buttons are added in sequence in GFFormDetail
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

}

GF_Fields::register( new GF_Field_Business_Hours() );