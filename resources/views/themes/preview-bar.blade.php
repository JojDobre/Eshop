<div style="position: fixed; inset: auto 0 0 0; z-index: 9999; display: flex; gap: 1rem; align-items: center; justify-content: center; padding: .75rem 1rem; background: #18181b; color: #fff; font: 14px/1.4 system-ui, sans-serif;">
    <span>{{ __('admin.themes.preview_bar', ['theme' => $theme?->title()]) }}</span>
    <a href="?theme_preview=0" style="color: #fff; text-decoration: underline;">{{ __('admin.themes.preview_end') }}</a>
</div>
