<?php if (!defined('WPINC')) die; ?>
<div class="wrap">
    <h1>Social Media Platforms</h1>
    <p>Connect and manage your social media accounts.</p>

    <div class="kf-platforms-grid">
        <?php foreach ($platforms as $platform): ?>
        <div class="kf-platform-card">
            <h3><?php echo esc_html(ucfirst($platform)); ?></h3>
            <button class="button button-primary connect-platform" data-platform="<?php echo esc_attr($platform); ?>">
                Connect <?php echo esc_html(ucfirst($platform)); ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
