<div class="vh360-ss-import-progress-content">
    <div class="vh360-ss-modal-header">
        <h2><?php esc_html_e('Importing Demo', 'videohub360-starter-sites'); ?></h2>
    </div>
    
    <div class="vh360-ss-modal-body">
        <div id="vh360-ss-progress-demo-name" class="import-demo-name"></div>
        
        <div class="import-progress-bar">
            <div id="vh360-ss-progress-bar" class="progress-bar-fill" style="width: 0%;"></div>
        </div>
        
        <div id="vh360-ss-progress-status" class="import-status">
            <span class="spinner is-active"></span>
            <p class="status-text"><?php esc_html_e('Initializing import...', 'videohub360-starter-sites'); ?></p>
        </div>
        
        <div id="vh360-ss-progress-phases" class="import-phases">
            <div class="phase-item" data-phase="validate">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Validating environment', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="download">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Downloading package', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="plugins">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Checking plugins', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="content">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Importing content', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="widgets">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Importing widgets', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="customizer">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Importing Customizer settings', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="elementor">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Importing Elementor kit', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="theme-options">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Applying theme settings', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="post-import">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Running post-import setup', 'videohub360-starter-sites'); ?></span>
            </div>
            <div class="phase-item" data-phase="complete">
                <span class="phase-icon">⋯</span>
                <span class="phase-label"><?php esc_html_e('Completing import', 'videohub360-starter-sites'); ?></span>
            </div>
        </div>
        
        <div id="vh360-ss-progress-log" class="import-log" style="display: none;">
            <h3><?php esc_html_e('Import Log', 'videohub360-starter-sites'); ?></h3>
            <div class="log-entries"></div>
        </div>
    </div>
    
    <div class="vh360-ss-modal-footer">
        <p class="description">
            <?php esc_html_e('Please do not close this window or navigate away during the import process.', 'videohub360-starter-sites'); ?>
        </p>
    </div>
</div>
