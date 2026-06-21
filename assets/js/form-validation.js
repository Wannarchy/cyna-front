(function () {
  'use strict';

  var LANG = window.CYNA_LANG || 'fr';

  var MSG = {
    fr: {
      required: 'Ce champ est obligatoire.',
      email: 'Adresse email invalide.',
      minLength: 'Minimum {n} caractères.',
      maxLength: 'Maximum {n} caractères.',
      passwordMin: 'Le mot de passe doit contenir au moins 8 caractères.',
      passwordUpper: 'Le mot de passe doit contenir au moins une majuscule.',
      passwordLower: 'Le mot de passe doit contenir au moins une minuscule.',
      passwordDigit: 'Le mot de passe doit contenir au moins un chiffre.',
      passwordSpecial: 'Le mot de passe doit contenir au moins un caractère spécial.',
      passwordMatch: 'Les mots de passe ne correspondent pas.',
      selectRequired: 'Veuillez faire un choix.',
      checkboxRequired: 'Vous devez accepter pour continuer.',
      numberMin: 'La valeur minimale est {n}.',
      numberMax: 'La valeur maximale est {n}.',
      fileType: 'Format de fichier non autorisé (JPG, PNG, WEBP, GIF).',
      fileSize: 'Fichier trop volumineux (max {n} Mo).',
      formInvalid: 'Veuillez corriger les erreurs ci-dessous.',
      invalidUrl: 'URL invalide.',
    },
    en: {
      required: 'This field is required.',
      email: 'Invalid email address.',
      minLength: 'Minimum {n} characters.',
      maxLength: 'Maximum {n} characters.',
      passwordMin: 'Password must be at least 8 characters.',
      passwordUpper: 'Password must contain at least one uppercase letter.',
      passwordLower: 'Password must contain at least one lowercase letter.',
      passwordDigit: 'Password must contain at least one number.',
      passwordSpecial: 'Password must contain at least one special character.',
      passwordMatch: 'Passwords do not match.',
      selectRequired: 'Please make a selection.',
      checkboxRequired: 'You must accept to continue.',
      numberMin: 'Minimum value is {n}.',
      numberMax: 'Maximum value is {n}.',
      fileType: 'File type not allowed (JPG, PNG, WEBP, GIF).',
      fileSize: 'File too large (max {n} MB).',
      formInvalid: 'Please fix the errors below.',
      invalidUrl: 'Invalid URL.',
    },
    ar: {
      required: 'هذا الحقل مطلوب.',
      email: 'عنوان البريد الإلكتروني غير صالح.',
      minLength: 'الحد الأدنى {n} أحرف.',
      maxLength: 'الحد الأقصى {n} أحرف.',
      passwordMin: 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.',
      passwordUpper: 'يجب أن تحتوي كلمة المرور على حرف كبير واحد على الأقل.',
      passwordLower: 'يجب أن تحتوي كلمة المرور على حرف صغير واحد على الأقل.',
      passwordDigit: 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
      passwordSpecial: 'يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل.',
      passwordMatch: 'كلمتا المرور غير متطابقتان.',
      selectRequired: 'يرجى الاختيار.',
      checkboxRequired: 'يجب الموافقة للمتابعة.',
      numberMin: 'القيمة الدنيا هي {n}.',
      numberMax: 'القيمة القصوى هي {n}.',
      fileType: 'نوع الملف غير مسموح (JPG, PNG, WEBP, GIF).',
      fileSize: 'الملف كبير جداً (الحد {n} م.ب).',
      formInvalid: 'يرجى تصحيح الأخطاء أدناه.',
      invalidUrl: 'رابط غير صالح.',
    },
    he: {
      required: 'שדה חובה.',
      email: 'כתובת אימייל לא תקינה.',
      minLength: 'מינימום {n} תווים.',
      maxLength: 'מקסימום {n} תווים.',
      passwordMin: 'הסיסמה חייבת להיות לפחות 8 תווים.',
      passwordUpper: 'הסיסמה חייבת להכיל לפחות אות גדולה אחת.',
      passwordLower: 'הסיסמה חייבת להכיל לפחות אות קטנה אחת.',
      passwordDigit: 'הסיסמה חייבת להכיל לפחות ספרה אחת.',
      passwordSpecial: 'הסיסמה חייבת להכיל לפחות תו מיוחד אחד.',
      passwordMatch: 'הסיסמאות אינן תואמות.',
      selectRequired: 'יש לבחור אפשרות.',
      checkboxRequired: 'יש לאשר כדי להמשיך.',
      numberMin: 'ערך מינימלי: {n}.',
      numberMax: 'ערך מקסימלי: {n}.',
      fileType: 'סוג קובץ לא מותר (JPG, PNG, WEBP, GIF).',
      fileSize: 'הקובץ גדול מדי (מקס {n} MB).',
      formInvalid: 'יש לתקן את השגיאות למטה.',
      invalidUrl: 'כתובת URL לא תקינה.',
    },
  };

  var PRESETS = {
    register: {
      prenom: ['required'],
      nom: ['required'],
      email: ['email'],
      mot_de_passe: ['password'],
      confirmation_mot_de_passe: [{ match: 'mot_de_passe' }],
      cgu: ['checkbox'],
    },
    login: {
      email: ['email'],
      mot_de_passe: ['required'],
    },
    'forgot-password': {
      email: ['email'],
    },
    profile: {
      prenom: ['required'],
      nom: ['required'],
      email: ['email'],
    },
    'password-change': {
      ancien_mdp: ['required'],
      nouveau_mdp: ['password'],
      confirmer_mdp: [{ match: 'nouveau_mdp' }],
    },
    'delete-account': {
      delete_password: ['required'],
      delete_confirmation: ['required', { minLength: 8, maxLength: 8 }],
    },
    'password-reset': {
      nouveau_mot_de_passe: ['password'],
      confirmation_mot_de_passe: [{ match: 'nouveau_mot_de_passe' }],
    },
    contact: {
      email: ['email'],
      sujet: ['required', { minLength: 3 }],
      message: ['required', { minLength: 10, maxLength: 5000 }],
    },
    address: {
      prenom: ['required'],
      nom: ['required'],
      adresse1: ['required'],
      ville: ['required'],
      code_postal: ['required'],
      pays: ['required'],
    },
    checkout: {
      prenom: ['required'],
      nom: ['required'],
      adresse1: ['required'],
      ville: ['required'],
      code_postal: ['required'],
      pays: ['required'],
    },
    'admin-login': {
      email: ['email'],
      password: ['required'],
    },
    'admin-category': {
      name: ['required'],
      sort_order: [{ numberMin: 1 }],
      image: [{ fileImage: true, maxMb: 10 }],
    },
    'admin-product': {
      name: ['required'],
      category_id: ['select'],
      price_monthly: [{ numberMin: 0 }],
      price_yearly: [{ numberMin: 0 }],
      stock: [{ numberMin: 0 }],
      image: [{ fileImage: true, maxMb: 10 }],
    },
    'admin-promo': {
      code: ['required', { minLength: 2 }],
      value: [{ numberMin: 0.01 }],
    },
    'admin-reply': {
      reply: ['required', { minLength: 5, maxLength: 5000 }],
    },
    'admin-home-text': {
      content_text: ['required', { minLength: 1 }],
    },
    'admin-slide': {
      title: ['required'],
      link_url: [{ urlOptional: true }],
    },
  };

  var SHIPPING_FIELDS = ['shipping_prenom', 'shipping_nom', 'shipping_adresse1', 'shipping_ville', 'shipping_code_postal', 'shipping_pays'];

  function t(key, vars) {
    var pack = MSG[LANG] || MSG.fr;
    var text = pack[key] || MSG.fr[key] || key;
    if (vars) {
      Object.keys(vars).forEach(function (k) {
        text = text.replace('{' + k + '}', vars[k]);
      });
    }
    return text;
  }

  function trim(val) {
    return (val || '').toString().trim();
  }

  function getField(form, name) {
    if (!name) return null;
    var el = form.querySelector('[name="' + name.replace(/"/g, '\\"') + '"]');
    return el || null;
  }

  function passwordErrors(value) {
    var errors = [];
    if (value.length < 8) errors.push(t('passwordMin'));
    if (!/[A-Z]/.test(value)) errors.push(t('passwordUpper'));
    if (!/[a-z]/.test(value)) errors.push(t('passwordLower'));
    if (!/[0-9]/.test(value)) errors.push(t('passwordDigit'));
    if (!/[^A-Za-z0-9]/.test(value)) errors.push(t('passwordSpecial'));
    return errors;
  }

  function clearFieldError(el) {
    if (!el) return;
    el.classList.remove('cyna-field-error');
    el.removeAttribute('aria-invalid');
    var wrap = el.closest('.field, .mb-3, .mb-4, .col-md-6, .col-md-4, .col-md-5, .col-md-3, .col-12, .col-md-2');
    if (!wrap) wrap = el.parentElement;
    if (!wrap) return;
    var msg = wrap.querySelector('.cyna-error-msg');
    if (msg) msg.remove();
  }

  function showFieldError(el, message) {
    if (!el) return;
    el.classList.add('cyna-field-error');
    el.setAttribute('aria-invalid', 'true');
    var wrap = el.closest('.field, .mb-3, .mb-4, .col-md-6, .col-md-4, .col-md-5, .col-md-3, .col-12, .col-md-2');
    if (!wrap) wrap = el.parentElement;
    if (!wrap) return;
    var existing = wrap.querySelector('.cyna-error-msg');
    if (existing) {
      existing.textContent = message;
      return;
    }
    var div = document.createElement('div');
    div.className = 'cyna-error-msg';
    div.setAttribute('role', 'alert');
    div.textContent = message;
    wrap.appendChild(div);
  }

  function clearFormSummary(form) {
    var box = form.querySelector('.cyna-form-errors');
    if (box) box.remove();
  }

  function showFormSummary(form, messages) {
    clearFormSummary(form);
    if (!messages.length) return;
    var box = document.createElement('div');
    box.className = 'cyna-form-errors';
    box.setAttribute('role', 'alert');
    var ul = document.createElement('ul');
    messages.forEach(function (m) {
      var li = document.createElement('li');
      li.textContent = m;
      ul.appendChild(li);
    });
    box.appendChild(ul);
    form.insertBefore(box, form.firstChild);
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function validateRule(el, rule, form, value) {
    if (rule === 'required') {
      if (el.type === 'checkbox') {
        return el.checked ? null : t('checkboxRequired');
      }
      return trim(value) !== '' ? null : t('required');
    }
    if (rule === 'checkbox') {
      return el.checked ? null : t('checkboxRequired');
    }
    if (rule === 'email') {
      if (trim(value) === '') return t('required');
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trim(value)) ? null : t('email');
    }
    if (rule === 'select') {
      return trim(value) !== '' && value !== '0' ? null : t('selectRequired');
    }
    if (rule === 'password') {
      if (trim(value) === '') return t('required');
      var pwdErrs = passwordErrors(value);
      return pwdErrs.length ? pwdErrs[0] : null;
    }
    if (typeof rule === 'object') {
      if (rule.match) {
        var other = getField(form, rule.match);
        var otherVal = other ? other.value : '';
        if (trim(value) === '') return t('required');
        return value === otherVal ? null : t('passwordMatch');
      }
      if (rule.minLength != null) {
        if (trim(value) === '') return t('required');
        return value.length >= rule.minLength ? null : t('minLength', { n: rule.minLength });
      }
      if (rule.maxLength != null && value.length > rule.maxLength) {
        return t('maxLength', { n: rule.maxLength });
      }
      if (rule.numberMin != null) {
        if (trim(value) === '') return t('required');
        var num = parseFloat(value);
        if (isNaN(num) || num < rule.numberMin) return t('numberMin', { n: rule.numberMin });
      }
      if (rule.numberMax != null) {
        var nmax = parseFloat(value);
        if (!isNaN(nmax) && nmax > rule.numberMax) return t('numberMax', { n: rule.numberMax });
      }
      if (rule.urlOptional && trim(value) !== '') {
        try {
          new URL(value, window.location.origin);
        } catch (e) {
          return t('invalidUrl');
        }
      }
      if (rule.fileImage && el.type === 'file' && el.files && el.files.length) {
        var file = el.files[0];
        var okType = /^image\/(jpeg|png|webp|gif)$/i.test(file.type);
        if (!okType) return t('fileType');
        var maxBytes = (rule.maxMb || 10) * 1024 * 1024;
        if (file.size > maxBytes) return t('fileSize', { n: rule.maxMb || 10 });
      }
    }
    return null;
  }

  function validateField(form, name, rules) {
    var el = getField(form, name);
    if (!el) return null;
    if (el.type === 'file' && (!el.files || !el.files.length)) {
      clearFieldError(el);
      return null;
    }
    if (el.disabled || el.offsetParent === null) {
      clearFieldError(el);
      return null;
    }
    var value = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value;
    for (var i = 0; i < rules.length; i++) {
      var err = validateRule(el, rules[i], form, value);
      if (err) {
        showFieldError(el, err);
        return err;
      }
    }
    clearFieldError(el);
    return null;
  }

  function shippingRequired(form) {
    var same = form.querySelector('#shipping_same_as_billing, [name="shipping_same_as_billing"]');
    if (!same) return false;
    if (same.checked) return false;
    var section = form.querySelector('#shipping-fields');
    if (section && section.style.display === 'none') return false;
    return true;
  }

  function validateForm(form, presetName) {
    var preset = PRESETS[presetName];
    if (!preset) return true;

    clearFormSummary(form);
    var errors = [];

    Object.keys(preset).forEach(function (name) {
      var err = validateField(form, name, preset[name]);
      if (err) errors.push(err);
    });

    if (presetName === 'admin-promo') {
      var typeEl = getField(form, 'type');
      var valueEl = getField(form, 'value');
      if (typeEl && valueEl && typeEl.value === 'percent') {
        var pct = parseFloat(valueEl.value);
        if (!isNaN(pct) && pct > 100) {
          showFieldError(valueEl, t('numberMax', { n: 100 }));
          errors.push(t('numberMax', { n: 100 }));
        }
      }
    }

    if (presetName === 'checkout' && shippingRequired(form)) {
      SHIPPING_FIELDS.forEach(function (name) {
        var short = name.replace('shipping_', '');
        var err = validateField(form, name, PRESETS.address[short] || ['required']);
        if (err) errors.push(err);
      });
    }

    if (errors.length) {
      showFormSummary(form, [t('formInvalid')]);
      var firstBad = form.querySelector('.cyna-field-error');
      if (firstBad) firstBad.focus();
      return false;
    }
    return true;
  }

  function attachForm(form) {
    var preset = form.getAttribute('data-cyna-validate');
    if (!preset) return;

    form.setAttribute('novalidate', 'novalidate');

    form.addEventListener('input', function (e) {
      var target = e.target;
      if (!target.name || !PRESETS[preset][target.name]) return;
      validateField(form, target.name, PRESETS[preset][target.name]);
    });

    form.addEventListener('change', function (e) {
      var target = e.target;
      if (!target.name) return;
      if (PRESETS[preset][target.name]) {
        validateField(form, target.name, PRESETS[preset][target.name]);
      }
      if (target.name === 'type' && preset === 'admin-promo') {
        validateForm(form, preset);
      }
    });

    if (form.getAttribute('data-cyna-manual') === '1') {
      return;
    }

    form.addEventListener('submit', function (e) {
      if (!validateForm(form, preset)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  }

  function init() {
    document.querySelectorAll('form[data-cyna-validate]').forEach(attachForm);
  }

  window.CynaValidate = {
    validate: validateForm,
    init: init,
    passwordErrors: passwordErrors,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
