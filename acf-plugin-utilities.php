<?php

/* *
 * Utilities for plugin interaction
 * For commonly used plugins
 */

/**
 * Advanced custom fields
 */

/**
 * Plugin agnostic function for retrieving post meta set up by ACF
 * @param type $name
 * @param type $post_ID
 * @param type $level
 * @param type $keys
 * @return boolean
 */
function mu_get_field($name, $post_ID, $type = 'text', $level = 'single', $keys = NULL) {
    $field = false;

    if ($post_ID === 'option') {
        $field_meta = get_option('options_' . $name);

        if (!$field_meta) {
            return $field;
        }

        $type = 'option';

        $field = mu_get_options_field($name, $post_ID, $field_meta, $keys);
    } else {
        $field_meta = get_post_meta($post_ID, $name, true);

        if (!$field_meta) {
            return $field;
        }

        switch ($level) {
            case 'single':
                $field = mu_field_type_check($field_meta, $post_ID, $type);
                break;
            case 'repeater':
                $field = mu_get_repeater_field($name, $post_ID, $field_meta, $keys);
                break;
        }
    }

    return $field;
}

function mu_get_repeater_field($name, $post_ID, $num, $keys) {
    $repeater_out = array();

    for ($i = 0; $i < $num; $i++) {

        foreach ($keys as $fieldname => $type) {
            $meta_key = $name . '_' . $i . '_' . $fieldname;

            $meta_in = mu_field_type_check(get_post_meta($post_ID, $meta_key, true), $post_ID, $type);

            $repeater_out[$i][$fieldname] = $meta_in;
        }
    }

    return $repeater_out;
}

function mu_get_options_field($name, $post_ID, $field_meta, $keys = array()) {
    $options_out = array();

    if (!empty($keys)) {

        foreach ($keys as $fieldname => $data) {

            $target_key = array_search($fieldname, $field_meta);

            if ($target_key !== false) {
                $option_key = 'options_' . $name . '_' . $target_key . '_';

                foreach ($data as $fielddata => $type) {
                    $field = mu_field_type_check(get_option($option_key . $fielddata), $post_ID, $type);
                    $options_out[$name][$fieldname][$fielddata] = $field;
                }
            }
        }
    }

    return $options_out;
}

function mu_field_type_check($field, $post_ID, $type = 'text') {

    if (!field) {
        return false;
    }

    switch ($type) {
        case 'image':
        case 'image_crop':
            //for cropped images
            if (is_string($field)) {
                $field_check = json_decode($field);
                if (isset($field_check->original_image)) {

                    $attachment_target = $field_check->original_image;

                    if (isset($field_check->cropped_image)) {
                        $attachment_target = $field_check->cropped_image;
                    }

                    $field = mu_get_attachment($attachment_target);
                }
            }

            //checking for regular images
            if (is_numeric($field)) {

                $image_check = wp_get_attachment_image_src($field);
                if ($image_check) {
                    $field = mu_get_attachment($field);
                }
            }
            break;
        case 'wysiwyg':
            $field = mu_acf_content_setup($field, $post_ID);
            break;
    }

    return $field;
}

function mu_acf_content_setup($value, $post_ID) {
    global $post;

    //override $post global in case we are on regen or bulk edit
    $legacy = $post;
    $post_obj = get_post($post_ID);
    $post = $post_obj;
    setup_postdata($post);
    
    if (function_exists('register_field_group')) {
        $value = apply_filters('acf_the_content', $value);
    } else {
        $value = apply_filters('the_content', $value);
    }

    $value = str_replace(']]>', ']]&gt;', $value);

    //safely restore $post defaults
    wp_reset_postdata();
    $post = $legacy;
    
    return $value;
}

/**
 * Get attachment data
 * Borrowed from ACF, stored here to have theme-centric function
 * @param type $post
 * @return boolean
 */
function mu_get_attachment($post) {

    // post
    $post = get_post($post);


    // bail early if no post
    if (!$post)
        return false;


    // vars
    $thumb_id = 0;
    $id = $post->ID;
    $a = array(
        'ID' => $id,
        'id' => $id,
        'title' => $post->post_title,
        'filename' => wp_basename($post->guid),
        'url' => wp_get_attachment_url($id),
        'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
        'author' => $post->post_author,
        'description' => $post->post_content,
        'caption' => $post->post_excerpt,
        'name' => $post->post_name,
        'date' => $post->post_date_gmt,
        'modified' => $post->post_modified_gmt,
        'mime_type' => $post->post_mime_type,
        'type' => mu_maybe_get(explode('/', $post->post_mime_type), 0, ''),
        'icon' => wp_mime_type_icon($id)
    );


    // video may use featured image
    if ($a['type'] === 'image') {

        $thumb_id = $id;
        $src = wp_get_attachment_image_src($id, 'full');

        $a['url'] = $src[0];
        $a['width'] = $src[1];
        $a['height'] = $src[2];
    } elseif ($a['type'] === 'audio' || $a['type'] === 'video') {

        // video dimentions
        if ($a['type'] == 'video') {

            $meta = wp_get_attachment_metadata($id);
            $a['width'] = mu_maybe_get($meta, 'width', 0);
            $a['height'] = mu_maybe_get($meta, 'height', 0);
        }


        // feature image
        if ($featured_id = get_post_thumbnail_id($id)) {

            $thumb_id = $featured_id;
        }
    }


    // sizes
    if ($thumb_id) {

        // find all image sizes
        if ($sizes = get_intermediate_image_sizes()) {

            $a['sizes'] = array();

            foreach ($sizes as $size) {

                // url
                $src = wp_get_attachment_image_src($thumb_id, $size);

                // add src
                $a['sizes'][$size] = $src[0];
                $a['sizes'][$size . '-width'] = $src[1];
                $a['sizes'][$size . '-height'] = $src[2];
            }
        }
    }

    // return
    return $a;
}

function mu_maybe_get($array, $key, $default = null) {

    // vars
    $keys = explode('/', $key);


    // loop through keys
    foreach ($keys as $k) {

        // return default if does not exist
        if (!isset($array[$k])) {

            return $default;
        }


        // update $array
        $array = $array[$k];
    }


    // return
    return $array;
}
