<div class="vh360-ss-import-complete-content">
    <div class="vh360-ss-modal-header">
        <div id="vh360-ss-complete-icon" class="complete-icon">
            <!-- Will be filled by JavaScript with success/error icon -->
        </div>
        <h2 id="vh360-ss-complete-title"></h2>
    </div>
    
    <div class="vh360-ss-modal-body">
        <div id="vh360-ss-complete-message" class="complete-message"></div>
        
        <div id="vh360-ss-complete-stats" class="complete-stats" style="display: none;">
            <h3><?php esc_html_e('Import Summary', 'videohub360-starter-sites'); ?></h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Duration:', 'videohub360-starter-sites'); ?></span>
                    <span id="vh360-ss-stat-duration" class="stat-value"></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Demo:', 'videohub360-starter-sites'); ?></span>
                    <span id="vh360-ss-stat-demo" class="stat-value"></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Errors:', 'videohub360-starter-sites'); ?></span>
                    <span id="vh360-ss-stat-errors" class="stat-value"></span>
                </div>
            </div>
        </div>
        
        <div id="vh360-ss-complete-issues" class="complete-issues" style="display: none;">
            <h3><?php esc_html_e('Issues Found', 'videohub360-starter-sites'); ?></h3>
            <ul id="vh360-ss-issues-list"></ul>
        </div>
        
        <div id="vh360-ss-complete-log" class="complete-log" style="display: none;">
            <h3><?php esc_html_e('Import Log', 'videohub360-starter-sites'); ?></h3>
            <div class="log-entries"></div>
        </div>
    </div>
    
    <div class="vh360-ss-modal-footer">
        <button type="button" id="vh360-ss-complete-close" class="button button-primary">
            <?php esc_html_e('Close', 'videohub360-starter-sites'); ?>
        </button>
        <button type="button" id="vh360-ss-complete-view-site" class="button" style="display: none;">
            <?php esc_html_e('View Site', 'videohub360-starter-sites'); ?>
        </button>
        <button type="button" id="vh360-ss-complete-view-log" class="button">
            <?php esc_html_e('View Full Log', 'videohub360-starter-sites'); ?>
        </button>
    </div>
</div>
