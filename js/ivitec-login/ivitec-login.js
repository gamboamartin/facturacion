(function () {
    'use strict';

    var root = document.querySelector('[data-ivitec-login]');
    if (!root) return;

    var passwordInput = root.querySelector('[data-password-input]');
    var passwordToggle = root.querySelector('[data-password-toggle]');
    var eyeIcon = root.querySelector('.iv-login__eye');
    var eyeOffIcon = root.querySelector('.iv-login__eye-off');

    if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener('click', function () {
            var showPassword = passwordInput.type === 'password';

            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
            passwordToggle.setAttribute(
                'aria-label',
                showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );

            if (eyeIcon) eyeIcon.hidden = showPassword;
            if (eyeOffIcon) eyeOffIcon.hidden = !showPassword;
            passwordInput.focus({ preventScroll: true });
        });
    }

    var clientLogo = root.querySelector('[data-client-logo]');
    var logoFallback = root.querySelector('[data-logo-fallback]');

    if (clientLogo && logoFallback) {
        clientLogo.addEventListener('error', function () {
            clientLogo.hidden = true;
            logoFallback.hidden = false;
        });
    }

    var form = root.querySelector('[data-login-form]');
    var submitButton = root.querySelector('[data-submit-button]');
    var submitLabel = root.querySelector('[data-button-label]');

    function restoreButton() {
        if (!submitButton) return;

        submitButton.disabled = false;
        submitButton.classList.remove('is-loading');
        submitButton.removeAttribute('aria-busy');
        if (submitLabel) submitLabel.textContent = 'Entrar al sistema';
    }

    if (form && submitButton) {
        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');
            submitButton.setAttribute('aria-busy', 'true');
            if (submitLabel) submitLabel.textContent = 'Validando acceso…';
        });

        window.addEventListener('pageshow', restoreButton);
    }
})();
