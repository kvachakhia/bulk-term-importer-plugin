<?php
/*
Plugin Name: Bulk term importer
Plugin URI: https://github.com/kvachakhia/bulk-term-importer-plugin/
Description: Allows selection of taxonomy, parent term, and adding multiple terms with commas.
Version: 1.0
Author: Dimitri Kvachakhia
Author URI: https://dima.ge
*/

// Add custom menu in the admin
function my_category_plugin_menu()
{
    add_menu_page('Bulk term importer', 'Bulk term importer', 'manage_options', 'bulk-term-importer-plugin', 'my_category_plugin_page', 'dashicons-admin-plugins', 10);
}
add_action('admin_menu', 'my_category_plugin_menu');

// Callback function for the custom menu page
function my_category_plugin_page()
{
    // Process form submission
    if (isset($_POST['submit'])) {
        // Get the submitted data
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $parent_term = isset($_POST['parent_term']) ? absint($_POST['parent_term']) : 0;
        $terms = isset($_POST['terms']) ? sanitize_text_field($_POST['terms']) : '';

        // Perform necessary actions with the submitted data
        if (!empty($terms)) {
            $term_names = explode(',', $terms);
            $term_names = array_map('trim', $term_names);

            foreach ($term_names as $term_name) {
                wp_insert_term($term_name, $taxonomy, array('parent' => $parent_term));
            }
        }

        // Display success message
        echo '<div class="notice notice-success"><p>Data saved successfully!</p></div>';
    }

    // Display the form for selecting taxonomy, parent term, and adding terms
?>
    <div class="wrap">
        <h1>Category Plugin</h1>
        <form method="post">
            <label for="taxonomy">Select Taxonomy:</label>
            <br>
            <select name="taxonomy" id="taxonomy">
                <option value="">Select Taxonomy</option>
                <?php
                $taxonomies = get_taxonomies(array('public' => true), 'objects');
                foreach ($taxonomies as $taxonomy) {
                    echo '<option value="' . $taxonomy->name . '">' . $taxonomy->label . '</option>';
                }
                ?>
            </select>
            <br><br>
            <label for="parent_term">Select Parent Term:</label>
            <br>
            <select name="parent_term" id="parent_term">
                <option value="">Select Parent Term</option>
            </select>
            <br><br>
            <label for="terms">Terms:</label>
            <br>
            <br>
            <textarea name="terms" id="terms" rows="15" cols="50"></textarea>
            <br><br>
            <input type="submit" name="submit" class="button button-primary" value="Save">
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Fetch parent terms based on the selected taxonomy
            $('#taxonomy').on('change', function() {
                var taxonomy = $(this).val();
                $('#parent_term').html('<option value="">Select Parent Term</option>');

                if (taxonomy !== '') {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'fetch_parent_terms',
                            taxonomy: taxonomy
                        },
                        success: function(response) {
                            if (response) {
                                var terms = JSON.parse(response);
                                $.each(terms, function(key, term) {
                                    $('#parent_term').append('<option value="' + term.term_id + '">' + term.name + '</option>');
                                });
                            }
                        }
                    });
                }
            });
        });
    </script>
<?php
}

// AJAX handler for fetching parent terms based on the selected taxonomy
function my_category_plugin_fetch_parent_terms()
{
    if (isset($_POST['taxonomy'])) {
        $taxonomy = sanitize_text_field($_POST['taxonomy']);
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => 0,
        ));

        echo wp_json_encode($terms);
    }

    wp_die();
}
add_action('wp_ajax_fetch_parent_terms', 'my_category_plugin_fetch_parent_terms');

// Enqueue the necessary scripts for the admin page
function my_category_plugin_enqueue_scripts($hook)
{
    if ($hook === 'toplevel_page_my-category-plugin') {
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'my_category_plugin_enqueue_scripts');
