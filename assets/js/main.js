document.addEventListener('DOMContentLoaded', function() {
    
    // Inicialización del campo de teléfono internacional
    const phoneInputField = document.querySelector("#phone_number");
    var iti;
    if (phoneInputField) {
        iti = window.intlTelInput(phoneInputField, {
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch('https://ipinfo.io/json', { headers: { 'Accept': 'application/json' } })
                    .then(response => response.json())
                    .then(data => callback(data.country))
                    .catch(() => callback('us'));
            },
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"
        });
    }

    // Lógica para abrir el modal de descarga (OTP)
    const otpModalElement = document.getElementById('otpModal');
    if (otpModalElement) {
        const downloadModal = new bootstrap.Modal(otpModalElement, {});
        const downloadButtons = document.querySelectorAll('.btn-download');

        downloadButtons.forEach(button => {
            button.addEventListener('click', function() {
                const pluginId = this.dataset.pluginId;
                const pluginSlug = this.dataset.pluginSlug;

                // Asignar los valores a los campos ocultos del formulario
                document.getElementById('modal_plugin_id').value = pluginId;
                
                // Estos campos están en el segundo formulario, pero los llenamos desde ahora
                document.getElementById('otp_plugin_id').value = pluginId;
                document.getElementById('otp_plugin_slug').value = pluginSlug;
                
                // Reiniciar el modal a su estado inicial antes de mostrarlo
                document.getElementById('phone-feedback').innerHTML = '';
                document.getElementById('otp-feedback').innerHTML = '';
                document.getElementById('phone-form').reset();
                document.getElementById('otp-form').reset();
                document.getElementById('phone-input-section').style.display = 'block';
                document.getElementById('otp-input-section').style.display = 'none';

                downloadModal.show();
            });
        });
    }

    // Lógica para enviar el número y recibir el OTP
    const phoneForm = document.getElementById('phone-form');
    if (phoneForm) {
        phoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('phone-feedback');
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';

            const formData = new FormData(this);
            formData.set('phone_number', iti.getNumber());

            fetch(SITE_URL + '/api/send-otp.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('phone-input-section').style.display = 'none';
                    document.getElementById('otp-input-section').style.display = 'block';
                    feedbackDiv.innerHTML = '';
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión. Inténtalo de nuevo.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Código de Verificación';
            });
        });
    }

    // Lógica para verificar el OTP y procesar la descarga/registro
    const otpForm = document.getElementById('otp-form');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('otp-feedback');
            const submitButton = this.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...';

            const formData = new FormData(this);

            fetch(SITE_URL + '/api/verify-otp.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let successMessage = `<div class="alert alert-success">${data.message}</div>`;

                    if (data.license_key) {
                        successMessage += `
                        <div class="alert alert-info mt-3">
                            <h5 class="alert-heading">¡Importante! Guarda tu Licencia</h5>
                            <p>Usa la siguiente clave para activar todas las funcionalidades del plugin.</p>
                            <hr>
                            <p class="mb-0"><strong>Clave de Licencia:</strong> <code class="user-select-all">${data.license_key}</code></p>
                            <p class="mb-0"><strong>Expira:</strong> ${data.expires_at}</p>
                        </div>`;
                    }
                    
                    feedbackDiv.innerHTML = successMessage;
                    otpForm.reset();

                    setTimeout(() => {
                        window.location.href = data.download_url;
                    }, 3000);

                    setTimeout(() => {
                        if (otpModalElement) {
                           const modal = bootstrap.Modal.getInstance(otpModalElement);
                           if (modal) modal.hide();
                        }
                    }, 10000);

                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión. Inténtalo de nuevo.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Verificar y Descargar';
            });
        });
    }

    // Lógica para el modal de video
    const videoModal = document.getElementById('videoModal');
    if (videoModal) {
        videoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const videoSrc = button.getAttribute('data-video-src');
            const iframe = videoModal.querySelector('iframe');
            iframe.src = videoSrc;
        });
        videoModal.addEventListener('hide.bs.modal', function(event) {
            const iframe = videoModal.querySelector('iframe');
            iframe.src = '';
        });
    }
});