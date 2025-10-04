<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="otpModalLabel">Verificación Requerida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="phoneStep">
                    <p>Ingresa tu número de WhatsApp para recibir tu código de descarga:</p>
                    <div class="mb-3">
                        <input type="tel" id="phone_otp" class="form-control">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="optInCheckbox">
                        <label class="form-check-label" for="optInCheckbox">
                            Sí, quiero recibir por email futuras actualizaciones de este plugin.
                        </label>
                    </div>
                    <div id="extraFields" style="display: none;">
                        <div class="mb-2">
                            <label for="userName" class="form-label">Tu Nombre:</label>
                            <input type="text" class="form-control" id="userName" name="user_name" placeholder="Nombre Apellido">
                        </div>
                        <div class="mb-2">
                            <label for="userEmail" class="form-label">Tu Email:</label>
                            <input type="email" class="form-control" id="userEmail" name="user_email" placeholder="tu@email.com">
                        </div>
                    </div>
                    <div id="phoneError" class="text-danger mt-2" style="display: none;"></div>
                </div>
                <div id="otpStep" style="display: none;">
                    <p>Ingresa el código de 6 dígitos que enviamos a tu WhatsApp:</p>
                    <input type="text" id="otpCode" class="form-control text-center" maxlength="6" placeholder="123456">
                    <div id="otpError" class="text-danger mt-2" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="sendOTPButton" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    Enviar Código
                </button>
                <button type="button" id="verifyOTPButton" class="btn btn-success" style="display: none;">
                     <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    Verificar y Descargar
                </button>
            </div>
        </div>
    </div>
</div>