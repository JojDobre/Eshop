{{-- Náhľad e-mailu v izolovanom rámci, aby štýly e-mailu neovplyvnili administráciu. --}}
<iframe srcdoc="{{ $html }}" sandbox title="{{ __('admin.email_templates.preview') }}"
        style="display: block; width: 100%; height: 70vh; border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.5rem; background: #fff;"></iframe>
