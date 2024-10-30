<?php
// Megakadályozza a közvetlen fájlhoz való hozzáférést
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: Molin AI Integration
 * Plugin URI: https://olcsoweboldal1.hu/molin-ai-wordpress-integration-plugin/
 * Description: This plugin integrates the Molin AI writing assistant into your WordPress site. It allows you to use the Molin AI directly in the post or page editor for seamless content creation. By using this plugin, you can generate AI-written content with a simple click, making your content creation process faster and more efficient.
 * Version: 1.0.2
 * Author: Sári Zoltán
 * Author URI: https://olcsoweboldal1.hu/olcso-weboldalak-keszitese-rolam-sari-zoltan/
 */

function molin_add_script() {
    // Molin AI script
    wp_enqueue_script('molin-ai', 'https://widget.molin.ai/write-with-ai.js', array(), '1.0', true);

    // Get the widget ID from the settings
    $widget_id = get_option('molin_widget_id', '');

    // Connect the widget with your app
    wp_add_inline_script('molin-ai', '
        // 1) get a reference to the molin-write-with-ai element
        const wwai = document.querySelector("molin-write-with-ai");

        // 2) add an event listener to detect when the user pastes the generated content
        wwai.addEventListener("molin:paste", onMolinPaste);

        // 3) when the user clicks "Paste", fill in the textarea with the generated content
        function onMolinPaste(event) {
            const result = event.detail;
            console.log("molin:paste", result); // optional, for debugging
            document.querySelector("textarea").value = result.text;
        }
    ');
}
add_action('admin_enqueue_scripts', 'molin_add_script');

function molin_add_meta_box() {
    add_meta_box(
        'molin_meta_box', // id
        'Molin AI', // title
        'display_molin_meta_box', // callback
        array('post', 'page', 'product'), // post types (hozzáadtuk a 'product' típust)
        'side', // context
        'high' // priority
    );
}
add_action('add_meta_boxes', 'molin_add_meta_box');

function display_molin_meta_box() {
    // Get the widget ID from the settings
    $widget_id = get_option('molin_widget_id', '');

    // Embed the Write with AI button with proper escaping
    echo '<molin-write-with-ai widget="' . esc_attr($widget_id) . '"></molin-write-with-ai>';
}

function molin_settings_page() {
    add_options_page(
        'Molin AI Settings', // page_title
        'Molin AI', // menu_title
        'manage_options', // capability
        'molin-ai', // menu_slug
        'molin_settings_page_markup' // function
    );
}
add_action('admin_menu', 'molin_settings_page');

function molin_settings_page_markup() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('molin-ai');
            do_settings_sections('molin-ai');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function molin_settings() {
    register_setting('molin-ai', 'molin_widget_id');

    add_settings_section(
        'molin_settings_section', // id
        'Settings', // title
        '', // callback
        'molin-ai' // page
    );

    add_settings_field(
        'molin_widget_id', // id
        'Widget ID', // title
        'molin_widget_id_callback', // callback
        'molin-ai', // page
        'molin_settings_section' // section
    );
}
add_action('admin_init', 'molin_settings');

function molin_widget_id_callback() {
    $widget_id = get_option('molin_widget_id', '');
    echo "<input type='text' name='molin_widget_id' value='" . esc_attr($widget_id) . "' />";
}

// Gutenberg blokk regisztrálása
function molin_ai_register_block() {
    // Blokk kódja és beállításai
    $block_code = 'molin-ai/block';
    $block_settings = array(
        'editor_script' => 'molin-ai-block-editor',
        'editor_style'  => 'molin-ai-block-editor',
        'style'         => 'molin-ai-block',
    );

    // Blokk regisztrálása
    register_block_type($block_code, $block_settings);
}
add_action('init', 'molin_ai_register_block');

// Gutenberg blokk stílusainak és scriptjeinek beillesztése
function molin_ai_enqueue_block_assets() {
    // Blokk szerkesztő script
    $block_editor_script = 'molin-ai-block-editor';
    $block_editor_script_path = plugin_dir_path(__FILE__) . 'dist/block-editor.js';
    if (file_exists($block_editor_script_path)) {
        wp_enqueue_script(
            $block_editor_script,
            plugins_url('dist/block-editor.js', __FILE__),
            array('wp-blocks', 'wp-i18n', 'wp-element'),
            filemtime($block_editor_script_path),
            true // A scriptet a lábléc helyett a testben töltjük be
        );
    }

  // Blokk szerkesztő stílus
    $block_editor_style_path = plugin_dir_path(__FILE__) . 'dist/block-editor.css';
    if (file_exists($block_editor_style_path)) {
        wp_enqueue_style(
            'molin-ai-block-editor',
            plugins_url('dist/block-editor.css', __FILE__),
            array(),
            filemtime($block_editor_style_path)
        );
    }

   // Blokk stílus
    $block_style_path = plugin_dir_path(__FILE__) . 'dist/block.css';
    if (file_exists($block_style_path)) {
        wp_enqueue_style(
            'molin-ai-block',
            plugins_url('dist/block.css', __FILE__),
            array(),
            filemtime($block_style_path)
        );
    }
}
add_action('enqueue_block_assets', 'molin_ai_enqueue_block_assets');


