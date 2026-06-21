<?php

function form_validation_lang(): string
{
    global $lang;

    return in_array($lang ?? 'fr', ['fr', 'en', 'ar', 'he'], true) ? ($lang ?? 'fr') : 'fr';
}

function form_validation_styles(): string
{
    return <<<'CSS'
<style>
.cyna-field-error,
.cyna-field-error:focus {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15) !important;
}
.cyna-error-msg {
  font-size: 0.72rem;
  color: #f87171;
  margin-top: 4px;
  line-height: 1.35;
}
.cyna-form-errors {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 0.82rem;
  color: #f87171;
  margin-bottom: 16px;
}
.cyna-form-errors ul {
  margin: 0;
  padding-left: 18px;
}
</style>
CSS;
}

function form_validation_include(string $lang = 'fr'): void
{
    $safeLang = in_array($lang, ['fr', 'en', 'ar', 'he'], true) ? $lang : 'fr';

    echo form_validation_styles();
    echo '<script>window.CYNA_LANG='.json_encode($safeLang, JSON_UNESCAPED_UNICODE).';</script>'."\n";
    echo '<script src="../assets/js/form-validation.js"></script>'."\n";
}
