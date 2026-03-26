<div class="vh360-ss-demo-card" data-demo-id="{{id}}">
    <div class="demo-thumbnail">
        <img src="{{thumbnail}}" alt="{{name}}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'rgba(0,0,0,0.5)\' font-family=\'sans-serif\' font-size=\'24\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E{{name}}%3C/text%3E%3C/svg%3E'">
        {{#if preview_url}}
        <a href="{{preview_url}}" target="_blank" class="demo-preview-btn" title="<?php esc_attr_e('Preview Demo', 'videohub360-starter-sites'); ?>">
            <span class="dashicons dashicons-visibility"></span>
        </a>
        {{/if}}
    </div>
    <div class="demo-info">
        <h3 class="demo-name">{{name}}</h3>
        {{#if label}}
        <span class="demo-label">{{label}}</span>
        {{/if}}
        {{#if description}}
        <p class="demo-description">{{description}}</p>
        {{/if}}
        <div class="demo-meta">
            <span class="demo-version">v{{version}}</span>
            {{#if category}}
            <span class="demo-category">{{category}}</span>
            {{/if}}
        </div>
        {{#if required_plugins.length}}
        <div class="demo-requirements">
            <strong><?php esc_html_e('Required Plugins:', 'videohub360-starter-sites'); ?></strong>
            <ul>
                {{#each required_plugins}}
                <li>{{this}}</li>
                {{/each}}
            </ul>
        </div>
        {{/if}}
    </div>
    <div class="demo-actions">
        <button type="button" class="button button-primary vh360-ss-import-btn" data-demo-id="{{id}}">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Import Demo', 'videohub360-starter-sites'); ?>
        </button>
    </div>
</div>
