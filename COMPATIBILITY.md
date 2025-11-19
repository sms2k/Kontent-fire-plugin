# Kontent Fire Plugin - Compatibility Guide

## Page Builder Compatibility

Kontent Fire is designed to work seamlessly with all major WordPress page builders and plugins. The auto-blogging system generates clean, semantic HTML that can be easily edited with any page builder.

### ✅ Fully Compatible Page Builders

- **Elementor** - Full compatibility with visual editing
- **Divi Builder** - Works with both Visual Builder and Classic Editor
- **Beaver Builder** - Standard content editing supported
- **WPBakery Page Builder** - Compatible with all modules
- **Gutenberg (Block Editor)** - Native WordPress editor support
- **Oxygen Builder** - Clean HTML structure compatible
- **Bricks Builder** - Full compatibility
- **Thrive Architect** - Works with all content types

### ✅ SEO Plugin Compatibility

Kontent Fire automatically integrates with popular SEO plugins:

- **Yoast SEO** - Auto-populates meta title, description, and focus keyword
- **Rank Math** - Full metadata integration
- **All in One SEO** - Automatic SEO field population
- **SEOPress** - Compatible with all features
- **The SEO Framework** - Works seamlessly

### How It Works

#### Clean HTML Generation

Kontent Fire generates standard WordPress content using:
- Semantic HTML5 markup
- WordPress `wpautop()` for proper paragraph formatting
- Standard WordPress image functions for media
- No proprietary shortcodes or markup
- Clean structure that page builders can parse

#### Page Builder Integration

When a blog post is created:

1. **Content Structure**: Uses standard `post_content` field
2. **Metadata**: Adds compatibility flags for each builder
3. **Images**: Properly attached to WordPress media library
4. **Editability**: Content can be immediately edited with any page builder

#### Elementor Specific

Generated posts are marked as Elementor-compatible:
```php
update_post_meta($post_id, '_elementor_edit_mode', 'builder');
update_post_meta($post_id, '_elementor_template_type', 'wp-post');
```

Users can:
- Click "Edit with Elementor" immediately
- Convert entire post to Elementor format
- Edit sections individually
- Add Elementor widgets to generated content

#### Divi Specific

Generated posts work with Divi:
```php
update_post_meta($post_id, '_et_pb_use_builder', 'off');
update_post_meta($post_id, '_et_pb_old_content', $content);
```

Users can:
- Use Divi Visual Builder on generated posts
- Convert to Divi format
- Mix generated content with Divi modules

### Developer Hooks & Filters

#### Modify Content Before Saving

```php
add_filter('kontent_fire_auto_blog_content', function($content, $blog_data, $images) {
    // Modify content before it's saved to WordPress
    // Example: Add custom HTML wrapper
    $content = '<div class="custom-wrapper">' . $content . '</div>';
    return $content;
}, 10, 3);
```

#### Modify Post Data

```php
add_filter('kontent_fire_auto_blog_post_data', function($post_data, $blog_data) {
    // Modify post data before creation
    // Example: Change post type to 'page'
    $post_data['post_type'] = 'page';
    return $post_data;
}, 10, 2);
```

#### After Post Creation Hook

```php
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data, $images, $keywords) {
    // Run custom code after blog is created
    // Example: Send notification, update custom fields, etc.

    // Add custom page builder metadata
    update_post_meta($post_id, '_my_builder_flag', 'yes');

    // Log creation
    error_log("Kontent Fire created post: " . $post_id);
}, 10, 4);
```

### Page Builder Conversion Examples

#### Converting to Elementor

After a blog is auto-generated, users can convert it to Elementor format:

1. Open post in WordPress editor
2. Click "Edit with Elementor"
3. Elementor will parse the HTML into editable sections
4. All content, images, and headings become Elementor blocks

#### Converting to Divi

1. Open post in WordPress editor
2. Enable "Use Divi Builder"
3. Divi will convert HTML to Divi modules
4. Content becomes fully editable in Visual Builder

### Best Practices

#### For End Users

1. **Direct Editing**: Generated posts can be edited immediately with your preferred page builder
2. **Clean Slate**: Content uses standard HTML, so no conversion issues
3. **SEO Preserved**: All SEO metadata transfers when using page builders
4. **Images**: All images are properly attached and can be resized/edited

#### For Developers

1. **Use Filters**: Leverage `kontent_fire_auto_blog_content` to customize output
2. **Add Metadata**: Use `kontent_fire_auto_blog_created` action to add builder-specific meta
3. **Custom Post Types**: Filter `kontent_fire_auto_blog_post_data` to change post type
4. **Template Integration**: Generated content respects theme templates

### Technical Details

#### Content Structure

```html
<!-- Example generated content -->
<p>Introduction paragraph with proper wpautop formatting...</p>

<h2>Section Heading with Keywords</h2>
<p>Section content with natural keyword integration...</p>

<figure class="wp-block-image aligncenter size-large">
    <img src="image-url.jpg" alt="Alt text" class="wp-image-123" loading="lazy" />
</figure>

<h2>Another Section</h2>
<p>More content...</p>

<h2>Conclusion</h2>
<p>Conclusion content with call-to-action...</p>
```

#### Image Handling

- Uses WordPress `wp_get_attachment_image()` for proper srcset and lazy loading
- Images have proper attachment IDs for page builder editing
- Includes responsive image attributes
- Featured image set automatically

#### Metadata Storage

Kontent Fire stores metadata in standard WordPress meta fields:

```php
// Kontent Fire metadata
_kf_focus_keyword           // Primary SEO keyword
_kf_keywords                // All keywords (JSON)
_kf_auto_generated          // Flag for auto-generated content
_kf_generation_time         // Timestamp
_kf_page_builder_compatible // Compatibility flag

// SEO plugin metadata (auto-populated)
_yoast_wpseo_title         // Yoast title
_yoast_wpseo_metadesc      // Yoast description
rank_math_title            // Rank Math title
_aioseo_title              // AIOSEO title
// ... and more
```

### Troubleshooting

#### Issue: Can't Edit with Page Builder

**Solution**: The post is created with standard WordPress content. Try:
1. Enable your page builder for the post
2. Convert content to builder format
3. Check if page builder is activated for 'post' post type

#### Issue: Images Not Showing in Page Builder

**Solution**:
1. Check WordPress media library for image attachments
2. Verify images were successfully generated
3. Re-attach images using page builder's image selector

#### Issue: SEO Data Not Showing

**Solution**:
1. Verify your SEO plugin is active
2. Check that metadata fields match your plugin's format
3. Kontent Fire supports Yoast, Rank Math, and AIOSEO out of the box

### Support for Custom Page Builders

If you're using a custom or less common page builder, you can add support:

```php
add_action('kontent_fire_auto_blog_created', function($post_id, $blog_data, $images, $keywords) {
    // Add your custom page builder metadata
    update_post_meta($post_id, '_your_builder_mode', 'enabled');
    update_post_meta($post_id, '_your_builder_data', json_encode($blog_data));
}, 10, 4);
```

### Compatibility Checklist

Before using Kontent Fire with your page builder:

- ✅ Page builder supports standard WordPress posts
- ✅ Page builder can parse HTML content
- ✅ WordPress media library integration works
- ✅ Post type is set to 'post' (or filter it to your custom type)
- ✅ Your theme supports standard WordPress content

### Summary

Kontent Fire is built with compatibility as a core principle:

- **Clean Code**: No proprietary markup or shortcodes
- **Standard WordPress**: Uses native WordPress functions and structures
- **Extensible**: Hooks and filters for customization
- **SEO-Friendly**: Works with all major SEO plugins
- **Page Builder Ready**: Compatible with Elementor, Divi, and all major builders
- **Developer-Friendly**: Well-documented APIs for integration

The auto-blogging system generates professional, SEO-optimized content that can be immediately edited with any WordPress page builder, ensuring maximum flexibility for all users.
