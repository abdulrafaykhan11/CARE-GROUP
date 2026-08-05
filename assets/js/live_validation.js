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

  function validateControl(control, showEmpty) {
    const field = control.closest('.field');
    if (!field || control.disabled || control.readOnly) return true;

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
