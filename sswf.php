<?php
/**
 * Plugin Name: Stupid Simple Word Filter
 * Description: Easily manage prohibited words and phrases in Gutenberg comments or forms.
 * Version: 1.0.5
 * Author: Dynamic Technologies
 * Author URI: https://bedynamic.tech
 * Plugin URI: https://github.com/bedynamictech/Stupid-Simple-Word-Filter
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add admin menu
function sswf_add_menu() {
    add_menu_page(
        'Stupid Simple',
        'Stupid Simple',
        'manage_options',                // fixed capability string
        'stupidsimple',
        'sswf_settings_page_content',
        'dashicons-hammer',
        99
    );

    add_submenu_page(
        'stupidsimple',
        'Word Filter Settings',
        'Word Filter',
        'manage_options',
        'stupid-simple-word-filter',
        'sswf_settings_page_content'
    );
}
add_action( 'admin_menu', 'sswf_add_menu' );

// Display settings page content
function sswf_settings_page_content() {
    ?>
    <div class="wrap">
        <h1>Word Filter</h1>

        <?php
        // Add a word
        if ( isset( $_POST['sswf_word_to_add'] ) ) {
            check_admin_referer( 'sswf_save_words', 'sswf_nonce' );
            $new_word = sanitize_text_field( wp_unslash( $_POST['sswf_word_to_add'] ) );
            $prohibited_words = get_option( 'sswf_prohibited_words', array() );

            if ( $new_word && ! in_array( $new_word, $prohibited_words, true ) ) {
                $prohibited_words[] = $new_word;
                update_option( 'sswf_prohibited_words', $prohibited_words );
                echo '<div class="updated"><p>Word or Phrase Added!</p></div>';
            } else {
                echo '<div class="error"><p>This word/phrase is already in the list or empty.</p></div>';
            }
        }

        // Delete a word
        if ( isset( $_POST['word_to_delete'] ) ) {
            check_admin_referer( 'sswf_save_words', 'sswf_nonce' );
            $word_to_delete   = sanitize_text_field( wp_unslash( $_POST['word_to_delete'] ) );
            $prohibited_words = get_option( 'sswf_prohibited_words', array() );

            $prohibited_words = array_values(
                array_filter( $prohibited_words, function( $word ) use ( $word_to_delete ) {
                    return $word !== $word_to_delete;
                } )
            );

            update_option( 'sswf_prohibited_words', $prohibited_words );
            echo '<div class="updated"><p>Word or Phrase Removed!</p></div>';
        }
        ?>

        <form method="post">
            <?php wp_nonce_field( 'sswf_save_words', 'sswf_nonce' ); ?>
            <p>
                <input type="text" name="sswf_word_to_add" class="regular-text" />
                <input type="submit" value="Add to Blocklist" class="button button-primary" />
            </p>
        </form>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Word/Phrase</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $prohibited_words = get_option( 'sswf_prohibited_words', array() );
                if ( ! empty( $prohibited_words ) ) {
                    foreach ( $prohibited_words as $word ) {
                        echo '<tr>';
                        echo '<td>' . esc_html( $word ) . '</td>';
                        echo '<td>
                                <form method="post">
                                    ' . wp_nonce_field( 'sswf_save_words', 'sswf_nonce', false, false ) . '
                                    <input type="hidden" name="word_to_delete" value="' . esc_attr( $word ) . '" />
                                    <input type="submit" value="Delete" class="button button-secondary" />
                                </form>
                              </td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="2">No prohibited words or phrases added yet.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Register the settings (optional)
function sswf_register_settings() {
    register_setting( 'sswf_options_group', 'sswf_prohibited_words' );
}
add_action( 'admin_init', 'sswf_register_settings' );

// Block prohibited words in comments
function sswf_block_prohibited_words_in_comment( $comment_data ) {
    $prohibited = get_option( 'sswf_prohibited_words', array() );
    foreach ( $prohibited as $word ) {
        if ( stripos( $comment_data['comment_content'], $word ) !== false ) {
            wp_die( 'Your comment contains prohibited words or phrases.' );
        }
    }
    return $comment_data;
}
add_filter( 'preprocess_comment', 'sswf_block_prohibited_words_in_comment' );

// Add Settings link on Plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sswf_action_links' );
function sswf_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=stupid-simple-word-filter' ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
