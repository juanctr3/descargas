document.addEventListener('DOMContentLoaded', function() {
    
    var iti; // Hacer la variable iti accesible globalmente en este scope
    const phoneInputField = document.querySelector("#phone_number");
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

    const otpModalElement = document.getElementById('otpModal');
    if (otpModalElement) {
        const downloadModal = new bootstrap.Modal(otpModalElement, {});
        const downloadButtons = document.querySelectorAll('.btn-download');

        downloadButtons.forEach(button => {
            button.addEventListener('click', function() {
                const pluginId = this.dataset.pluginId;
                const pluginSlug = this.dataset.pluginSlug;

                if (!pluginId || !pluginSlug) {
                    console.error("Botón de descarga no tiene data-plugin-id o data-plugin-slug.");
                    return;
                }

                // Asignar los valores a los campos ocultos de AMBOS formularios
                document.getElementById('modal_plugin_id').value = pluginId;
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

    const phoneForm = document.getElementById('phone-form');
    if (phoneForm) {
        phoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('phone-feedback');
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            const formData = new FormData(this);
            formData.set('phone_number', iti.getNumber());

            fetch(SITE_URL + '/api/send-otp.php', { method: 'POST', body: formData })
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
            .catch(error => { feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>'; })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Código de Verificación';
            });
        });
    }

    const otpForm = document.getElementById('otp-form');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('otp-feedback');
            const submitButton = this.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verificando...';

            const formData = new FormData(this);

            fetch(SITE_URL + '/api/verify-otp.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let successMessage = `<div class="alert alert-success">${data.message}</div>`;
                    if (data.license_key) {
                        successMessage += `
                        <div class="alert alert-info mt-3">
                            <h5 class="alert-heading">¡Importante! Guarda tu Licencia</h5>
                            <p>Usa esta clave para activar el plugin.</p><hr>
                            <p class="mb-0"><strong>Clave:</strong> <code class="user-select-all">${data.license_key}</code></p>
                            <p class="mb-0"><strong>Expira:</strong> ${data.expires_at}</p>
                        </div>`;
                    }
                    feedbackDiv.innerHTML = successMessage;
                    otpForm.reset();
                    setTimeout(() => { window.location.href = data.download_url; }, 3000);
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(otpModalElement);
                        if (modal) modal.hide();
                    }, 10000);
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => { feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>'; })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Verificar y Descargar';
            });
        });
    }

    const videoModal = document.getElementById('videoModal');
    if (videoModal) {
        videoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const videoSrc = button.getAttribute('data-video-src');
            videoModal.querySelector('iframe').src = videoSrc;
        });
        videoModal.addEventListener('hide.bs.modal', function() {
            videoModal.querySelector('iframe').src = '';
        });
    }
});