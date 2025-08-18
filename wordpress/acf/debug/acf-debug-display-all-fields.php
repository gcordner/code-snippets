<?php
/*
* ACF Field Debug Display
* 
* Purpose: Display all ACF (Advanced Custom Fields) for the current page/post
* Use case: Debugging ACF setup, checking field values, troubleshooting templates
* 
* Usage: 
* - Drop this code into a page template, functions.php, or test file
* - Outputs directly to the page (HTML <p> tags)
* - Shows field names and their current values
* 
* What it handles:
* - Arrays (like multi-select, checkboxes)
* - WP_Post objects (like post relationships)  
* - Regular text/number fields
* 
* Output location: Wherever you place this code in your template
* Note: Remove from production - this is for development/debugging only
*/

function display_all_acf_fields()
{
    // Get all ACF fields for current page/post
    $fields = get_fields();
    
    // Check if there are any fields
    if ($fields) {
        echo '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px; background: #f9f9f9;">';
        echo '<h3>ACF Fields Debug:</h3>';
        
        // Loop through each field
        foreach ($fields as $name => $value) {
            // Check if the value is an array
            if (is_array($value)) {
                // If it's an array, print the array values
                echo '<p><strong>' . $name . '</strong>: ';
                foreach ($value as $item) {
                    // Check if the item is an object
                    if (is_object($item) && $item instanceof WP_Post) {
                        echo $item->post_title . ', ';
                    } else {
                        echo $item . ', ';
                    }
                }
                echo '</p>';
            } elseif (is_object($value) && $value instanceof WP_Post) {
                // If it's a WP_Post object, retrieve the post title
                echo '<p><strong>' . $name . '</strong>: ' . $value->post_title . '</p>';
            } else {
                // If it's not an array or WP_Post object, display field name and value
                echo '<p><strong>' . $name . '</strong>: ' . $value . '</p>';
            }
        }
        echo '</div>';
    } else {
        echo '<p>No ACF fields found for this page.</p>';
    }
}

// Execute the function
display_all_acf_fields();
?>