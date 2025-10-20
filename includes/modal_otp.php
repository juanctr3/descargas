<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otpModalLabel">Descarga Segura y Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div id="phone-input-section">
                    <p class="text-muted">Para descargar, necesitamos verificar tu número y crear tu cuenta. Te enviaremos un código por WhatsApp.</p>
                    <div id="phone-feedback" class="mb-2"></div>
                    <form id="phone-form">
                        <input type="hidden" name="plugin_id" id="modal_plugin_id">
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Tu número de WhatsApp (*)</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Enviar Código de Verificación</button>
                        </div>
                    </form>
                    <div id="create-password-section" class="mt-4" style="display: none;">
    <hr>
    <p class="text-center fw-bold">Para proteger tu licencia y facilitar futuras descargas, ¿deseas crear una contraseña para tu cuenta?</p>
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
        <button type="button" class="btn btn-primary" id="btn-yes-password">Sí, crear contraseña</button>
        <button type="button" class="btn btn-secondary" id="btn-no-password">No, gracias</button>
    </div>
    <form id="password-form" class="mt-3" style="display: none;">
        <input type="hidden" id="password_user_id" name="user_id">
        <div class="mb-3">
            <label for="password" class="form-label">Nueva Contraseña (mínimo 8 caracteres)</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-success">Guardar Contraseña</button>
        </div>
    </form>
    <div id="password-feedback" class="mt-3"></div>
</div>
                </div>

                <div id="otp-input-section" style="display: none;">
                    <p class="text-muted">Introduce el código y completa tus datos para crear tu cuenta y descargar.</p>
                    <div id="otp-feedback" class="mb-2"></div>
                    <form id="otp-form">
    <input type="hidden" id="otp_plugin_id" name="otp_plugin_id">
    <input type="hidden" id="otp_plugin_slug" name="otp_plugin_slug">

    <div class="mb-3">
        <label for="otp_code" class="form-label">Introduce el codigo recibido en tu Whatsapp</label>
        <input type="text" class="form-control" id="otp_code" name="otp_code" required autocomplete="one-time-code">
    </div>

    <div class="mb-3">
        <label for="otp_user_name" class="form-label">Nombre y Apellido (*)</label>
        <input type="text" class="form-control" id="otp_user_name" name="user_name" required>
    </div>
    <div class="mb-3">
        <label for="otp_email" class="form-label">Correo Electrónico (*)</label>
        <input type="email" class="form-control" id="otp_email" name="email" required>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" value="1" id="opt_in" name="opt_in" checked>
        <label class="form-check-label small" for="opt_in">
            Acepto recibir notificaciones y actualizaciones sobre este plugin.
        </label>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">Verificar y Descargar</button>
    </div>
</form>
                </div>

            </div>
        </div>
    </div>
</div>