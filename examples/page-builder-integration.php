<?php
/**
 * Page Builder Integration Examples
 *
 * This file shows how to integrate Kontent Fire auto-blogging
 * with custom page builders or modify content for specific builders.
 *
 * @package Kontent_Fire
 */

// Example 1: Add custom wrapper for your theme/builder
add_filter('kontent_fire_auto_blog_content', function($content, $blog_data, $images) {
    // Wrap content in a custom container
    $wrapped = '<div class="my-theme-container">';
    $wrapped .= '<div class="content-wrapper">';
    $wrapped .= $content;
    $wrapped .= '</div>';
    $wrapped .= '</div>';

    return $wrapped;
}, 10, 3);

// Example 2: Convert content to Gutenberg blocks
add_filter('kontent_fire_auto_blog_content', function($content, $blog_data, $images) {
    // Convert standard HTML to Gutenberg block format
    $blocks = '';

    // Introduction as paragraph block
    $blocks .= '<!-- wp:paragraph -->';
    $blocks .= '<p>' . $blog_data['introduction'] . '</p>';
    $blocks .= '<!-- /wp:paragraph -->' . "\n\n";

    foreach ($blog_data['sections'] as $index => $section) {
        // Heading block
        $blocks .= '<!-- wp:heading -->';
        $blocks .= '<h2>' . $section['heading'] . '</h2>';
        $blocks .= '<!-- /wp:heading -->' . "\n\n";

        // Content block
        $blocks .= '<!-- wp:paragraph -->';
        $blocks .= '<p>' . $section['content'] . '</p>';
        $blocks .= '<!-- /wp:paragraph -->' . "\n\n";

        // Image block (after certain sections)
        if (($index === 0 || $index === 2) && isset($images[$index])) {
            $attachment_id = attachment_url_to_postid($images[$index]);
            if ($attachment_id) {
                $blocks .= '<!-- wp:image {"id":' . $attachment_id . ',"align":"center"} -->';
                $blocks .= '<figure class="wp-block-image aligncenter">';
                $blocks .= '<img src="' . esc_url($images[$index]) . '" alt="' . esc_attr($section['heading']) . '" class="wp-image-' . $attachment_id . '"/>';
                $blocks .= '</figure>';
                $blocks .= '<!-- /wp:image -->' . "\n\n";
            }
        }
    }

    // Conclusion
    $blocks .= '<!-- wp:heading -->';
    $blocks .= '<h2>Conclusion</h2>';
    $blocks .= '<!-- /wp:heading -->' . "\n\n";
    $blocks .= '<!-- wp:paragraph -->';
    $blocks .= '<p>' . $blog_data['conclusion'] . '</p>';
    $blocks .= '<!-- /wp:paragraph -->';

    return $blocks;
}, 10, 3);

// Example 3: Change post type to 'page' for page builder templates
add_filter('kontent_fire_auto_blog_post_data', function($post_data, $blog_data) {
    // Create as page instead of post
    $post_data['post_type'] = 'page';

    // Optionally set a specific page template
    $post_data['meta_input']['_wp_page_template'] = 'template-fullwidth.php';

    return $post_data;
}, 10, 2);

// Example 4: Add custom page builder metadata after creation
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data, $images, $keywords) {

    // Example: Oxygen Builder integration
    update_post_meta($post_id, 'ct_builder_shortcodes', ''); // Empty for standard content
    update_post_meta($post_id, 'ct_builder_json', ''); // Can be converted later

    // Example: Custom builder metadata
    update_post_meta($post_id, '_custom_builder_enabled', 'yes');
    update_post_meta($post_id, '_custom_builder_version', '1.0');

    // Example: Store original data for future editing
    update_post_meta($post_id, '_kf_original_blog_data', json_encode($blog_data));

}, 10, 4);

// Example 5: Disable auto-promotion for specific posts
add_action('kontent_fire_auto_blog_created', function($post_id) {
    // Prevent auto-promotion on social media for this post
    update_post_meta($post_id, '_kf_skip_auto_promotion', true);
}, 10, 1);

// Example 6: Add custom CSS classes to generated content
add_filter('kontent_fire_auto_blog_content', function($content) {
    // Add custom classes to all headings
    $content = preg_replace('/<h2>/', '<h2 class="my-custom-heading">', $content);

    // Add custom classes to paragraphs
    $content = preg_replace('/<p>/', '<p class="my-custom-paragraph">', $content);

    return $content;
}, 10, 1);

// Example 7: Elementor Pro integration - Create custom template
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data) {

    // Tell Elementor to use a specific template for this post
    update_post_meta($post_id, '_elementor_template_type', 'wp-post');

    // Optional: Apply a specific Elementor template
    // $template_id = 123; // Your Elementor template ID
    // update_post_meta($post_id, '_elementor_page_settings', array(
    //     'template' => $template_id
    // ));

}, 10, 2);

// Example 8: Divi Builder - Convert to Divi modules
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data, $images) {

    // Enable Divi Builder for this post
    update_post_meta($post_id, '_et_pb_use_builder', 'on');

    // Create basic Divi shortcode structure
    $divi_content = '';
    $divi_content .= '[et_pb_section][et_pb_row]';
    $divi_content .= '[et_pb_column type="4_4"]';

    // Introduction text module
    $divi_content .= '[et_pb_text]' . $blog_data['introduction'] . '[/et_pb_text]';

    // Add sections
    foreach ($blog_data['sections'] as $section) {
        $divi_content .= '[et_pb_text]<h2>' . $section['heading'] . '</h2>' . $section['content'] . '[/et_pb_text]';
    }

    // Conclusion
    $divi_content .= '[et_pb_text]<h2>Conclusion</h2>' . $blog_data['conclusion'] . '[/et_pb_text]';

    $divi_content .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

    // Update post content with Divi shortcodes
    // Note: Uncomment if you want to use Divi format by default
    // wp_update_post(array(
    //     'ID' => $post_id,
    //     'post_content' => $divi_content
    // ));

}, 10, 3);

// Example 9: Add schema markup for SEO
add_filter('kontent_fire_auto_blog_content', function($content, $blog_data) {

    // Add JSON-LD schema markup for article
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $blog_data['title'],
        'description' => $blog_data['meta_description'],
        'keywords' => implode(', ', $blog_data['tags']),
        'datePublished' => current_time('c'),
        'author' => array(
            '@type' => 'Person',
            'name' => get_bloginfo('name')
        )
    );

    $schema_markup = '<script type="application/ld+json">' . json_encode($schema) . '</script>';

    return $schema_markup . "\n" . $content;
}, 10, 2);

// Example 10: Create custom table of contents
add_filter('kontent_fire_auto_blog_content', function($content, $blog_data) {

    // Build table of contents from sections
    $toc = '<div class="table-of-contents">';
    $toc .= '<h3>Table of Contents</h3>';
    $toc .= '<ul>';

    foreach ($blog_data['sections'] as $index => $section) {
        $slug = sanitize_title($section['heading']);
        $toc .= '<li><a href="#' . $slug . '">' . $section['heading'] . '</a></li>';
    }

    $toc .= '<li><a href="#conclusion">Conclusion</a></li>';
    $toc .= '</ul>';
    $toc .= '</div>';

    // Add IDs to headings for anchor links
    $content = preg_replace_callback('/<h2>(.*?)<\/h2>/', function($matches) {
        $slug = sanitize_title($matches[1]);
        return '<h2 id="' . $slug . '">' . $matches[1] . '</h2>';
    }, $content);

    // Insert TOC after introduction
    $parts = explode('</p>', $content, 2);
    if (count($parts) === 2) {
        $content = $parts[0] . '</p>' . "\n" . $toc . "\n" . $parts[1];
    }

    return $content;
}, 10, 2);

// Example 11: Custom post category based on keywords
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data, $images, $keywords) {

    // Auto-categorize based on primary keyword
    $primary_keyword = $keywords['primary'][0] ?? '';

    if (stripos($primary_keyword, 'marketing') !== false) {
        wp_set_post_categories($post_id, array(get_cat_ID('Marketing')));
    } elseif (stripos($primary_keyword, 'seo') !== false) {
        wp_set_post_categories($post_id, array(get_cat_ID('SEO')));
    } elseif (stripos($primary_keyword, 'web design') !== false) {
        wp_set_post_categories($post_id, array(get_cat_ID('Web Design')));
    }

}, 10, 4);

// Example 12: Notification on blog creation
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data) {

    // Send email notification to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Blog Post Auto-Generated: ' . $blog_data['title'];
    $message = "A new blog post has been automatically generated:\n\n";
    $message .= "Title: " . $blog_data['title'] . "\n";
    $message .= "URL: " . get_permalink($post_id) . "\n\n";
    $message .= "Keywords: " . $blog_data['focus_keyword'] . "\n";

    wp_mail($admin_email, $subject, $message);

    // Optional: Slack notification, webhook, etc.

}, 10, 2);
