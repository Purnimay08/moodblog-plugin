<?php
/**
 * Plugin Name: MoodBlog – Daily Mood Tracker
 * Description: Allows users to submit their daily mood and notes, and stores them in the database.
 * Version: 1.0
 * Author: Purnima Yadav
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register the shortcode to display the form
function moodblog_display_form() {
    ob_start(); // Start output buffering
    ?>

    <form method="post" action="">
        <label for="mood">Today's Mood:</label><br>
        <select name="mood" id="mood" required>
            <option value="">-- Select Mood --</option>
            <option value="Happy">😊 Happy</option>
            <option value="Neutral">😐 Neutral</option>
            <option value="Sad">😢 Sad</option>
        </select><br><br>

        <label for="note">Journal Entry (optional):</label><br>
        <textarea name="note" id="note" rows="5" cols="40"></textarea><br><br>

        <input type="submit" name="moodblog_submit" value="Save Mood">
    </form>

    <?php
    return ob_get_clean(); // Return the buffered output
}
add_shortcode('moodblog_form', 'moodblog_display_form');

// Handle the form submission
function moodblog_handle_submission() {
    if (isset($_POST['moodblog_submit'])) {
        global $wpdb;

        $mood = sanitize_text_field($_POST['mood']);
        $note = sanitize_textarea_field($_POST['note']);
        $date = current_time('Y-m-d');

        // Create a custom table if it doesn't exist
        $table_name = $wpdb->prefix . 'moodblog_entries';
        $charset_collate = $wpdb->get_charset_collate();

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                mood varchar(50) NOT NULL,
                note text,
                date date NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Insert the form data into the database
        $wpdb->insert(
            $table_name,
            array(
                'mood' => $mood,
                'note' => $note,
                'date' => $date
            )
        );

        echo "<p style='color:green;'>✅ Mood saved successfully!</p>";
    }
}
add_action('init', 'moodblog_handle_submission');

// Add custom admin menu for viewing mood entries
function moodblog_admin_menu() {
    add_menu_page(
        'Mood Entries',           // Page title
        'MoodBlog Entries',       // Menu label
        'manage_options',         // Capability
        'moodblog-entries',       // Slug
        'moodblog_render_entries_page', // Function to display
        'dashicons-smiley',       // Icon
        6                         // Position
    );
}
add_action('admin_menu', 'moodblog_admin_menu');

// Function to display mood entries
function moodblog_render_entries_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'moodblog_entries';
    
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");

    echo '<div class="wrap">';
    echo '<h1>📝 Mood Entries</h1>';
    // Load Chart.js from CDN
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// Count moods
$counts = ['Happy' => 0, 'Neutral' => 0, 'Sad' => 0];
foreach ($results as $entry) {
    $m = $entry->mood;
    if (isset($counts[$m])) {
        $counts[$m]++;
    }
}

    
    if ($results) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Date</th><th>Mood</th><th>Journal Entry</th></tr></thead><tbody>';

        foreach ($results as $entry) {
            echo '<tr>';
            echo '<td>' . esc_html($entry->id) . '</td>';
            echo '<td>' . esc_html($entry->date) . '</td>';
            echo '<td>' . esc_html($entry->mood) . '</td>';
            echo '<td>' . esc_html($entry->note) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No mood entries found yet.</p>';
    }

    echo '</div>';
    echo '<h2>Mood Summary Chart</h2>';
echo '<canvas id="moodChart" width="400" height="200"></canvas>';
echo '
<script>
    const ctx = document.getElementById("moodChart").getContext("2d");
    const chart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["Happy", "Neutral", "Sad"],
            datasets: [{
                label: "Mood Count",
                data: [' . $counts['Happy'] . ', ' . $counts['Neutral'] . ', ' . $counts['Sad'] . '],
                backgroundColor: [
                    "rgba(75, 192, 192, 0.6)",
                    "rgba(255, 206, 86, 0.6)",
                    "rgba(255, 99, 132, 0.6)"
                ],
                borderColor: [
                    "rgba(75, 192, 192, 1)",
                    "rgba(255, 206, 86, 1)",
                    "rgba(255, 99, 132, 1)"
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
';

}

