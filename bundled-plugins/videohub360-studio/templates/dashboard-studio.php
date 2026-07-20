<?php
/**
 * Studio dashboard tab template.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$default_label     = isset( $quality_presets[ $default_preset ]['label'] ) ? $quality_presets[ $default_preset ]['label'] : $default_preset;
$is_admin          = current_user_can( 'manage_options' );
$allowed_overlay_modules  = isset( $allowed_overlay_modules ) ? $allowed_overlay_modules : VH360_Studio_User_Preferences::allowed_overlay_modules();
$enabled_overlay_modules = isset( $enabled_overlay_modules ) ? $enabled_overlay_modules : array();
$enabled_overlay_modules = VH360_Studio_User_Preferences::sanitize_overlay_modules( $enabled_overlay_modules );
$active_overlay_module   = ! empty( $enabled_overlay_modules ) ? $enabled_overlay_modules[0] : null;
$overlay_tool_labels     = array(
    'lower-thirds' => __( 'Lower Thirds', 'videohub360-studio' ),
    'bible'        => __( 'Bible', 'videohub360-studio' ),
    'countdown'    => __( 'Countdown', 'videohub360-studio' ),
);
$overlay_tool_descriptions = array(
    'lower-thirds' => __( 'Names, titles, and speaker information.', 'videohub360-studio' ),
    'bible'        => __( 'Scripture passages from the Bible Library.', 'videohub360-studio' ),
    'countdown'    => __( 'Timers and event countdowns.', 'videohub360-studio' ),
);
?>
<section class="vh360-studio-dashboard" data-vh360-studio-dashboard>
    <header class="vh360-studio-topbar">
        <div>
            <p class="vh360-studio-kicker"><?php esc_html_e( 'Production console', 'videohub360-studio' ); ?></p>
            <h2><?php echo esc_html( function_exists( 'vh360_studio_get_display_name' ) ? vh360_studio_get_display_name() : __( 'Studio', 'videohub360-studio' ) ); ?></h2>
        </div>
        <div class="vh360-studio-screen-reader-status" aria-live="polite" data-studio-status>
            <?php esc_html_e( 'Checking browser support…', 'videohub360-studio' ); ?>
        </div>
        <div
            class="vh360-studio-on-air-notice"
            data-on-air-notice
            role="status"
            aria-live="polite"
            hidden
        >
            <span class="vh360-studio-on-air-notice__message">
                <?php esc_html_e( 'For best results, keep this Studio tab open and visible while broadcasting or recording.', 'videohub360-studio' ); ?>
            </span>
            <button
                type="button"
                class="vh360-studio-on-air-notice__dismiss"
                data-dismiss-on-air-notice
                aria-label="<?php esc_attr_e( 'Dismiss Studio visibility reminder', 'videohub360-studio' ); ?>"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="vh360-studio-topbar-links" aria-live="polite">
            <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'studio', 'studio_mode' => 'mobile' ), remove_query_arg( 'studio_mode' ) ) ); ?>" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-studio-mode-choice="mobile"><?php esc_html_e( 'Open Mobile Live', 'videohub360-studio' ); ?></a>
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact vh360-studio-attention-button" data-open-studio-diagnostics hidden>
                <?php esc_html_e( 'Studio needs attention', 'videohub360-studio' ); ?>
            </button>
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-studio-fullscreen>
                <?php esc_html_e( 'Fullscreen Studio', 'videohub360-studio' ); ?>
            </button>
            <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-open-studio-window>
                <?php esc_html_e( 'Open Studio Window', 'videohub360-studio' ); ?>
            </button>

            <div class="vh360-studio-viewer-actions" data-viewer-link-wrap hidden>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-open-viewer-link disabled>
                    <?php esc_html_e( 'Open Viewer', 'videohub360-studio' ); ?>
                </button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-copy-viewer-link>
                    <?php esc_html_e( 'Copy Link', 'videohub360-studio' ); ?>
                </button>
                <span class="vh360-studio-copy-feedback" data-copy-viewer-feedback hidden></span>
            </div>
        </div>
    </header>

    <div class="vh360-studio-workbench">
        <div class="vh360-studio-production-workspace" data-overlays-workspace>
            <div class="vh360-studio-monitors">
            <section class="vh360-studio-monitor vh360-studio-monitor--preview" aria-labelledby="vh360-studio-preview-title">
                <div class="vh360-studio-monitor-header">
                    <h3 id="vh360-studio-preview-title"><?php esc_html_e( 'Preview', 'videohub360-studio' ); ?></h3>
                    <button
                        type="button"
                        class="vh360-studio-monitor-action"
                        data-toggle-media-controls
                        aria-expanded="false"
                        aria-controls="vh360-studio-selected-media-controls"
                        hidden
                    >
                        <?php esc_html_e( 'Media Controls', 'videohub360-studio' ); ?>
                    </button>
                </div>
                <div class="vh360-studio-monitor-screen vh360-studio-preview-stage">
                    <div class="vh360-studio-preview-camera" data-camera-preview-container aria-label="<?php esc_attr_e( 'Local camera preview', 'videohub360-studio' ); ?>"></div>
                    <video class="vh360-studio-preview-video vh360-studio-preview-video--screen" data-screen-preview playsinline muted aria-label="<?php esc_attr_e( 'Screen-share preview', 'videohub360-studio' ); ?>"></video>
                    <div class="vh360-studio-preview-media" data-media-preview aria-label="<?php esc_attr_e( 'Media source preview', 'videohub360-studio' ); ?>"></div>
                    <canvas class="vh360-studio-preview-overlay-canvas" data-preview-overlay-canvas aria-hidden="true"></canvas>
                    <span class="vh360-studio-monitor-empty" data-preview-empty><?php esc_html_e( 'Choose a Scene to stage it in Preview.', 'videohub360-studio' ); ?></span>
                </div>
                <div class="vh360-studio-media-playback" data-media-playback-controls hidden>
                    <button type="button" class="vh360-studio-icon-button" data-media-play-pause aria-label="<?php esc_attr_e( 'Play media', 'videohub360-studio' ); ?>">
                        <span data-media-play-pause-label><?php esc_html_e( 'Play', 'videohub360-studio' ); ?></span>
                    </button>

                    <button type="button" class="vh360-studio-icon-button" data-media-restart aria-label="<?php esc_attr_e( 'Restart media', 'videohub360-studio' ); ?>">
                        <?php esc_html_e( 'Restart', 'videohub360-studio' ); ?>
                    </button>

                    <label class="vh360-studio-media-loop">
                        <input type="checkbox" data-media-loop checked>
                        <?php esc_html_e( 'Loop', 'videohub360-studio' ); ?>
                    </label>

                    <input type="range" min="0" max="1000" value="0" step="1" data-media-seek aria-label="<?php esc_attr_e( 'Media timeline', 'videohub360-studio' ); ?>">

                    <span class="vh360-studio-media-time" data-media-time>00:00 / 00:00</span>
                </div>
                <div
                    id="vh360-studio-selected-media-controls"
                    class="vh360-studio-selected-media-controls"
                    data-selected-media-controls
                    hidden
                >
                    <h4><?php esc_html_e( 'Selected Media', 'videohub360-studio' ); ?></h4>
                    <p class="vh360-studio-selected-media-name" data-selected-media-name></p>

                    <label>
                        <?php esc_html_e( 'Fit mode', 'videohub360-studio' ); ?>
                        <select data-media-fit-mode>
                            <option value="fit"><?php esc_html_e( 'Fit to canvas', 'videohub360-studio' ); ?></option>
                            <option value="fill"><?php esc_html_e( 'Fill / crop', 'videohub360-studio' ); ?></option>
                            <option value="stretch"><?php esc_html_e( 'Stretch', 'videohub360-studio' ); ?></option>
                            <option value="original"><?php esc_html_e( 'Original size', 'videohub360-studio' ); ?></option>
                            <option value="custom"><?php esc_html_e( 'Custom', 'videohub360-studio' ); ?></option>
                        </select>
                    </label>

                    <label>
                        <?php esc_html_e( 'Scale', 'videohub360-studio' ); ?>
                        <input type="range" min="10" max="300" step="1" value="100" data-media-scale>
                        <span data-media-scale-value>100%</span>
                    </label>

                    <label>
                        <?php esc_html_e( 'Position X', 'videohub360-studio' ); ?>
                        <input type="range" min="-100" max="100" step="1" value="0" data-media-position-x>
                        <span data-media-position-x-value>0</span>
                    </label>

                    <label>
                        <?php esc_html_e( 'Position Y', 'videohub360-studio' ); ?>
                        <input type="range" min="-100" max="100" step="1" value="0" data-media-position-y>
                        <span data-media-position-y-value>0</span>
                    </label>

                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-media-reset-transform>
                        <?php esc_html_e( 'Reset media transform', 'videohub360-studio' ); ?>
                    </button>

                    <p class="vh360-studio-help" data-program-resolution-details></p>
                </div>
            </section>

            <section class="vh360-studio-transition-panel" aria-labelledby="vh360-studio-transition-title">
                <h3 id="vh360-studio-transition-title"><?php esc_html_e( 'Transition', 'videohub360-studio' ); ?></h3>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-transition-cut><?php esc_html_e( 'Cut', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary vh360-studio-button--compact" data-transition-fade><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></button>
                <label class="vh360-studio-transition-duration"><?php esc_html_e( 'Duration', 'videohub360-studio' ); ?><input type="number" min="0" max="2000" step="50" value="300" data-transition-duration></label>
                <p><?php esc_html_e( 'Stage a source in Preview, then send it to Program.', 'videohub360-studio' ); ?></p>
            </section>

            <section class="vh360-studio-monitor vh360-studio-monitor--program" aria-labelledby="vh360-studio-program-title">
                <div class="vh360-studio-monitor-header">
                    <h3 id="vh360-studio-program-title"><?php esc_html_e( 'Program', 'videohub360-studio' ); ?></h3>
                    <span class="vh360-studio-pill"><?php esc_html_e( 'Program output', 'videohub360-studio' ); ?></span>
                        <button type="button" class="vh360-studio-monitor-action" data-clear-program-overlays hidden><?php esc_html_e( 'Clear Overlays', 'videohub360-studio' ); ?></button>
                </div>
                <div class="vh360-studio-monitor-screen">
                    <canvas class="vh360-studio-program-canvas" data-program-canvas aria-label="<?php esc_attr_e( 'Program output preview', 'videohub360-studio' ); ?>"></canvas>
                    <div data-agora-local-preview class="vh360-studio-agora-preview"></div>
                    <span class="vh360-studio-monitor-empty" data-program-empty><?php esc_html_e( 'Choose a Preview source, then use Cut or Fade to send it to Program.', 'videohub360-studio' ); ?></span>
                </div>
            </section>
            </div>

            <div
                class="vh360-studio-overlays-resizer"
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-label="<?php esc_attr_e( 'Resize Overlays workspace', 'videohub360-studio' ); ?>"
                aria-valuemin="320"
                aria-valuemax="520"
                aria-valuenow="400"
                data-overlays-resizer
            ></div>

            <aside
                class="vh360-studio-overlays-dock"
                aria-labelledby="vh360-studio-overlays-title"
                data-overlays-dock
                data-label-expand="<?php esc_attr_e( 'Expand', 'videohub360-studio' ); ?>"
                data-label-collapse="<?php esc_attr_e( 'Collapse', 'videohub360-studio' ); ?>"
                data-message-collapsed="<?php esc_attr_e( 'Overlays collapsed.', 'videohub360-studio' ); ?>"
                data-message-expanded="<?php esc_attr_e( 'Overlays expanded.', 'videohub360-studio' ); ?>"
                data-message-module-change="<?php esc_attr_e( 'Overlay module changed to %s.', 'videohub360-studio' ); ?>"
                data-message-section-change="<?php esc_attr_e( 'Overlay section changed to %s.', 'videohub360-studio' ); ?>"
            >
                <header class="vh360-studio-overlays-header">
                    <div>
                        <p class="vh360-studio-overlays-kicker"><?php esc_html_e( 'Workspace', 'videohub360-studio' ); ?></p>
                        <h3 id="vh360-studio-overlays-title"><?php esc_html_e( 'Overlays', 'videohub360-studio' ); ?></h3>
                    </div>
                    <div class="vh360-studio-overlays-header-actions">
                        <button type="button" class="vh360-studio-overlays-tools" data-open-overlay-tools><?php esc_html_e( 'Overlay Tools', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-overlays-collapse" aria-expanded="true" aria-controls="vh360-studio-overlays-body" data-overlays-collapse>
                            <span data-overlays-collapse-label><?php esc_html_e( 'Collapse', 'videohub360-studio' ); ?></span>
                        </button>
                    </div>
                </header>

                <div id="vh360-studio-overlays-body" class="vh360-studio-overlays-body" data-overlays-body>
                    <nav class="vh360-studio-overlays-section-tabs" <?php echo empty( $enabled_overlay_modules ) ? 'hidden' : ''; ?> role="tablist" aria-label="<?php esc_attr_e( 'Overlay section', 'videohub360-studio' ); ?>" data-overlays-section-tabs>
                        <?php foreach ( array( 'control' => __( 'Control', 'videohub360-studio' ), 'customize' => __( 'Customize', 'videohub360-studio' ), 'settings' => __( 'Settings', 'videohub360-studio' ) ) as $section => $section_label ) : ?>
                            <?php $section_selected = 'control' === $section; ?>
                            <button type="button" role="tab" id="vh360-overlays-section-<?php echo esc_attr( $section ); ?>" aria-selected="<?php echo $section_selected ? 'true' : 'false'; ?>" <?php echo $active_overlay_module ? 'aria-controls="' . esc_attr( 'vh360-overlays-panel-' . $active_overlay_module . '-' . $section ) . '"' : ''; ?> data-overlays-section-tab data-section="<?php echo esc_attr( $section ); ?>" tabindex="<?php echo $section_selected ? '0' : '-1'; ?>"><?php echo esc_html( $section_label ); ?></button>
                        <?php endforeach; ?>
                    </nav>

                    <div class="vh360-studio-overlays-content" data-overlays-content>
                        <div class="vh360-studio-overlays-empty" data-overlays-empty <?php echo empty( $enabled_overlay_modules ) ? '' : 'hidden'; ?>>
                            <div><h4><?php esc_html_e( 'No overlay tools enabled', 'videohub360-studio' ); ?></h4><p><?php esc_html_e( 'Choose the overlay tools you want available in this workspace.', 'videohub360-studio' ); ?></p><button type="button" class="vh360-studio-button" data-open-overlay-tools><?php esc_html_e( 'Choose Overlay Tools', 'videohub360-studio' ); ?></button></div>
                        </div>
                        <section id="vh360-overlays-panel-lower-thirds-control" class="vh360-studio-lower-thirds-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-lower-thirds vh360-overlays-section-control" data-overlays-panel data-module="lower-thirds" data-section="control" <?php echo ( 'lower-thirds' === $active_overlay_module && 'control' === 'control' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-lower-thirds-stack">
                                <label for="vh360-lower-third-preset"><?php esc_html_e( 'Saved preset', 'videohub360-studio' ); ?></label>
                                <select id="vh360-lower-third-preset" data-lt-preset-select>
                                    <option value=""><?php esc_html_e( 'Unsaved lower third', 'videohub360-studio' ); ?></option>
                                </select>
                                <label for="vh360-lower-third-primary"><?php esc_html_e( 'Primary text', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-primary" type="text" maxlength="120" data-lt-primary>
                                <label for="vh360-lower-third-secondary"><?php esc_html_e( 'Secondary text', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-secondary" type="text" maxlength="160" data-lt-secondary>
                                <div class="vh360-studio-lower-thirds-status-grid">
                                    <p><strong><?php esc_html_e( 'Preview', 'videohub360-studio' ); ?></strong><span data-lt-preview-status><?php esc_html_e( 'Not staged', 'videohub360-studio' ); ?></span></p>
                                    <p><strong><?php esc_html_e( 'Program', 'videohub360-studio' ); ?></strong><span data-lt-program-status><?php esc_html_e( 'Not live', 'videohub360-studio' ); ?></span></p>
                                </div>
                                <div class="vh360-studio-lower-thirds-actions">
                                    <button type="button" class="vh360-studio-button" data-lt-stage><?php esc_html_e( 'Stage in Preview', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-lt-clear-preview><?php esc_html_e( 'Clear Preview', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button" data-lt-take><?php esc_html_e( 'Take to Program', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-lt-update-program><?php esc_html_e( 'Update Program', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-lt-hide><?php esc_html_e( 'Hide from Program', 'videohub360-studio' ); ?></button>
                                </div>
                                <p class="vh360-studio-lower-thirds-message" role="status" aria-live="polite" data-lt-status></p>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-lower-thirds-customize" class="vh360-studio-lower-thirds-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-lower-thirds vh360-overlays-section-customize" data-overlays-panel data-module="lower-thirds" data-section="customize" <?php echo ( 'lower-thirds' === $active_overlay_module && 'control' === 'customize' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-lower-thirds-stack">
                                <label for="vh360-lower-third-template"><?php esc_html_e( 'Template', 'videohub360-studio' ); ?></label>
                                <select id="vh360-lower-third-template" data-lt-template>
                                    <option value="accent_bar"><?php esc_html_e( 'Accent Bar', 'videohub360-studio' ); ?></option>
                                    <option value="solid_band"><?php esc_html_e( 'Solid Band', 'videohub360-studio' ); ?></option>
                                    <option value="minimal"><?php esc_html_e( 'Minimal', 'videohub360-studio' ); ?></option>
                                </select>
                                <label for="vh360-lower-third-position"><?php esc_html_e( 'Position', 'videohub360-studio' ); ?></label>
                                <select id="vh360-lower-third-position" data-lt-position>
                                    <option value="bottom_left"><?php esc_html_e( 'Bottom left', 'videohub360-studio' ); ?></option>
                                    <option value="bottom_center"><?php esc_html_e( 'Bottom center', 'videohub360-studio' ); ?></option>
                                    <option value="bottom_right"><?php esc_html_e( 'Bottom right', 'videohub360-studio' ); ?></option>
                                </select>
                                <label for="vh360-lower-third-scale"><?php esc_html_e( 'Scale', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-scale" type="range" min="75" max="140" value="100" data-lt-scale>
                                <label for="vh360-lower-third-accent"><?php esc_html_e( 'Accent color', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-accent" type="color" value="#4f46e5" data-lt-accent-color>
                                <label for="vh360-lower-third-bg"><?php esc_html_e( 'Background color', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-bg" type="color" value="#0f172a" data-lt-background-color>
                                <label for="vh360-lower-third-bg-opacity"><?php esc_html_e( 'Background opacity', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-bg-opacity" type="range" min="0" max="100" value="90" data-lt-background-opacity>
                                <label for="vh360-lower-third-primary-color"><?php esc_html_e( 'Primary text color', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-primary-color" type="color" value="#ffffff" data-lt-primary-color>
                                <label for="vh360-lower-third-secondary-color"><?php esc_html_e( 'Secondary text color', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-secondary-color" type="color" value="#dbeafe" data-lt-secondary-color>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-lower-thirds-settings" class="vh360-studio-lower-thirds-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-lower-thirds vh360-overlays-section-settings" data-overlays-panel data-module="lower-thirds" data-section="settings" <?php echo ( 'lower-thirds' === $active_overlay_module && 'control' === 'settings' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-lower-thirds-stack">
                                <label for="vh360-lower-third-name"><?php esc_html_e( 'Preset name', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-name" type="text" maxlength="120" data-lt-name>
                                <label for="vh360-lower-third-entrance"><?php esc_html_e( 'Entrance animation', 'videohub360-studio' ); ?></label>
                                <select id="vh360-lower-third-entrance" data-lt-entrance>
                                    <option value="slide_left"><?php esc_html_e( 'Slide from left', 'videohub360-studio' ); ?></option>
                                    <option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option>
                                    <option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option>
                                </select>
                                <label for="vh360-lower-third-exit"><?php esc_html_e( 'Exit animation', 'videohub360-studio' ); ?></label>
                                <select id="vh360-lower-third-exit" data-lt-exit>
                                    <option value="slide_left"><?php esc_html_e( 'Slide to left', 'videohub360-studio' ); ?></option>
                                    <option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option>
                                    <option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option>
                                </select>
                                <label for="vh360-lower-third-duration"><?php esc_html_e( 'Animation duration', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-duration" type="number" min="0" max="2000" step="50" value="300" data-lt-duration>
                                <label for="vh360-lower-third-auto-hide"><?php esc_html_e( 'Auto-hide duration', 'videohub360-studio' ); ?></label>
                                <input id="vh360-lower-third-auto-hide" type="number" min="0" max="300" step="1" value="0" data-lt-auto-hide>
                                <div class="vh360-studio-lower-thirds-actions">
                                    <button type="button" class="vh360-studio-button" data-lt-save><?php esc_html_e( 'Save preset', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-lt-save-new><?php esc_html_e( 'Save as new', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-lt-duplicate><?php esc_html_e( 'Duplicate', 'videohub360-studio' ); ?></button>
                                    <button type="button" class="vh360-studio-button vh360-studio-button--danger" data-lt-delete><?php esc_html_e( 'Delete', 'videohub360-studio' ); ?></button>
                                </div>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-bible-control" class="vh360-studio-bible-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-bible vh360-overlays-section-control" data-overlays-panel data-module="bible" data-section="control" <?php echo ( 'bible' === $active_overlay_module && 'control' === 'control' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-bible-stack">
                                <label for="vh360-bible-cue"><?php esc_html_e( 'Saved Scripture cue', 'videohub360-studio' ); ?></label><select id="vh360-bible-cue" data-bible-cue><option value=""><?php esc_html_e( 'Unsaved Scripture cue', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-bible-translation"><?php esc_html_e( 'Translation', 'videohub360-studio' ); ?></label><select id="vh360-bible-translation" data-bible-translation></select>
                                <label for="vh360-bible-reference"><?php esc_html_e( 'Reference', 'videohub360-studio' ); ?></label><input id="vh360-bible-reference" type="text" placeholder="John 3:16–18" data-bible-reference><button type="button" class="vh360-studio-button" data-bible-load-reference><?php esc_html_e( 'Load Reference', 'videohub360-studio' ); ?></button>
                                <label for="vh360-bible-book"><?php esc_html_e( 'Book', 'videohub360-studio' ); ?></label><select id="vh360-bible-book" data-bible-book></select><label for="vh360-bible-chapter"><?php esc_html_e( 'Chapter', 'videohub360-studio' ); ?></label><select id="vh360-bible-chapter" data-bible-chapter></select>
                                <label for="vh360-bible-start-verse"><?php esc_html_e( 'Start verse', 'videohub360-studio' ); ?></label><select id="vh360-bible-start-verse" data-bible-start-verse></select><label for="vh360-bible-end-verse"><?php esc_html_e( 'End verse', 'videohub360-studio' ); ?></label><select id="vh360-bible-end-verse" data-bible-end-verse></select><button type="button" class="vh360-studio-button" data-bible-load-selected><?php esc_html_e( 'Load Selected Passage', 'videohub360-studio' ); ?></button>
                                <div class="vh360-studio-bible-reading" data-bible-reading role="region" aria-label="<?php esc_attr_e( 'Loaded Scripture passage', 'videohub360-studio' ); ?>" tabindex="0"></div><div class="vh360-studio-bible-status"><p><strong><?php esc_html_e( 'Preview', 'videohub360-studio' ); ?></strong> <span data-bible-preview-page></span></p><p><strong><?php esc_html_e( 'Program', 'videohub360-studio' ); ?></strong> <span data-bible-program-page></span></p></div>
                                <div class="vh360-studio-bible-actions"><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-prev-page><?php esc_html_e( 'Previous Preview Page', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-next-page><?php esc_html_e( 'Next Preview Page', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-prev-cue><?php esc_html_e( 'Previous Cue', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-next-cue><?php esc_html_e( 'Next Cue', 'videohub360-studio' ); ?></button></div>
                                <div class="vh360-studio-bible-actions"><button type="button" class="vh360-studio-button" data-bible-stage><?php esc_html_e( 'Stage in Preview', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-clear-preview><?php esc_html_e( 'Clear Preview', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button" data-bible-take><?php esc_html_e( 'Take to Program', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-update><?php esc_html_e( 'Update Program', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-hide><?php esc_html_e( 'Hide from Program', 'videohub360-studio' ); ?></button></div><p class="vh360-studio-bible-message" role="status" aria-live="polite" data-bible-status></p>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-bible-customize" class="vh360-studio-bible-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-bible vh360-overlays-section-customize" data-overlays-panel data-module="bible" data-section="customize" <?php echo ( 'bible' === $active_overlay_module && 'control' === 'customize' ) ? '' : 'hidden'; ?>><div class="vh360-studio-bible-stack">
                            <label for="vh360-bible-template"><?php esc_html_e( 'Template', 'videohub360-studio' ); ?></label><select id="vh360-bible-template" data-bible-template><option value="lower_band"><?php esc_html_e( 'Lower Band', 'videohub360-studio' ); ?></option><option value="scripture_card"><?php esc_html_e( 'Scripture Card', 'videohub360-studio' ); ?></option><option value="full_width_panel"><?php esc_html_e( 'Full-Width Scripture Panel', 'videohub360-studio' ); ?></option></select>
                            <label for="vh360-bible-position"><?php esc_html_e( 'Position', 'videohub360-studio' ); ?></label><select id="vh360-bible-position" data-bible-position><option value="bottom_center"><?php esc_html_e( 'Bottom center', 'videohub360-studio' ); ?></option><option value="center"><?php esc_html_e( 'Center', 'videohub360-studio' ); ?></option><option value="top_center"><?php esc_html_e( 'Top center', 'videohub360-studio' ); ?></option></select>
                            <label for="vh360-bible-scale"><?php esc_html_e( 'Scale', 'videohub360-studio' ); ?></label><input id="vh360-bible-scale" type="range" min="75" max="140" value="100" data-bible-scale><label for="vh360-bible-bg"><?php esc_html_e( 'Background color', 'videohub360-studio' ); ?></label><input id="vh360-bible-bg" type="color" value="#0f172a" data-bible-bg><label for="vh360-bible-bg-opacity"><?php esc_html_e( 'Background opacity', 'videohub360-studio' ); ?></label><input id="vh360-bible-bg-opacity" type="range" min="0" max="100" value="88" data-bible-bg-opacity><label for="vh360-bible-scripture-color"><?php esc_html_e( 'Scripture color', 'videohub360-studio' ); ?></label><input id="vh360-bible-scripture-color" type="color" value="#ffffff" data-bible-scripture-color><label for="vh360-bible-reference-color"><?php esc_html_e( 'Reference color', 'videohub360-studio' ); ?></label><input id="vh360-bible-reference-color" type="color" value="#dbeafe" data-bible-reference-color><label for="vh360-bible-align"><?php esc_html_e( 'Text alignment', 'videohub360-studio' ); ?></label><select id="vh360-bible-align" data-bible-align><option value="center"><?php esc_html_e( 'Center', 'videohub360-studio' ); ?></option><option value="left"><?php esc_html_e( 'Left', 'videohub360-studio' ); ?></option><option value="right"><?php esc_html_e( 'Right', 'videohub360-studio' ); ?></option></select><label><input type="checkbox" checked data-bible-show-verse-numbers> <?php esc_html_e( 'Show verse numbers', 'videohub360-studio' ); ?></label><label><input type="checkbox" checked data-bible-show-reference> <?php esc_html_e( 'Show reference', 'videohub360-studio' ); ?></label><label><input type="checkbox" checked data-bible-show-translation> <?php esc_html_e( 'Show translation abbreviation', 'videohub360-studio' ); ?></label></div></section>
                        <section id="vh360-overlays-panel-bible-settings" class="vh360-studio-bible-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-bible vh360-overlays-section-settings" data-overlays-panel data-module="bible" data-section="settings" <?php echo ( 'bible' === $active_overlay_module && 'control' === 'settings' ) ? '' : 'hidden'; ?>><div class="vh360-studio-bible-stack"><label for="vh360-bible-name"><?php esc_html_e( 'Cue name', 'videohub360-studio' ); ?></label><input id="vh360-bible-name" type="text" maxlength="120" data-bible-name><label for="vh360-bible-maximum-lines"><?php esc_html_e( 'Maximum lines per page', 'videohub360-studio' ); ?></label><input id="vh360-bible-maximum-lines" type="number" min="1" max="12" value="6" data-bible-maximum-lines><label for="vh360-bible-entrance"><?php esc_html_e( 'Entrance animation', 'videohub360-studio' ); ?></label><select id="vh360-bible-entrance" data-bible-entrance><option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option><option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option></select><label for="vh360-bible-exit"><?php esc_html_e( 'Exit animation', 'videohub360-studio' ); ?></label><select id="vh360-bible-exit" data-bible-exit><option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option><option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option></select><label for="vh360-bible-duration"><?php esc_html_e( 'Animation duration', 'videohub360-studio' ); ?></label><input id="vh360-bible-duration" type="number" min="0" max="2000" step="50" value="300" data-bible-duration><div class="vh360-studio-bible-actions"><button type="button" class="vh360-studio-button" data-bible-save><?php esc_html_e( 'Save cue', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-save-new><?php esc_html_e( 'Save as new', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-bible-duplicate><?php esc_html_e( 'Duplicate', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--danger" data-bible-delete><?php esc_html_e( 'Delete', 'videohub360-studio' ); ?></button></div></div></section>
                        <section id="vh360-overlays-panel-countdown-control" class="vh360-studio-countdown-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-countdown vh360-overlays-section-control" data-overlays-panel data-module="countdown" data-section="control" <?php echo ( 'countdown' === $active_overlay_module && 'control' === 'control' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-countdown-stack">
                                <label for="vh360-countdown-preset"><?php esc_html_e( 'Saved preset', 'videohub360-studio' ); ?></label>
                                <select id="vh360-countdown-preset" data-countdown-preset><option value=""><?php esc_html_e( 'Unsaved countdown', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-mode"><?php esc_html_e( 'Mode', 'videohub360-studio' ); ?></label>
                                <select id="vh360-countdown-mode" data-countdown-mode><option value="duration"><?php esc_html_e( 'Duration', 'videohub360-studio' ); ?></option><option value="target_time"><?php esc_html_e( 'Target Time', 'videohub360-studio' ); ?></option></select>
                                <div class="vh360-studio-countdown-duration" data-countdown-duration-fields>
                                    <label for="vh360-countdown-hours"><?php esc_html_e( 'Hours', 'videohub360-studio' ); ?></label><input id="vh360-countdown-hours" type="number" min="0" max="23" value="0" data-countdown-hours>
                                    <label for="vh360-countdown-minutes"><?php esc_html_e( 'Minutes', 'videohub360-studio' ); ?></label><input id="vh360-countdown-minutes" type="number" min="0" max="59" value="10" data-countdown-minutes>
                                    <label for="vh360-countdown-seconds"><?php esc_html_e( 'Seconds', 'videohub360-studio' ); ?></label><input id="vh360-countdown-seconds" type="number" min="0" max="59" value="0" data-countdown-seconds>
                                </div>
                                <div data-countdown-target-fields hidden><label for="vh360-countdown-target"><?php esc_html_e( 'Target date and time', 'videohub360-studio' ); ?></label><input id="vh360-countdown-target" type="datetime-local" data-countdown-target></div>
                                <label for="vh360-countdown-label"><?php esc_html_e( 'Label', 'videohub360-studio' ); ?></label><input id="vh360-countdown-label" type="text" maxlength="120" data-countdown-label>
                                <label for="vh360-countdown-message"><?php esc_html_e( 'End message', 'videohub360-studio' ); ?></label><input id="vh360-countdown-message" type="text" maxlength="160" data-countdown-message>
                                <div class="vh360-studio-countdown-status"><p><strong><?php esc_html_e( 'Preview', 'videohub360-studio' ); ?></strong><span data-countdown-preview-status></span></p><p><strong><?php esc_html_e( 'Program', 'videohub360-studio' ); ?></strong><span data-countdown-program-status></span></p><p><strong><?php esc_html_e( 'Remaining', 'videohub360-studio' ); ?></strong><span data-countdown-remaining></span></p></div>
                                <div class="vh360-studio-countdown-actions"><button type="button" class="vh360-studio-button" data-countdown-stage><?php esc_html_e( 'Stage in Preview', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-clear-preview><?php esc_html_e( 'Clear Preview', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button" data-countdown-take><?php esc_html_e( 'Take to Program', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-update><?php esc_html_e( 'Update Program', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-hide><?php esc_html_e( 'Hide from Program', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button" data-countdown-start><?php esc_html_e( 'Start', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-pause><?php esc_html_e( 'Pause', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-resume><?php esc_html_e( 'Resume', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-reset><?php esc_html_e( 'Reset', 'videohub360-studio' ); ?></button></div>
                                <p class="vh360-studio-countdown-message" role="status" aria-live="polite" data-countdown-status></p>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-countdown-customize" class="vh360-studio-countdown-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-countdown vh360-overlays-section-customize" data-overlays-panel data-module="countdown" data-section="customize" <?php echo ( 'countdown' === $active_overlay_module && 'control' === 'customize' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-countdown-stack">
                                <label for="vh360-countdown-template"><?php esc_html_e( 'Template', 'videohub360-studio' ); ?></label><select id="vh360-countdown-template" data-countdown-template><option value="full_screen"><?php esc_html_e( 'Full Screen', 'videohub360-studio' ); ?></option><option value="center_card"><?php esc_html_e( 'Center Card', 'videohub360-studio' ); ?></option><option value="lower_center"><?php esc_html_e( 'Lower Center', 'videohub360-studio' ); ?></option><option value="corner"><?php esc_html_e( 'Corner', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-position"><?php esc_html_e( 'Corner position', 'videohub360-studio' ); ?></label><select id="vh360-countdown-position" data-countdown-position><option value="top_left"><?php esc_html_e( 'Top left', 'videohub360-studio' ); ?></option><option value="top_right"><?php esc_html_e( 'Top right', 'videohub360-studio' ); ?></option><option value="bottom_left"><?php esc_html_e( 'Bottom left', 'videohub360-studio' ); ?></option><option value="bottom_right"><?php esc_html_e( 'Bottom right', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-scale"><?php esc_html_e( 'Scale', 'videohub360-studio' ); ?></label><input id="vh360-countdown-scale" type="range" min="75" max="140" value="100" data-countdown-scale>
                                <label for="vh360-countdown-accent"><?php esc_html_e( 'Accent color', 'videohub360-studio' ); ?></label><input id="vh360-countdown-accent" type="color" value="#4f46e5" data-countdown-accent>
                                <label for="vh360-countdown-bg"><?php esc_html_e( 'Background color', 'videohub360-studio' ); ?></label><input id="vh360-countdown-bg" type="color" value="#0f172a" data-countdown-bg>
                                <label for="vh360-countdown-bg-opacity"><?php esc_html_e( 'Background opacity', 'videohub360-studio' ); ?></label><input id="vh360-countdown-bg-opacity" type="range" min="0" max="100" value="88" data-countdown-bg-opacity>
                                <label for="vh360-countdown-timer-color"><?php esc_html_e( 'Timer text color', 'videohub360-studio' ); ?></label><input id="vh360-countdown-timer-color" type="color" value="#ffffff" data-countdown-timer-color>
                                <label for="vh360-countdown-label-color"><?php esc_html_e( 'Label text color', 'videohub360-studio' ); ?></label><input id="vh360-countdown-label-color" type="color" value="#dbeafe" data-countdown-label-color>
                            </div>
                        </section>
                        <section id="vh360-overlays-panel-countdown-settings" class="vh360-studio-countdown-panel" role="tabpanel" aria-labelledby="vh360-overlays-tab-countdown vh360-overlays-section-settings" data-overlays-panel data-module="countdown" data-section="settings" <?php echo ( 'countdown' === $active_overlay_module && 'control' === 'settings' ) ? '' : 'hidden'; ?>>
                            <div class="vh360-studio-countdown-stack">
                                <label for="vh360-countdown-name"><?php esc_html_e( 'Preset name', 'videohub360-studio' ); ?></label><input id="vh360-countdown-name" type="text" maxlength="120" data-countdown-name>
                                <label for="vh360-countdown-end-behavior"><?php esc_html_e( 'End behavior', 'videohub360-studio' ); ?></label><select id="vh360-countdown-end-behavior" data-countdown-end-behavior><option value="hold_zero"><?php esc_html_e( 'Hold at zero', 'videohub360-studio' ); ?></option><option value="show_message"><?php esc_html_e( 'Show end message', 'videohub360-studio' ); ?></option><option value="hide"><?php esc_html_e( 'Hide', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-message-duration"><?php esc_html_e( 'End-message duration', 'videohub360-studio' ); ?></label><input id="vh360-countdown-message-duration" type="number" min="0" max="300" value="5" data-countdown-message-duration>
                                <label for="vh360-countdown-entrance"><?php esc_html_e( 'Entrance animation', 'videohub360-studio' ); ?></label><select id="vh360-countdown-entrance" data-countdown-entrance><option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option><option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-exit"><?php esc_html_e( 'Exit animation', 'videohub360-studio' ); ?></label><select id="vh360-countdown-exit" data-countdown-exit><option value="fade"><?php esc_html_e( 'Fade', 'videohub360-studio' ); ?></option><option value="none"><?php esc_html_e( 'None', 'videohub360-studio' ); ?></option></select>
                                <label for="vh360-countdown-duration-ms"><?php esc_html_e( 'Animation duration', 'videohub360-studio' ); ?></label><input id="vh360-countdown-duration-ms" type="number" min="0" max="2000" step="50" value="300" data-countdown-duration-ms>
                                <div class="vh360-studio-countdown-actions"><button type="button" class="vh360-studio-button" data-countdown-save><?php esc_html_e( 'Save preset', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-save-new><?php esc_html_e( 'Save as new', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-countdown-duplicate><?php esc_html_e( 'Duplicate', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--danger" data-countdown-delete><?php esc_html_e( 'Delete', 'videohub360-studio' ); ?></button></div>
                            </div>
                        </section>
                    </div>

                    <nav class="vh360-studio-overlays-module-tabs" <?php echo empty( $enabled_overlay_modules ) ? 'hidden' : ''; ?> role="tablist" aria-label="<?php esc_attr_e( 'Overlay module', 'videohub360-studio' ); ?>" data-overlays-module-tabs>
                        <?php foreach ( $allowed_overlay_modules as $module ) : ?>
                            <?php $selected = $module === $active_overlay_module; ?>
                            <button type="button" role="tab" id="vh360-overlays-tab-<?php echo esc_attr( $module ); ?>" aria-selected="<?php echo $selected ? 'true' : 'false'; ?>" aria-controls="vh360-overlays-panel-<?php echo esc_attr( $module ); ?>-control" data-overlays-module-tab data-module="<?php echo esc_attr( $module ); ?>" <?php echo in_array( $module, $enabled_overlay_modules, true ) ? '' : 'hidden'; ?> tabindex="<?php echo $selected ? '0' : '-1'; ?>"><?php echo esc_html( $overlay_tool_labels[ $module ] ); ?></button>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <p class="screen-reader-text" aria-live="polite" data-overlays-status></p>
            </aside>
        </div>

        <div class="vh360-studio-dock-grid" data-studio-dock-layout>
            <section id="vh360-studio-dock-scenes" class="vh360-studio-dock vh360-studio-dock--scenes" aria-labelledby="vh360-studio-scenes-title" data-studio-dock-panel="scenes">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-scenes-title"><?php esc_html_e( 'Scenes', 'videohub360-studio' ); ?></h3></div>
                <div class="vh360-studio-dock-body">
                    <ul class="vh360-studio-scene-list" data-scene-list></ul>
                    <div class="vh360-studio-scene-controls" aria-label="<?php esc_attr_e( 'Scene controls', 'videohub360-studio' ); ?>">
                        <div class="vh360-studio-scene-add-menu-wrap">
                            <button
                                type="button"
                                class="vh360-studio-scene-control-button"
                                data-toggle-source-menu
                                aria-label="<?php esc_attr_e( 'Add source', 'videohub360-studio' ); ?>"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <span aria-hidden="true">+</span>
                            </button>

                            <div class="vh360-studio-scene-add-menu" data-source-menu hidden>
                                <button type="button" data-open-camera-source>
                                    <span><?php esc_html_e( 'Video Capture Device', 'videohub360-studio' ); ?></span>
                                    <small><?php esc_html_e( 'Browser-local camera source', 'videohub360-studio' ); ?></small>
                                </button>

                                <button type="button" data-open-local-media-source>
                                    <span><?php esc_html_e( 'Local Media', 'videohub360-studio' ); ?></span>
                                    <small><?php esc_html_e( 'Current session only', 'videohub360-studio' ); ?></small>
                                </button>

                                <button type="button" data-open-upload-media-source>
                                    <span><?php esc_html_e( 'Upload to Studio', 'videohub360-studio' ); ?></span>
                                    <small><?php esc_html_e( 'Saved until deleted', 'videohub360-studio' ); ?></small>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="vh360-studio-scene-control-button" data-delete-selected-source-scene aria-label="<?php esc_attr_e( 'Delete selected source scene', 'videohub360-studio' ); ?>" disabled>
                            <span aria-hidden="true">−</span>
                        </button>
                    </div>
                    <p class="vh360-studio-help"><?php esc_html_e( 'Scene buttons stage the matching source in Preview.', 'videohub360-studio' ); ?></p>
                </div>
            </section>

            <div class="vh360-studio-dock-resizer" role="separator" tabindex="0" aria-orientation="vertical" aria-controls="vh360-studio-dock-scenes vh360-studio-dock-sources" aria-label="<?php esc_attr_e( 'Resize Scenes and Sources panels', 'videohub360-studio' ); ?>" data-studio-dock-resizer data-left-dock="scenes" data-right-dock="sources"></div>

            <section id="vh360-studio-dock-sources" class="vh360-studio-dock vh360-studio-dock--sources" aria-labelledby="vh360-studio-sources-title" data-studio-dock-panel="sources">
                <div class="vh360-studio-dock-header">
                    <h3 id="vh360-studio-sources-title"><?php esc_html_e( 'Sources', 'videohub360-studio' ); ?></h3>
                    <div class="vh360-studio-dock-header-actions">
                        <button type="button" class="vh360-studio-button vh360-studio-button--ghost vh360-studio-button--compact" data-refresh-devices><?php esc_html_e( 'Refresh', 'videohub360-studio' ); ?></button>
                        <button type="button" class="vh360-studio-button vh360-studio-button--ghost vh360-studio-button--compact" data-open-device-tools aria-haspopup="dialog"><?php esc_html_e( 'Device Tools', 'videohub360-studio' ); ?></button>
                    </div>
                </div>
                <div class="vh360-studio-dock-body vh360-studio-control-stack">
                    <label for="vh360-studio-camera-select"><?php esc_html_e( 'Camera', 'videohub360-studio' ); ?></label>
                    <div class="vh360-studio-selected-camera-controls" data-selected-camera-controls hidden>
                        <label for="vh360-studio-camera-source-name"><?php esc_html_e( 'Camera source name', 'videohub360-studio' ); ?></label>
                        <input id="vh360-studio-camera-source-name" type="text" data-selected-camera-name>
                        <p class="vh360-studio-inline-status" data-selected-camera-status hidden></p>
                    </div>
                    <select id="vh360-studio-camera-select" data-camera-select disabled>
                        <option value=""><?php esc_html_e( 'Grant camera access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <label for="vh360-studio-mic-select"><?php esc_html_e( 'Microphone', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-mic-select" data-mic-select disabled>
                        <option value=""><?php esc_html_e( 'Grant microphone access to list devices', 'videohub360-studio' ); ?></option>
                    </select>
                    <p class="vh360-studio-inline-status" data-device-status role="status" aria-live="polite" aria-atomic="true" hidden></p>
                </div>
            </section>

            <div class="vh360-studio-dock-resizer" role="separator" tabindex="0" aria-orientation="vertical" aria-controls="vh360-studio-dock-sources vh360-studio-dock-audio" aria-label="<?php esc_attr_e( 'Resize Sources and Audio Mixer panels', 'videohub360-studio' ); ?>" data-studio-dock-resizer data-left-dock="sources" data-right-dock="audio"></div>

            <section id="vh360-studio-dock-audio" class="vh360-studio-dock vh360-studio-dock--audio" aria-labelledby="vh360-studio-audio-title" data-studio-dock-panel="audio">
                <div class="vh360-studio-dock-header"><h3 id="vh360-studio-audio-title"><?php esc_html_e( 'Audio Mixer', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-button vh360-studio-button--ghost vh360-studio-button--compact" data-add-audio-input><?php esc_html_e( 'Add Audio Input', 'videohub360-studio' ); ?></button></div>
                <div class="vh360-studio-dock-body">
                    <?php
                    $audio_channels = array(
                        'screen' => __( 'Screen Share', 'videohub360-studio' ),
                        'media'  => __( 'Media/Asset', 'videohub360-studio' ),
                        'master' => __( 'Master Output', 'videohub360-studio' ),
                    );
                    ?>
                    <div class="vh360-studio-audio-mixer" data-audio-mixer role="group" aria-label="<?php esc_attr_e( 'Studio audio mixer channels', 'videohub360-studio' ); ?>">
                        <div class="vh360-studio-meter-scale" aria-hidden="true"><span>0</span><span>-6</span><span>-12</span><span>-18</span><span>-30</span><span>-45</span><span>-60</span></div>
                        <div class="vh360-studio-audio-input-channels" data-audio-input-channels></div>
                        <?php foreach ( $audio_channels as $channel_id => $channel_label ) : ?>
                            <?php
                            $channel_display = $channel_label;
                            $initial_status  = 'master' === $channel_id ? __( 'Active', 'videohub360-studio' ) : __( 'Off', 'videohub360-studio' );
                            ?>
                            <div class="vh360-studio-audio-channel" data-mixer-channel="<?php echo esc_attr( $channel_id ); ?>" data-audio-input-status="<?php echo 'master' === $channel_id ? 'active' : 'off'; ?>">
                                <span class="vh360-studio-audio-status" data-mixer-status="<?php echo esc_attr( $channel_id ); ?>"><span class="vh360-studio-audio-status__dot" aria-hidden="true"></span><span data-mixer-status-text><?php echo esc_html( $initial_status ); ?></span></span>
                                <strong class="vh360-studio-audio-name" title="<?php echo esc_attr( $channel_display ); ?>"><?php echo esc_html( $channel_display ); ?></strong>
                                <?php if ( 'master' !== $channel_id ) : ?>
                                    <output class="vh360-studio-audio-gain-readout" for="vh360-studio-<?php echo esc_attr( $channel_id ); ?>-gain" data-mixer-gain-readout="<?php echo esc_attr( $channel_id ); ?>">0.0 dB</output>
                                <?php endif; ?>
                                <div class="vh360-studio-audio-strip-body">
                                    <?php if ( 'master' !== $channel_id ) : ?>
                                        <label class="vh360-studio-audio-gain">
                                            <span class="screen-reader-text"><?php echo esc_html( sprintf( __( '%s gain', 'videohub360-studio' ), $channel_label ) ); ?></span>
                                            <input id="vh360-studio-<?php echo esc_attr( $channel_id ); ?>-gain" type="range" min="0" max="150" value="100" data-mixer-gain="<?php echo esc_attr( $channel_id ); ?>">
                                        </label>
                                    <?php else : ?>
                                        <span class="vh360-studio-audio-master-note"><?php esc_html_e( 'Mix', 'videohub360-studio' ); ?></span>
                                    <?php endif; ?>
                                    <div class="vh360-studio-meter" data-meter-channel="<?php echo esc_attr( $channel_id ); ?>" role="meter" aria-valuemin="-60" aria-valuemax="0" aria-valuenow="-60" aria-valuetext="<?php echo esc_attr( $channel_label ); ?> <?php esc_attr_e( 'audio level -60 dBFS', 'videohub360-studio' ); ?>" aria-label="<?php echo esc_attr( $channel_label ); ?> <?php esc_attr_e( 'audio level', 'videohub360-studio' ); ?>"><span class="vh360-studio-meter__track" aria-hidden="true"><span class="vh360-studio-meter__fill" data-mixer-meter="<?php echo esc_attr( $channel_id ); ?>"></span><span class="vh360-studio-meter__peak" data-mixer-peak="<?php echo esc_attr( $channel_id ); ?>"></span></span><span class="vh360-studio-meter__clip" data-mixer-clip="<?php echo esc_attr( $channel_id ); ?>" aria-hidden="true"><?php esc_html_e( 'Clip', 'videohub360-studio' ); ?></span></div>
                                </div>
                                <?php if ( 'master' !== $channel_id ) : ?>
                                    <button type="button" class="vh360-studio-audio-mute" data-mixer-mute="<?php echo esc_attr( $channel_id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( __( 'Mute %s', 'videohub360-studio' ), $channel_label ) ); ?>"><span class="vh360-studio-audio-mute__icon" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Mute %s', 'videohub360-studio' ), $channel_label ) ); ?></span></button>
                                <?php else : ?>
                                    <span class="vh360-studio-audio-master-note vh360-studio-audio-master-note--bottom"><?php esc_html_e( 'Output', 'videohub360-studio' ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="vh360-studio-dock-resizer" role="separator" tabindex="0" aria-orientation="vertical" aria-controls="vh360-studio-dock-audio vh360-studio-dock-stream" aria-label="<?php esc_attr_e( 'Resize Audio Mixer and Stream Controls panels', 'videohub360-studio' ); ?>" data-studio-dock-resizer data-left-dock="audio" data-right-dock="stream"></div>

            <section id="vh360-studio-dock-stream" class="vh360-studio-dock vh360-studio-dock--stream" aria-labelledby="vh360-studio-live-title" data-studio-dock-panel="stream">
                <div class="vh360-studio-dock-header">
                    <h3 id="vh360-studio-live-title"><?php esc_html_e( 'Stream Controls', 'videohub360-studio' ); ?></h3>
                    <button type="button" class="vh360-studio-button vh360-studio-button--ghost vh360-studio-button--compact" data-open-stream-settings><?php esc_html_e( 'Settings', 'videohub360-studio' ); ?></button>
                </div>
                <div class="vh360-studio-dock-body vh360-studio-stream-controls">
                    <section class="vh360-studio-stream-control-section" aria-labelledby="vh360-studio-live-controls-heading">
                        <h4 id="vh360-studio-live-controls-heading" class="screen-reader-text"><?php esc_html_e( 'Live', 'videohub360-studio' ); ?></h4>
                        <div class="vh360-studio-live-state"><span data-studio-program-live-status aria-live="polite"><?php esc_html_e( 'Not live', 'videohub360-studio' ); ?></span></div>
                        <div class="vh360-studio-actions">
                            <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-go-live><?php esc_html_e( 'Go Live', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-end-live disabled><?php esc_html_e( 'End Live', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-toggle-mic disabled><?php esc_html_e( 'Mute', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-studio-toggle-video disabled><?php esc_html_e( 'Video Off', 'videohub360-studio' ); ?></button>
                        </div>
                        <div class="vh360-studio-inline-status" aria-live="polite" data-broadcast-status hidden></div>
                        <span class="vh360-studio-screen-reader-status" data-program-diagnostics aria-live="polite"><?php esc_html_e( 'Program active', 'videohub360-studio' ); ?></span>
                    </section>
                    <section class="vh360-studio-stream-control-section" aria-labelledby="vh360-studio-recording-controls-heading">
                        <h4 id="vh360-studio-recording-controls-heading" class="screen-reader-text"><?php esc_html_e( 'Recording & Replay', 'videohub360-studio' ); ?></h4>
                        <div class="vh360-studio-recording-meta"><span data-recording-summary-status><?php esc_html_e( 'Ready to record', 'videohub360-studio' ); ?></span><span aria-hidden="true">·</span><span><span class="screen-reader-text"><?php esc_html_e( 'Duration', 'videohub360-studio' ); ?></span><span data-recording-timer>00:00</span></span><span class="vh360-studio-recording-progress-meta" data-recording-progress-meta hidden><span aria-hidden="true">·</span><span class="screen-reader-text"><?php esc_html_e( 'Progress', 'videohub360-studio' ); ?></span><span data-recording-progress-label>0%</span></span></div>
                        <progress class="vh360-studio-progress" max="100" value="0" data-recording-progress hidden></progress>
                        <div class="vh360-studio-actions">
                            <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-start-recording><?php esc_html_e( 'Start Program recording', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-stop-recording hidden disabled><?php esc_html_e( 'Stop recording', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-retry-chunks hidden disabled><?php esc_html_e( 'Retry failed chunks', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button" data-finalize-recording hidden disabled><?php esc_html_e( 'Prepare replay', 'videohub360-studio' ); ?></button>
                        </div>
                        <div class="vh360-studio-inline-status" aria-live="polite" data-recording-status hidden></div>
                        <p class="vh360-studio-help-text" data-program-recording-helper hidden><?php esc_html_e( 'Records only the Program output. Open the Viewer to record the complete interactive session.', 'videohub360-studio' ); ?></p>
                        <div class="vh360-studio-actions">
                            <button type="button" class="vh360-studio-button vh360-studio-button--primary" data-publish-replay hidden disabled><?php esc_html_e( 'Publish replay', 'videohub360-studio' ); ?></button>
                            <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-check-replay-status hidden disabled><?php esc_html_e( 'Check replay status', 'videohub360-studio' ); ?></button>
                        </div>
                        <div class="vh360-studio-inline-status" aria-live="polite" data-publishing-status hidden></div>
                        <p class="vh360-studio-replay-link" data-replay-link-wrap hidden><strong><?php esc_html_e( 'Replay published.', 'videohub360-studio' ); ?></strong> <a href="#" class="vh360-studio-button vh360-studio-button--secondary" data-replay-link target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open replay', 'videohub360-studio' ); ?></a></p>
                    </section>
                </div>
            </section>
        </div>
    </div>


    <div class="vh360-studio-modal" data-device-tools-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-device-tools-title">
        <div class="vh360-studio-modal__backdrop" data-close-device-tools></div>
        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header"><h3 id="vh360-studio-device-tools-title"><?php esc_html_e( 'Device Tools', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-modal__close" data-close-device-tools aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button></div>
            <div class="vh360-studio-modal__body">
                <div class="vh360-studio-actions"><button type="button" class="vh360-studio-button vh360-studio-button--ghost" data-test-camera><?php esc_html_e( 'Test Camera', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button vh360-studio-button--ghost" data-test-microphone><?php esc_html_e( 'Test Microphone', 'videohub360-studio' ); ?></button></div>
                <p class="vh360-studio-device-tools-summary" data-active-devices><?php esc_html_e( 'Camera: Permission required · Microphone: Permission required', 'videohub360-studio' ); ?></p>
                <div class="vh360-studio-modal__status" data-device-tools-status role="status" aria-live="polite" hidden></div>
            </div>
            <div class="vh360-studio-modal__footer"><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-close-device-tools><?php esc_html_e( 'Done', 'videohub360-studio' ); ?></button></div>
        </div>
    </div>

    <div class="vh360-studio-modal" data-audio-input-settings-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-audio-input-settings-title">
        <div class="vh360-studio-modal__backdrop" data-close-audio-input-settings></div>
        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header"><h3 id="vh360-studio-audio-input-settings-title" data-audio-input-settings-title><?php esc_html_e( 'Audio Input Settings', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-modal__close" data-close-audio-input-settings aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button></div>
            <div class="vh360-studio-modal__body">
                <label class="vh360-studio-field"><?php esc_html_e( 'Input name', 'videohub360-studio' ); ?><input type="text" maxlength="48" data-audio-input-settings-name></label>
                <label class="vh360-studio-field"><?php esc_html_e( 'Device', 'videohub360-studio' ); ?><select data-audio-input-settings-device></select></label>
                <p class="vh360-studio-audio-settings-primary" data-audio-input-settings-primary></p>
                <p class="vh360-studio-audio-settings-status"><strong><?php esc_html_e( 'Current status', 'videohub360-studio' ); ?>:</strong> <span data-audio-input-settings-status></span></p>
                <div class="vh360-studio-modal__status" data-audio-input-settings-warning role="status" aria-live="polite" hidden></div>
                <p class="vh360-studio-help"><?php esc_html_e( 'Each input represents one browser-visible microphone or USB audio device. Hardware mixer sub-channels are not separated by Studio.', 'videohub360-studio' ); ?></p>
                <button type="button" class="vh360-studio-button vh360-studio-button--danger" data-audio-input-settings-remove><?php esc_html_e( 'Remove Audio Input', 'videohub360-studio' ); ?></button>
            </div>
            <div class="vh360-studio-modal__footer"><button type="button" class="vh360-studio-button" data-close-audio-input-settings><?php esc_html_e( 'Done', 'videohub360-studio' ); ?></button></div>
        </div>
    </div>

    <div class="vh360-studio-modal" data-stream-settings-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-stream-settings-title">
        <div class="vh360-studio-modal__backdrop" data-close-stream-settings></div>
        <div class="vh360-studio-modal__panel vh360-studio-modal__panel--stream-settings">
            <div class="vh360-studio-modal__header"><h3 id="vh360-studio-stream-settings-title"><?php esc_html_e( 'Stream Settings', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-modal__close" data-close-stream-settings aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button></div>
            <div class="vh360-studio-modal__body vh360-studio-modal__body--stream-settings">
                <div class="vh360-studio-stream-settings-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Stream settings sections', 'videohub360-studio' ); ?>">
                    <button type="button" class="vh360-studio-stream-settings-tab" id="vh360-studio-stream-settings-tab-live" data-stream-settings-tab="live" role="tab" aria-selected="true" aria-controls="vh360-studio-stream-settings-panel-live" tabindex="0"><?php esc_html_e( 'Live Setup', 'videohub360-studio' ); ?></button>
                    <button type="button" class="vh360-studio-stream-settings-tab" id="vh360-studio-stream-settings-tab-quality" data-stream-settings-tab="quality" role="tab" aria-selected="false" aria-controls="vh360-studio-stream-settings-panel-quality" tabindex="-1"><?php esc_html_e( 'Quality', 'videohub360-studio' ); ?></button>
                </div>
                <div class="vh360-studio-stream-settings-panel" id="vh360-studio-stream-settings-panel-live" data-stream-settings-panel="live" role="tabpanel" aria-labelledby="vh360-studio-stream-settings-tab-live">
                    <div class="vh360-studio-stream-settings-section">
                    <div class="vh360-studio-live-grid">
                        <p class="vh360-studio-live-grid__full"><label><?php esc_html_e( 'Title', 'videohub360-studio' ); ?><input type="text" data-broadcast-title placeholder="<?php esc_attr_e( 'My livestream', 'videohub360-studio' ); ?>"></label></p>
                        <p class="vh360-studio-live-grid__full"><label><?php esc_html_e( 'Description', 'videohub360-studio' ); ?><textarea data-broadcast-description rows="3"></textarea></label></p>
                        <div class="vh360-studio-cover-control" data-cover-control>
                            <label><?php esc_html_e( 'Cover Image / Featured Image', 'videohub360-studio' ); ?></label>
                            <input type="hidden" data-cover-image-id value="">
                            <input type="file" accept="image/*" data-cover-image-file hidden>
                            <div class="vh360-studio-cover-preview" data-cover-image-preview hidden>
                                <img src="" alt="<?php esc_attr_e( 'Selected cover image preview', 'videohub360-studio' ); ?>" data-cover-image-preview-img>
                            </div>
                            <div class="vh360-studio-actions vh360-studio-actions--compact">
                                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-select-cover-image><?php esc_html_e( 'Upload Cover Image', 'videohub360-studio' ); ?></button>
                                <button type="button" class="vh360-studio-button" data-remove-cover-image hidden><?php esc_html_e( 'Remove Cover', 'videohub360-studio' ); ?></button>
                            </div>
                            <p class="vh360-studio-help"><?php esc_html_e( 'Upload an image from your device to use for the livestream, replay, and saved replay media attachment.', 'videohub360-studio' ); ?></p>
                        </div>
                        <p><label><?php esc_html_e( 'Mode', 'videohub360-studio' ); ?><select data-broadcast-mode><option value="broadcast"><?php esc_html_e( 'Broadcast', 'videohub360-studio' ); ?></option><option value="interactive"><?php esc_html_e( 'Interactive', 'videohub360-studio' ); ?></option></select></label></p>
                        <p><label><input type="checkbox" data-broadcast-viewer-count checked> <?php esc_html_e( 'Display viewer count', 'videohub360-studio' ); ?></label></p>
                        <p><label><input type="checkbox" data-broadcast-chat checked> <?php esc_html_e( 'Display live chat', 'videohub360-studio' ); ?></label></p>
                        <p data-interactive-only><label><input type="checkbox" data-broadcast-everyone-host> <?php esc_html_e( 'Allow everyone to be host', 'videohub360-studio' ); ?></label></p>
                        <p data-interactive-only><label><input type="checkbox" data-broadcast-require-passcode> <?php esc_html_e( 'Require passcode to join/present', 'videohub360-studio' ); ?></label></p>
                        <p data-passcode-wrap hidden><label><?php esc_html_e( 'Passcode', 'videohub360-studio' ); ?><input type="password" data-broadcast-passcode autocomplete="new-password"></label></p>
                    </div>
                    </div>
                </div>
                <div class="vh360-studio-stream-settings-panel" id="vh360-studio-stream-settings-panel-quality" data-stream-settings-panel="quality" role="tabpanel" aria-labelledby="vh360-studio-stream-settings-tab-quality" hidden>
                    <div class="vh360-studio-stream-settings-section">
                    <label for="vh360-studio-quality-select"><?php esc_html_e( 'Quality preset', 'videohub360-studio' ); ?></label>
                    <select id="vh360-studio-quality-select" data-quality-select>
                        <?php foreach ( $quality_presets as $preset_id => $preset ) : ?>
                            <option value="<?php echo esc_attr( $preset_id ); ?>" <?php selected( $preset_id, $default_preset ); ?>><?php echo esc_html( $preset['label'] ); ?><?php if ( ! empty( $preset['recommended'] ) ) : ?> <?php esc_html_e( '(recommended)', 'videohub360-studio' ); ?><?php endif; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="vh360-studio-help" data-quality-details><?php echo esc_html( $default_label ); ?> · <?php esc_html_e( 'Higher quality creates larger files and longer uploads; lower presets remain available for smaller recordings.', 'videohub360-studio' ); ?></p>
                    </div>
                </div>
                <div class="vh360-studio-modal__status" data-stream-settings-status role="status" aria-live="polite" hidden></div>
            </div>
            <div class="vh360-studio-modal__footer">
                <button type="button" class="vh360-studio-button" data-close-stream-settings><?php esc_html_e( 'Done', 'videohub360-studio' ); ?></button>
            </div>
        </div>
    </div>

    <div class="vh360-studio-modal" data-studio-diagnostics-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-diagnostics-title">
        <div class="vh360-studio-modal__backdrop" data-close-studio-diagnostics></div>
        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header"><h3 id="vh360-studio-diagnostics-title"><?php esc_html_e( 'Studio diagnostics', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-modal__close" data-close-studio-diagnostics aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button></div>
            <div class="vh360-studio-modal__body">
                <div class="vh360-studio-readiness-summary" data-readiness-summary><strong data-readiness-heading><?php esc_html_e( 'Checking Studio…', 'videohub360-studio' ); ?></strong><p data-readiness-message><?php esc_html_e( 'Checking browser support and permissions.', 'videohub360-studio' ); ?></p><ul data-readiness-issues hidden></ul></div>
                <?php if ( $is_admin ) : ?><details class="vh360-studio-technical-details" open><summary><?php esc_html_e( 'Browser details', 'videohub360-studio' ); ?></summary><ul class="vh360-studio-checks" data-support-checks></ul><div class="vh360-studio-operator-status" aria-live="polite"><h4><?php esc_html_e( 'Operator status', 'videohub360-studio' ); ?></h4><dl><div><dt><?php esc_html_e( 'Program canvas', 'videohub360-studio' ); ?></dt><dd data-operator-canvas-support>—</dd></div><div><dt><?php esc_html_e( 'Program source', 'videohub360-studio' ); ?></dt><dd data-operator-program-source>—</dd></div><div><dt><?php esc_html_e( 'Recording format', 'videohub360-studio' ); ?></dt><dd data-operator-recording-format>—</dd></div><div><dt><?php esc_html_e( 'Program status', 'videohub360-studio' ); ?></dt><dd data-operator-program-status>—</dd></div><div><dt><?php esc_html_e( 'Active job', 'videohub360-studio' ); ?></dt><dd data-operator-active-job>—</dd></div><div><dt><?php esc_html_e( 'Last REST error', 'videohub360-studio' ); ?></dt><dd data-operator-last-rest-error><?php esc_html_e( 'None', 'videohub360-studio' ); ?></dd></div></dl></div></details><?php endif; ?>
            </div><div class="vh360-studio-modal__footer"><button type="button" class="vh360-studio-button" data-close-studio-diagnostics><?php esc_html_e( 'Close', 'videohub360-studio' ); ?></button></div>
        </div>
    </div>

    <div class="vh360-studio-modal" data-overlay-tools-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-overlay-tools-title">
        <div class="vh360-studio-modal__backdrop" data-close-overlay-tools></div><div class="vh360-studio-modal__panel"><div class="vh360-studio-modal__header"><h3 id="vh360-overlay-tools-title"><?php esc_html_e( 'Overlay Tools', 'videohub360-studio' ); ?></h3><button type="button" class="vh360-studio-modal__close" data-close-overlay-tools aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button></div><div class="vh360-studio-modal__body"><p><?php esc_html_e( 'Choose which overlay tools are available in this workspace.', 'videohub360-studio' ); ?></p><div class="vh360-studio-overlay-tools-list">
            <?php foreach ( $allowed_overlay_modules as $module ) : ?><label class="vh360-studio-overlay-tool-row"><input type="checkbox" value="<?php echo esc_attr( $module ); ?>" data-overlay-tool-checkbox <?php checked( in_array( $module, $enabled_overlay_modules, true ) ); ?>><span><strong><?php echo esc_html( $overlay_tool_labels[ $module ] ); ?></strong><small><?php echo esc_html( $overlay_tool_descriptions[ $module ] ); ?></small></span></label><?php endforeach; ?>
        </div><div class="vh360-studio-modal__status" data-overlay-tools-status aria-live="polite" hidden></div></div><div class="vh360-studio-modal__footer"><button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-close-overlay-tools><?php esc_html_e( 'Cancel', 'videohub360-studio' ); ?></button><button type="button" class="vh360-studio-button" data-save-overlay-tools><?php esc_html_e( 'Save Changes', 'videohub360-studio' ); ?></button></div></div>
    </div>

    <div class="vh360-studio-modal" data-camera-source-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-camera-source-title">
        <div class="vh360-studio-modal__backdrop" data-close-camera-source-modal></div>

        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header">
                <h3 id="vh360-studio-camera-source-title"><?php esc_html_e( 'Add Video Capture Device', 'videohub360-studio' ); ?></h3>
                <button type="button" class="vh360-studio-modal__close" data-close-camera-source-modal aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button>
            </div>

            <div class="vh360-studio-modal__body">
                <label for="vh360-studio-new-camera-device"><?php esc_html_e( 'Choose video device', 'videohub360-studio' ); ?></label>
                <select id="vh360-studio-new-camera-device" data-camera-source-device></select>

                <label for="vh360-studio-new-camera-name"><?php esc_html_e( 'Camera source name', 'videohub360-studio' ); ?></label>
                <input id="vh360-studio-new-camera-name" type="text" data-camera-source-name>

                <div class="vh360-studio-modal__status" data-camera-source-status hidden></div>
            </div>

            <div class="vh360-studio-modal__footer">
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-close-camera-source-modal><?php esc_html_e( 'Cancel', 'videohub360-studio' ); ?></button>
                <button type="button" class="vh360-studio-button" data-add-camera-source><?php esc_html_e( 'Add Source', 'videohub360-studio' ); ?></button>
            </div>
        </div>
    </div>

    <div class="vh360-studio-modal" data-media-source-modal hidden role="dialog" aria-modal="true" aria-labelledby="vh360-studio-media-source-title">
        <div class="vh360-studio-modal__backdrop" data-close-media-source-modal></div>

        <div class="vh360-studio-modal__panel">
            <div class="vh360-studio-modal__header">
                <h3 id="vh360-studio-media-source-title" data-media-source-modal-title><?php esc_html_e( 'Add Media Source', 'videohub360-studio' ); ?></h3>
                <button type="button" class="vh360-studio-modal__close" data-close-media-source-modal aria-label="<?php esc_attr_e( 'Close', 'videohub360-studio' ); ?>">×</button>
            </div>

            <div class="vh360-studio-modal__body">
                <label class="vh360-studio-field">
                    <span><?php esc_html_e( 'Media file', 'videohub360-studio' ); ?></span>
                    <input type="file" accept="image/*,video/*" data-persistent-media-source-input>
                </label>

                <label class="vh360-studio-field">
                    <span><?php esc_html_e( 'Display name', 'videohub360-studio' ); ?></span>
                    <input type="text" data-persistent-media-source-name maxlength="120" placeholder="<?php esc_attr_e( 'Example: Welcome Video', 'videohub360-studio' ); ?>">
                </label>

                <p class="vh360-studio-help" data-media-source-modal-help>
                    <?php esc_html_e( 'Imported media will be saved to Studio and remain available until you delete it.', 'videohub360-studio' ); ?>
                </p>

                <div class="vh360-studio-modal__status" data-persistent-media-source-status hidden></div>
            </div>

            <div class="vh360-studio-modal__footer">
                <button type="button" class="vh360-studio-button vh360-studio-button--secondary" data-close-media-source-modal>
                    <?php esc_html_e( 'Cancel', 'videohub360-studio' ); ?>
                </button>
                <button type="button" class="vh360-studio-button" data-import-media-source>
                    <?php esc_html_e( 'Add to Studio', 'videohub360-studio' ); ?>
                </button>
            </div>
        </div>
    </div>

</section>
