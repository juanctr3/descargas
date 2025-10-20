/**
 * Archivo: assets/js/main.js
 * Descripción: Script principal para la interactividad del frontend.
 * Funcionalidades:
 * 1. Inicialización del campo de teléfono internacional.
 * 2. Manejo del modal de descarga por OTP (One-Time Password).
 * 3. Envío del número de teléfono para solicitar el OTP.
 * 4. Verificación del OTP y gestión de la respuesta (descarga, licencia).
 * 5. Funcionalidad opcional para que usuarios nuevos creen una contraseña.
 * 6. Manejo del modal para reproducir videos de YouTube.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Variable global para la instancia de intl-tel-input
    var iti; 
    const phoneInputField = document.querySelector("#phone_number");
    
    // 1. Inicialización del campo de teléfono internacional
    if (phoneInputField) {
        iti = window.intlTelInput(phoneInputField, {
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch('https://ipinfo.io/json', { headers: { 'Accept': 'application/json' } })
                    .then(response => response.json())
                    .then(data => callback(data.country))
                    .catch(() => callback('us')); // País por defecto si falla la geolocalización
            },
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"
        });
    }

    // 2. Manejo del modal de descarga
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
                document.getElementById('password-feedback').innerHTML = '';
                
                // Formularios y secciones
                const phoneForm = document.getElementById('phone-form');
                const otpForm = document.getElementById('otp-form');
                const passwordForm = document.getElementById('password-form');
                const createPasswordSection = document.getElementById('create-password-section');

                if(phoneForm) {
                    phoneForm.reset();
                    phoneForm.style.display = 'block';
                }
                if(otpForm) {
                    otpForm.reset();
                    otpForm.style.display = 'block';
                }
                if(passwordForm){
                    passwordForm.reset();
                    passwordForm.style.display = 'none';
                }

                document.getElementById('phone-input-section').style.display = 'block';
                document.getElementById('otp-input-section').style.display = 'none';

                if(createPasswordSection){
                    createPasswordSection.style.display = 'none';
                    // Restaurar botones de contraseña por si se ocultaron
                    document.getElementById('btn-yes-password').style.display = 'inline-block';
                    document.getElementById('btn-no-password').style.display = 'inline-block';
                }
                
                downloadModal.show();
            });
        });
    }

    // 3. Envío del formulario de teléfono para solicitar OTP
    const phoneForm = document.getElementById('phone-form');
    if (phoneForm) {
        phoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('phone-feedback');
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            const formData = new FormData(this);
            // Asegurarse de que el número de teléfono se envíe con el código de país
            formData.set('phone_number', iti.getNumber());

            fetch(SITE_URL + '/api/send-otp.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Si el usuario es nuevo, se muestra el formulario de OTP
                    document.getElementById('phone-input-section').style.display = 'none';
                    document.getElementById('otp-input-section').style.display = 'block';
                    feedbackDiv.innerHTML = '';
                } else {
                    // Si hay un error (ej: usuario ya registrado)
                    if (data.message && data.message.includes("ya está registrado")) {
                        // Mensaje especial para usuarios existentes
                        const loginUrl = SITE_URL + '/login.php';
                        const enhancedMessage = `
                            <div class="alert alert-info">
                                <h5 class="alert-heading">Usuario Encontrado</h5>
                                <p>Este número de teléfono ya está asociado a una cuenta.</p>
                                <hr>
                                <p class="mb-0">
                                    Por favor, <a href="${loginUrl}" class="alert-link">inicia sesión aquí</a> para acceder a tus descargas y licencias.
                                </p>
                            </div>`;
                        feedbackDiv.innerHTML = enhancedMessage;
                        phoneForm.style.display = 'none'; // Ocultar formulario para no confundir
                    } else {
                        // Mensaje de error genérico
                        feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Ocurrió un error.'}</div>`;
                    }
                }
            })
            .catch(error => { feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión. Inténtalo de nuevo.</div>'; })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Código de Verificación';
            });
        });
    }

    // 4. Verificación del OTP y gestión de la respuesta
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
                    otpForm.style.display = 'none'; // Ocultar formulario tras éxito

                    // 5. Mostrar opción de crear contraseña si el usuario es nuevo
                    if (data.is_new_user) {
                        document.getElementById('create-password-section').style.display = 'block';
                        document.getElementById('password_user_id').value = data.user_id;
                    }

                    // Iniciar la descarga automáticamente después de 20 segundos
                    setTimeout(() => { window.location.href = data.download_url; }, 20000);
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

    // 5.1 Lógica para la sección de crear contraseña
    const createPasswordSection = document.getElementById('create-password-section');
    if(createPasswordSection) {
        // Mostrar formulario de contraseña
        document.getElementById('btn-yes-password').addEventListener('click', () => {
            document.getElementById('password-form').style.display = 'block';
            document.getElementById('btn-yes-password').style.display = 'none';
            document.getElementById('btn-no-password').style.display = 'none';
        });

        // Ocultar sección si el usuario no quiere crear contraseña
        document.getElementById('btn-no-password').addEventListener('click', () => {
            createPasswordSection.innerHTML = '<div class="alert alert-secondary text-center">Entendido. Podrás añadir una contraseña más tarde desde tu panel de usuario.</div>';
        });

        // Enviar formulario para guardar contraseña
        document.getElementById('password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('password-feedback');
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

            fetch(SITE_URL + '/api/set-password.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    feedbackDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    document.getElementById('password-form').style.display = 'none';
                } else {
                    feedbackDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(err => { feedbackDiv.innerHTML = '<div class="alert alert-danger">Error de conexión.</div>'; })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Guardar Contraseña';
            });
        });
    }

    // 6. Manejo del modal de video
    const videoModal = document.getElementById('videoModal');
    if (videoModal) {
        videoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const videoSrc = button.getAttribute('data-video-src');
            videoModal.querySelector('iframe').src = videoSrc;
        });
        videoModal.addEventListener('hide.bs.modal', function() {
            // Detener la reproducción del video al cerrar el modal
            videoModal.querySelector('iframe').src = '';
        });
    }
});