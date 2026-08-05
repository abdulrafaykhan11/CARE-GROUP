(function () {
  const selectors = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea';

  function ensureMessage(field) {
    let message = field.querySelector('.validation-message');
    if (!message) {
      message = document.createElement('small');
      message.className = 'validation-message';
      message.setAttribute('aria-live', 'polite');
      field.appendChild(message);
    }
    return message;
  }

  function messageFor(control) {
    if (control.validity.valueMissing) return 'This field is required.';
    if (control.validity.typeMismatch) return control.type === 'email' ? 'Enter a valid email address.' : 'Enter a valid value.';
    if (control.validity.patternMismatch) return 'Use the requested format.';
    if (control.validity.tooShort) return 'Please add more detail.';
    if (control.validity.tooLong) return 'This entry is too long.';
    if (control.validity.rangeUnderflow) return 'Choose a value above the minimum.';
    if (control.validity.rangeOverflow) return 'Choose a value below the maximum.';
    if (control.validity.customError) return control.validationMessage;
    return '';
  }

  function applyCustomValidations(control) {
    control.setCustomValidity(''); 
    if (!control.value) return; 

    const name = (control.name || '').toLowerCase();
    const type = control.type;
    const value = control.value;

    if (name.includes('name') && value.trim().length < 3) {
      control.setCustomValidity('Name must be at least 3 characters long.');
    }

    if ((name.includes('phone') || type === 'tel') && value) {
      const phoneRegex = /^((\+92)|(0092)|(0))?3[0-9]{2}[-?\s]?[0-9]{7}$/;
      if (!phoneRegex.test(value)) {
        control.setCustomValidity('Enter a valid Pakistani phone number (e.g. +923001234567 or 03001234567).');
      }
    }

    if ((name.includes('email') || type === 'email') && value) {
      const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailRegex.test(value)) {
        control.setCustomValidity('Enter a valid email address.');
      }
    }

    if (name.includes('cnic') && value) {
      const cnicRegex = /^[0-9]{5}-[0-9]{7}-[0-9]{1}$/;
      if (!cnicRegex.test(value)) {
        control.setCustomValidity('Enter a valid CNIC format (e.g. 42101-1234567-1).');
      }
    }

    if (name.includes('pmdc') && value) {
      const pmdcRegex = /^[0-9]{4,7}-[A-Za-z]{1}$/;
      if (!pmdcRegex.test(value)) {
        control.setCustomValidity('Enter a valid PMDC format (e.g. 12345-P).');
      }
    }
  }

  function validateControl(control, showEmpty) {
    const field = control.closest('.field');
    if (!field || control.disabled || control.readOnly) return true;

    applyCustomValidations(control);

    const hasValue = control.type === 'file'
      ? Boolean(control.files && control.files.length)
      : String(control.value || '').trim() !== '';

    if (!showEmpty && !hasValue) {
      field.classList.remove('is-valid', 'is-invalid');
      const emptyMessage = field.querySelector('.validation-message');
      if (emptyMessage) emptyMessage.textContent = '';
      return control.checkValidity();
    }

    const valid = control.checkValidity();
    const message = ensureMessage(field);
    field.classList.toggle('is-valid', valid && hasValue);
    field.classList.toggle('is-invalid', !valid);
    message.textContent = valid ? '' : messageFor(control);
    return valid;
  }

  function wireForm(form) {
    const controls = Array.from(form.querySelectorAll(selectors));
    controls.forEach(control => {
      ['input', 'change', 'blur'].forEach(eventName => {
        control.addEventListener(eventName, () => validateControl(control, eventName === 'blur'));
      });
    });

    form.addEventListener('submit', event => {
      let firstInvalid = null;
      controls.forEach(control => {
        const valid = validateControl(control, true);
        if (!valid && !firstInvalid) firstInvalid = control;
      });

      if (firstInvalid) {
        event.preventDefault();
        firstInvalid.focus({ preventScroll: true });
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(wireForm);
  });
})();
