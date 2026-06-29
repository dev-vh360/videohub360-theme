<?php
/**
 * Urgent Bulletin Banner
 *
 * Sticky banner at top of site for urgent priority bulletins.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$urgent_bulletins = vh360_get_urgent_bulletins();

if (empty($urgent_bulletins)) {
    return;
}

// Only show the first urgent bulletin
$bulletin = $urgent_bulletins[0];
$bulletin_id = $bulletin->ID;
$dismissible = get_post_meta($bulletin_id, '_vh360_bulletin_dismissible', true);

?>

<div class="vh360-bulletin-banner" data-bulletin-id="<?php echo esc_attr($bulletin_id); ?>">
    <div class="vh360-bulletin-banner-container">
        
        <div class="vh360-bulletin-banner-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        
        <div class="vh360-bulletin-banner-content">
            <div class="vh360-bulletin-banner-badge">
                <?php esc_html_e('Urgent', 'videohub360-theme'); ?>
            </div>
            <h4 class="vh360-bulletin-banner-title">
                <?php echo esc_html(get_the_title($bulletin_id)); ?>
            </h4>
            <p class="vh360-bulletin-banner-excerpt">
                <?php 
                $excerpt = get_the_excerpt($bulletin_id);
                if ($excerpt) {
                    echo esc_html(wp_trim_words($excerpt, 15));
                }
                ?>
            </p>
        </div>
        
        <div class="vh360-bulletin-banner-actions">
            <a href="<?php echo esc_url(get_permalink($bulletin_id)); ?>" 
               class="vh360-bulletin-banner-link">
                <?php esc_html_e('Read More', 'videohub360-theme'); ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
            
            <?php if ($dismissible) : ?>
                <button class="vh360-bulletin-banner-close vh360-bulletin-dismiss" 
                        title="<?php esc_attr_e('Dismiss', 'videohub360-theme'); ?>"
                        aria-label="<?php esc_attr_e('Dismiss urgent bulletin', 'videohub360-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
/* Urgent Bulletin Banner Styles */
.vh360-bulletin-banner {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.vh360-bulletin-banner-container {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
    padding: 1rem 2rem;
}

.vh360-bulletin-banner-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

.vh360-bulletin-banner-content {
    flex: 1;
    min-width: 0;
}

.vh360-bulletin-banner-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.vh360-bulletin-banner-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
    line-height: 1.3;
}

.vh360-bulletin-banner-excerpt {
    font-size: 0.875rem;
    margin: 0;
    opacity: 0.95;
    line-height: 1.4;
}

.vh360-bulletin-banner-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.vh360-bulletin-banner-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.5rem;
    background: var(--surface-1, #ffffff);
    color: #dc2626;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}

.vh360-bulletin-banner-link:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.vh360-bulletin-banner-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.vh360-bulletin-banner-close:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    transform: rotate(90deg);
}

/* Responsive */
@media (max-width: 768px) {
    .vh360-bulletin-banner-container {
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem;
    }
    
    .vh360-bulletin-banner-icon {
        width: 40px;
        height: 40px;
    }
    
    .vh360-bulletin-banner-title {
        font-size: 1rem;
    }
    
    .vh360-bulletin-banner-excerpt {
        font-size: 0.8125rem;
    }
    
    .vh360-bulletin-banner-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .vh360-bulletin-banner-link {
        flex: 1;
        justify-content: center;
    }
}
</style>
