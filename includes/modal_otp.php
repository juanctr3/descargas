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
                </div>

                <div id="otp-input-section" style="display: none;">
                    <p class="text-muted">Introduce el código que te enviamos y completa tus datos para crear tu cuenta y descargar.</p>
                    <div id="otp-feedback" class="mb-2"></div>
                    <form id="otp-form">
                        <input type="hidden" name="plugin_id" id="otp_plugin_id">
                        <input type="hidden" name="plugin_slug" id="otp_plugin_slug">
                        
                        <div class="mb-3">
                            <label for="otp_code" class="form-label">Código de Verificación (*)</label>
                            <input type="text" class="form-control" id="otp_code" name="otp_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_name" class="form-label">Nombre Completo (*)</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico (*)</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                         <div class="mb-3">
                            <label for="password" class="form-label">Crea una Contraseña (*)</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Verificar y Descargar</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>