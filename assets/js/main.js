document.addEventListener('DOMContentLoaded', function () {

    // --- FUNCIÓN AUXILIAR PARA INICIALIZAR LOS CAMPOS DE TELÉFONO ---
    const initializeTelInput = (inputId, hiddenInputId = null) => {
        const input = document.querySelector("#" + inputId);
        if (!input) return null;

        // Inicializamos la librería en el campo de texto
        const iti = window.intlTelInput(input, {
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
            initialCountry: "auto",
            geoIpLookup: callback => {
                fetch("https://ipapi.co/json").then(res => res.json()).then(data => callback(data.country_code || "us")).catch(() => callback("us"));
            },
            separateDialCode: true,
            nationalMode: false,
        });

        // Si hay un campo oculto asociado (para los formularios de registro/mi cuenta), lo actualizamos
        if (hiddenInputId) {
            const hiddenInput = document.querySelector("#" + hiddenInputId);
            input.addEventListener('input', function() {
                if (hiddenInput && iti.isValidNumber()) {
                    hiddenInput.value = iti.getNumber(); // Obtiene el número en formato E.164 (ej: +573001234567)
                } else if (hiddenInput) {
                    hiddenInput.value = '';
                }
            });
        }
        return iti;
    };

    // --- INICIALIZAMOS LOS CAMPOS DE TELÉFONO EN TODO EL SITIO ---
    const iti_otp = initializeTelInput("phone_otp");
    const iti_register = initializeTelInput("whatsapp_register", "whatsapp_full");
    const iti_account = initializeTelInput("phone_account", "account_whatsapp_full");

    // ===================================================================
    // LÓGICA PARA EL MODAL DE DESCARGA (OTP)
    // ===================================================================
    const otpModalElement = document.getElementById('otpModal');
    if (otpModalElement) {
        const otpModal = new bootstrap.Modal(otpModalElement);
        let currentPluginId = null;
        let currentFullPhoneNumber = null;

        const optInCheckbox = document.getElementById('optInCheckbox');
        const extraFields = document.getElementById('extraFields');
        
        if (optInCheckbox && extraFields) {
            optInCheckbox.addEventListener('change', function () {
                extraFields.style.display = this.checked ? 'block' : 'none';
            });
        }

        document.querySelectorAll('.btn-download').forEach(button => {
            button.addEventListener('click', function () {
                currentPluginId = this.getAttribute('data-plugin-id');
                // Reseteamos el modal a su estado inicial
                document.getElementById('phoneStep').style.display = 'block';
                document.getElementById('otpStep').style.display = 'none';
                document.getElementById('sendOTPButton').style.display = 'inline-block';
                document.getElementById('verifyOTPButton').style.display = 'none';
                if (optInCheckbox) { optInCheckbox.checked = false; }
                if (extraFields) { extraFields.style.display = 'none'; }
                const phoneError = document.getElementById('phoneError');
                if(phoneError) { phoneError.style.display = 'none'; }
                const otpError = document.getElementById('otpError');
                if(otpError) { otpError.style.display = 'none'; }
                otpModal.show();
            });
        });

        const sendOTPButton = document.getElementById('sendOTPButton');
        if (sendOTPButton) {
            sendOTPButton.addEventListener('click', async function () {
                const phoneError = document.getElementById('phoneError');
                phoneError.style.display = 'none';
                
                if (!iti_otp || !iti_otp.isValidNumber()) {
                    phoneError.textContent = 'Por favor, ingresa un número de teléfono válido.';
                    phoneError.style.display = 'block';
                    return;
                }

                currentFullPhoneNumber = iti_otp.getNumber().replace(/\D/g, ''); // Guardamos solo los dígitos
                const optIn = document.getElementById('optInCheckbox').checked;
                const userName = document.getElementById('userName').value;
                const userEmail = document.getElementById('userEmail').value;

                if (optIn && (!userName || !userEmail)) {
                    phoneError.textContent = 'Si deseas recibir notificaciones, el nombre y el email son obligatorios.';
                    phoneError.style.display = 'block';
                    return;
                }

                this.querySelector('.spinner-border').style.display = 'inline-block';
                this.disabled = true;

                const formData = new FormData();
                formData.append('plugin_id', currentPluginId);
                formData.append('phone_number', currentFullPhoneNumber);
                formData.append('opt_in', optIn ? '1' : '0');
                formData.append('user_name', userName);
                formData.append('user_email', userEmail);
                
                try {
                    const response = await fetch(SITE_URL + '/api/send-otp.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        document.getElementById('phoneStep').style.display = 'none';
                        document.getElementById('otpStep').style.display = 'block';
                        sendOTPButton.style.display = 'none';
                        document.getElementById('verifyOTPButton').style.display = 'inline-block';
                    } else {
                        phoneError.textContent = result.message || 'Error al enviar el código.';
                        phoneError.style.display = 'block';
                    }
                } catch (error) {
                    phoneError.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    phoneError.style.display = 'block';
                } finally {
                    this.querySelector('.spinner-border').style.display = 'none';
                    this.disabled = false;
                }
            });
        }
        
        const verifyOTPButton = document.getElementById('verifyOTPButton');
        if (verifyOTPButton) {
            verifyOTPButton.addEventListener('click', async function() {
                const otpCode = document.getElementById('otpCode').value;
                const otpError = document.getElementById('otpError');
                otpError.style.display = 'none';

                if (!otpCode || !/^\d{6}$/.test(otpCode)) {
                    otpError.textContent = 'Ingresa un código válido de 6 dígitos.';
                    otpError.style.display = 'block';
                    return;
                }

                this.querySelector('.spinner-border').style.display = 'inline-block';
                this.disabled = true;

                const formData = new FormData();
                formData.append('phone_number', currentFullPhoneNumber);
                formData.append('otp_code', otpCode);

                try {
                    const response = await fetch(SITE_URL + '/api/verify-otp.php', { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success && result.download_url) {
                        otpModal.hide();
                        window.location.href = result.download_url;
                    } else {
                        otpError.textContent = result.message || 'Error al verificar el código.';
                        otpError.style.display = 'block';
                    }
                } catch (error) {
                    otpError.textContent = 'Error de conexión. Inténtalo de nuevo.';
                    otpError.style.display = 'block';
                } finally {
                    this.querySelector('.spinner-border').style.display = 'none';
                    this.disabled = false;
                }
            });
        }

        // Corrección para el cierre del modal
        const closeModalButtons = otpModalElement.querySelectorAll('[data-bs-dismiss="modal"]');
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                otpModal.hide();
            });
        });
    }

    // ===================================================================
    // LÓGICA PARA EL MODAL DE VIDEO
    // ===================================================================
    const videoModalElement = document.getElementById('videoModal');
    if (videoModalElement) {
        const videoIframe = videoModalElement.querySelector('iframe');
        videoModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const videoSrc = button.getAttribute('data-video-src');
            if (videoIframe && videoSrc) {
                videoIframe.setAttribute('src', videoSrc);
            }
        });
        videoModalElement.addEventListener('hide.bs.modal', function () {
            if (videoIframe) {
                videoIframe.setAttribute('src', '');
            }
        });
    }
});