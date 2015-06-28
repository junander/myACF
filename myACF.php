<?php

/**
 * ACF Generator Class
 * To simplify generating Advanced Custom Fields
 * Depends on Advanced Custom Fields Pro Plugin v.5+
 * 
 * @author junander
 * @version: 0.1
 */
class myACF {

    /**
     * Core Options
     * 
     * @var array $core_options holds core options for ACF generation
     */
    public $core_options;

    /**
     * User Options
     * 
     * @var type $user_options holds user submitted overrides for core options, on __contstruct()
     */
    public $user_options;

    /**
     * Location
     * 
     * @var type $location holds user submitted location(s), on __contstruct()
     */
    public $location;

    /**
     * Fields
     * 
     * @var array $fields holds user submitted fields assing on __construct()
     */
    public $fields;

    /**
     * Constructor
     */
    function __construct($args) {
        //parse user options into core options
        $this->core_options = wp_parse_args($args['options'], $this->build_default_core_options());

        //set user submitted location to location object
        if (empty($args['location'])) {
            $this->location = $this->build_location($this->default_location());
        } else {
            $this->location = $args['location'];
        }

        //set user submitted fields to fields object
        $this->fields = array();
        $this->fields = $this->build_fields($args['fields']);
        $this->checkConditionalLogic();

        //build and register field
        $this->register_field();
    }

    /**
     * Core options defaults
     * Does not include location or fields, which the user is expected to
     * provide separately
     */
    function build_default_core_options() {

        $core_options = array(
            'key' => '',
            'title' => '',
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array()
        );

        return $core_options;
    }

    /**
     * Default location
     * Defaults to a post
     * @return type
     */
    function default_location() {

        return array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'post',
        );
    }

    function field_defaults($type = 'text') {

        $field_defaults = array(
            'key' => '',
            'label' => '',
            'name' => '',
            'prefix' => '',
            'type' => '',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
            ),
        );


        if (in_array($type, array('select', 'checkbox', 'radio'))) {
            $this_ary = array(
                'choices' => array(
                    '' => '',
                ),
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('select', 'checkbox'))) {
            $this_ary = array(
                'default_value' => array(
                    '' => '',
                ),
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('checkbox', 'radio'))) {
            $this_ary = array(
                'layout' => 'vertical',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('radio'))) {
            $this_ary = array(
                'other_choice' => 0,
                'save_other_choice' => 0,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('wysiwyg', 'text', 'number', 'radio'))) {
            $this_ary = array(
                'default_value' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('color_picker'))) {
            $this_ary = array(
                'default_value' => '#ffffff',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('wysiwyg'))) {
            $this_ary = array(
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('text', 'textarea', 'number', 'select'))) {
            $this_ary = array(
                'placeholder' => '',
                'readonly' => 0,
                'disabled' => 0,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('text', 'textarea'))) {
            $this_ary = array(
                'maxlength' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('text', 'number'))) {
            $this_ary = array(
                'prepend' => '',
                'append' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('number', 'repeater'))) {
            $this_ary = array(
                'min' => '',
                'max' => '',
                'step' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('number'))) {
            $this_ary = array(
                'step' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('textarea'))) {
            $this_ary = array(
                'rows' => '',
                'new_lines' => 'wpautop',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('image'))) {
            $this_ary = array(
                'return_format' => 'array',
                'preview_size' => 'thumbnail',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('select, post_object'))) {
            $this_ary = array(
                'allow_null' => 0,
                'multiple' => 0,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('select'))) {
            $this_ary = array(
                'ui' => 0,
                'ajax' => 0,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('date_picker'))) {
            $this_ary = array(
                'display_format' => 'F j, Y',
                'return_format' => 'Ymd',
                'first_day' => 1,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('date_time_picker'))) {
            $this_ary = array(
                'show_date' => 'false',
                'date_format' => 'm/d/y',
                'time_format' => 'h:mm tt',
                'show_week_number' => 'false',
                'picker' => 'select',
                'save_as_timestamp' => 'true',
                'get_as_timestamp' => 'true',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('post_object'))) {
            $this_ary = array(
                'post_type' => '',
                'taxonomy' => '',
                'return_format' => 'object',
                'ui' => 1,
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('repeater'))) {
            $this_ary = array(
                'layout' => 'row',
                'button_label' => 'Add Row',
                'sub_fields' => array(),
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        if (in_array($type, array('row_floats'))) {
            $this_ary = array(
                'position' => 'wrapper_start',
            );
            $field_defaults = array_merge($field_defaults, $this_ary);
        }

        return $field_defaults;
    }

    /**
     * Builds out the location arrays for ACF
     * They are double nested arrays, so this is just saving some typing
     * @param type $locations
     * @return array
     */
    function build_location($locations) {
        $location_return = array();

        //check to make sure location is ready for parsing
        if (is_array($locations) && !empty($locations)) {
            foreach ($locations as $location) {
                $location_return[] = array(array($location));
            }
        }

        return $location_return;
    }

    function build_fields($fields) {
        $core_options = $this->core_options;
        $fields_return = array();

        foreach ($fields as $field) {

            //if there is no label, we're done with this field
            if (!isset($field['label'])) {
                continue;
            }

            $this_field = array();

            if (isset($field['type'])) {
                $this_field = wp_parse_args($field, $this->field_defaults($field['type']));

                //fallback for name
                if ($field['name'] == '') {
                    $this_field['name'] = str_replace('-', '_', sanitize_title($field['label']));
                } else {
                    $this_field['name'] = str_replace('-', '_', sanitize_title($field['name']));
                }

                //extra parsing for repeaters and flexible content fields
                if ($field['type'] == 'repeater') {

                    if (count($field['sub_fields']) > 0) {

                        unset($this_field['sub_fields']);
                        $this_field['sub_fields'] = array();

                        foreach ($field['sub_fields'] as $sub_field) {

                            //if there is no label, we're done with this subfield
                            if (!isset($sub_field['label'])) {
                                continue;
                            }

                            if (isset($sub_field['type'])) {
                                $this_subfield = wp_parse_args($sub_field, $this->field_defaults($sub_field['type']));
                            } else {
                                $this_subfield = wp_parse_args($sub_field, $this->field_defaults());
                            }

                            //fallback for name
                            if ($this_subfield['name'] == '') {
                                $this_subfield['name'] = str_replace('-', '_', sanitize_title($sub_field['label']));
                            } else {
                                $this_subfield['name'] = str_replace('-', '_', sanitize_title($sub_field['name']));
                            }

                            $set_key = get_option('acffieldkey_' . str_replace('-', '_', sanitize_key($core_options['title'])) . '_' . $this_field['name'] . '_' . $this_subfield['name']);

                            //make sure group key is set
                            if ($this_subfield['key'] == '') {

                                if ($set_key) {
                                    $this_subfield['key'] = $set_key;
                                } else {
                                    $this_subfield['key'] = acf_get_valid_field_key();
                                    update_option('acffieldkey_' . str_replace('-', '_', sanitize_key($core_options['title'])) . '_' . $this_field['name'] . '_' . $this_subfield['name'], $this_subfield['key']);
                                }
                            }

                            array_push($this_field['sub_fields'], $this_subfield);
                        }
                    }
                }
            } else {
                //defaults to text field
                $this_field = wp_parse_args($field, $this->field_defaults());

                //fallback for name
                if ($field['name'] == '') {
                    $this_field['name'] = str_replace('-', '_', sanitize_title($field['label']));
                } else {
                    $this_field['name'] = str_replace('-', '_', sanitize_title($field['name']));
                }
            }

            $set_key = get_option('acffieldkey_' . str_replace('-', '_', sanitize_key($core_options['title'])) . '_' . $this_field['name']);

            //make sure group key is set
            if ($this_field['key'] == '') {

                if ($set_key) {
                    $this_field['key'] = $set_key;
                } else {
                    $this_field['key'] = acf_get_valid_field_key();
                    update_option('acffieldkey_' . str_replace('-', '_', sanitize_key($core_options['title'])) . '_' . $this_field['name'], $this_field['key']);
                }
            }

            array_push($fields_return, $this_field);
        }

        return $fields_return;
    }

    //checking conditional logic to set up correct field keys if necessary
    function checkConditionalLogic() {
        $fields = $this->fields;

        foreach ($fields as $key => $this_field) {
            if (isset($this_field['conditional_logic']) && is_array($this_field['conditional_logic'])) {
                $logic_check = $this->fieldConditionalLogic($this_field['conditional_logic']);
                unset($this_field['conditional_logic']);
                $fields[$key]['conditional_logic'] = $logic_check;
            }
            if (isset($this_field['sub_fields'])) {
                foreach ($this_field['sub_fields'] as $sub_key => $this_sub_field) {
                    if (isset($this_sub_field['conditional_logic']) && is_array($this_sub_field['conditional_logic'])) {
                        $logic_check = $this->fieldConditionalLogic($this_sub_field['conditional_logic']);
                        unset($this_sub_field['conditional_logic']);
                        $fields[$key]['sub_fields'][$sub_key]['conditional_logic'] = $logic_check;
                    }
                }
            }
        }

        $this->fields = $fields;
    }

    function fieldConditionalLogic($logic_field) {
        $fields = $this->fields;

        if (isset($logic_field['rules']) && is_array($logic_field['rules'])) {

            foreach ($logic_field['rules'] as $key => $rule) {

                if (isset($rule['field']) && strpos($rule['field'], 'field_') !== 0) {
                    $field_key = $this->findFieldKeyByLabel($rule['field']);
                    if ($field_key) {
                        $logic_field['rules'][$key]['field'] = $field_key;
                    }
                }
            }
        }

        return $logic_field;
    }

    function findFieldKeyByLabel($label) {
        $fields = $this->fields;
        $return_key = NULL;

        foreach ($fields as $this_field) {

            if (strpos($label, '->')) {

                $label_elem = explode('->', $label);
                $field_label = $label_elem[0];
                $field_sub_label = $label_elem[1];

                if ($this_field['label'] == $field_label) {

                    $sub_fields = $this_field['sub_fields'];
                    foreach ($sub_fields as $sub_field) {
                        if ($sub_field['label'] == $field_sub_label) {
                            $return_key = $sub_field['key'];
                        }
                    }
                }
            } else if ($this_field['label'] == $label) {
                $return_key = $this_field['key'];
            }
        }

        return $return_key;
    }

    function register_field() {
        $array_prep = array();
        $field_array = array();

        $array_prep = array(
            'fields' => $this->fields,
            'location' => array($this->location),
        );

        $field_array = array_merge($array_prep, $this->core_options);

        $group_name = str_replace('-', '_', sanitize_key($field_array['title']));
        $set_key = get_option('acfgroupkey_' . $group_name);
        //make sure group key is set
        if ($field_array['key'] == '') {

            if ($set_key) {
                $field_array['key'] = $set_key;
            } else {
                $field_array['key'] = acf_get_valid_field_group_key();
                update_option('acfgroupkey_' . $group_name, $field_array['key']);
            }
        }

        //echo '<pre>' . print_r($field_array, true) . '</pre>';

        register_field_group($field_array);
    }

}
