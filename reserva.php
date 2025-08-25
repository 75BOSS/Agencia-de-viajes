<?php
/**
 * Sistema de reservas
 * Formulario y procesamiento de reservas
 */

require_once 'inc/db.php';
require_once 'inc/helpers.php';

// Variables para el header
$page_title = "Reservar Tour | ToursEC";
$page_description = "Completa tu reserva de forma segura";

require __DIR__."/inc/header.php";

// Inicializar sesión para CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success = false;
$schedule_id = $_GET['schedule_id'] ?? $_POST['schedule_id'] ?? '';

// Obtener información del schedule y tour
$schedule = null;
$tour = null;

if ($schedule_id) {
    $sql = "
        SELECT s.*, t.title, t.base_price, t.currency, t.slug as tour_slug,
               d.name as destination_name, d.province,
               (s.seats_total - s.seats_taken) as seats_available
        FROM schedules s
        JOIN tours t ON s.tour_id = t.id
        JOIN destinations d ON t.destination_id = d.id
        WHERE s.id = ? AND s.start_date >= CURDATE()
        AND (s.seats_total - s.seats_taken) > 0
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        $errors[] = "La fecha seleccionada no está disponible o ya no tiene cupos.";
    }
}

// Procesar formulario POST
if ($_POST && $schedule) {
    // Verificar CSRF token
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token de seguridad inválido. Inténtalo de nuevo.";
    }
    
    // Validar campos requeridos
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $pax = (int)($_POST['pax'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');
    
    // Validaciones
    if (empty($customer_name)) {
        $errors[] = "El nombre completo es obligatorio.";
    }
    
    if (empty($customer_email)) {
        $errors[] = "El email es obligatorio.";
    } elseif (!is_valid_email($customer_email)) {
        $errors[] = "El email no tiene un formato válido.";
    }
    
    if (empty($customer_phone)) {
        $errors[] = "El teléfono es obligatorio.";
    }
    
    if ($pax < 1 || $pax > 20) {
        $errors[] = "El número de personas debe ser entre 1 y 20.";
    }
    
    // Verificar disponibilidad de cupos
    if ($pax > $schedule['seats_available']) {
        $errors[] = "Solo hay {$schedule['seats_available']} cupos disponibles para esta fecha.";
    }
    
    // Si no hay errores, crear la reserva
    if (empty($errors)) {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Verificar cupos disponibles nuevamente (por seguridad)
            $check_sql = "SELECT (seats_total - seats_taken) as available FROM schedules WHERE id = ? FOR UPDATE";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$schedule_id]);
            $current_available = $check_stmt->fetch()['available'];
            
            if ($current_available < $pax) {
                throw new Exception("No hay suficientes cupos disponibles.");
            }
            
            // Insertar reserva
            $insert_sql = "
                INSERT INTO reservations (schedule_id, customer_name, customer_email, customer_phone, pax, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ";
            
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                $schedule_id,
                $customer_name,
                $customer_email,
                $customer_phone,
                $pax,
                $notes
            ]);
            
            $reservation_id = $pdo->lastInsertId();
            
            // Actualizar seats_taken
            $update_sql = "UPDATE schedules SET seats_taken = seats_taken + ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$pax, $schedule_id]);
            
            // Confirmar transacción
            $pdo->commit();
            
            $success = true;
            
            // Opcional: Enviar email de confirmación aquí
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al procesar la reserva: " . $e->getMessage();
        }
    }
}

// Si no hay schedule_id, mostrar error
if (!$schedule_id && !$_POST) {
    $errors[] = "Debes seleccionar una fecha para realizar la reserva.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $schedule ? "Reservar " . e($schedule['title']) : "Realizar Reserva" ?> | ToursEC</title>
    <meta name="description" content="Completa tu reserva para el tour seleccionado. Proceso rápido y seguro.">
    
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Main Content -->
    <!--
      La navegación principal se carga ahora desde inc/header.php.
      Eliminamos la barra de navegación duplicada para evitar estilos rotos.
    -->
    <section class="section" style="margin-top: 70px;">
        <div class="container">
            
            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="text-center" style="max-width: 600px; margin: 0 auto;">
                    <div class="card" style="padding: 3rem; text-align: center;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <h1 style="color: var(--primary-color); margin-bottom: 1rem;">¡Reserva Exitosa!</h1>
                        <p style="font-size: 1.125rem; margin-bottom: 2rem;">
                            Tu reserva ha sido registrada exitosamente con estado <strong>PENDIENTE</strong>.
                        </p>
                        
                        <div class="card" style="background-color: var(--gray-light); text-align: left; margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Detalles de tu reserva:</h3>
                            <div style="display: grid; gap: 0.5rem;">
                                <div><strong>Tour:</strong> <?= e($schedule['title']) ?></div>
                                <div><strong>Destino:</strong> <?= e($schedule['destination_name']) ?>, <?= e($schedule['province']) ?></div>
                                <div><strong>Fecha:</strong> <?= format_date($schedule['start_date']) ?> - <?= format_date($schedule['end_date']) ?></div>
                                <div><strong>Personas:</strong> <?= e($pax) ?></div>
                                <div><strong>Total:</strong> <?= format_price($schedule['base_price'] * $pax, $schedule['currency']) ?></div>
                                <div><strong>Nombre:</strong> <?= e($customer_name) ?></div>
                                <div><strong>Email:</strong> <?= e($customer_email) ?></div>
                                <div><strong>Teléfono:</strong> <?= e($customer_phone) ?></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" style="text-align: left;">
                            <strong>📱 Próximos pasos:</strong><br>
                            1. Nos pondremos en contacto contigo dentro de 24 horas.<br>
                            2. Te enviaremos los detalles de pago y punto de encuentro.<br>
                            3. Confirmaremos tu reserva una vez recibido el pago.<br><br>
                            <strong>¿Preguntas?</strong> Escríbenos por WhatsApp al +593 98 765 4321
                        </div>
                        
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <a href="/" class="btn btn-secondary">Volver al Inicio</a>
                            <a href="/tour/<?= e($schedule['tour_slug']) ?>" class="btn btn-primary">Ver Tour</a>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($schedule): ?>
                <!-- Booking Form -->
                
                <!-- Breadcrumb -->
                <nav style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 2rem;">
                    <a href="/" style="color: var(--primary-color); text-decoration: none;">Inicio</a>
                    <span> › </span>
                    <a href="/tour/<?= e($schedule['tour_slug']) ?>" style="color: var(--primary-color); text-decoration: none;">
                        <?= e($schedule['title']) ?>
                    </a>
                    <span> › </span>
                    <span>Reservar</span>
                </nav>
                
                <div style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem; align-items: start;">
                    
                    <!-- Reservation Form -->
                    <div>
                        <h1 style="margin-bottom: 2rem;">Completar Reserva</h1>
                        
                        <!-- Show Errors -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error" style="margin-bottom: 2rem;">
                                <strong>Por favor corrige los siguientes errores:</strong>
                                <ul style="margin: 0.5rem 0 0 1rem;">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= e($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" data-validate style="max-width: 500px;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="schedule_id" value="<?= e($schedule_id) ?>">
                            
                            <div class="form-group">
                                <label for="customer_name" class="form-label">Nombre Completo *</label>
                                <input 
                                    type="text" 
                                    id="customer_name" 
                                    name="customer_name" 
                                    class="form-input"
                                    value="<?= e($_POST['customer_name'] ?? '') ?>"
                                    required
                                    placeholder="Ej: María García López"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_email" class="form-label">Email *</label>
                                <input 
                                    type="email" 
                                    id="customer_email" 
                                    name="customer_email" 
                                    class="form-input"
                                    value="<?= e($_POST['customer_email'] ?? '') ?>"
                                    required
                                    placeholder="tu@email.com"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_phone" class="form-label">Teléfono/WhatsApp *</label>
                                <input 
                                    type="tel" 
                                    id="customer_phone" 
                                    name="customer_phone" 
                                    class="form-input"
                                    value="<?= e($_POST['customer_phone'] ?? '') ?>"
                                    required
                                    placeholder="0987654321"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="pax" class="form-label">Número de Personas *</label>
                                <select id="pax" name="pax" class="form-select" required>
                                    <?php for ($i = 1; $i <= min(10, $schedule['seats_available']); $i++): ?>
                                        <option value="<?= $i ?>" <?= (($_POST['pax'] ?? 1) == $i) ? 'selected' : '' ?>>
                                            <?= $i ?> persona<?= $i > 1 ? 's' : '' ?>
                                            (<?= format_price($schedule['base_price'] * $i, $schedule['currency']) ?>)
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    Solo <?= $schedule['seats_available'] ?> cupos disponibles para esta fecha
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes" class="form-label">Comentarios o Necesidades Especiales</label>
                                <textarea 
                                    id="notes" 
                                    name="notes" 
                                    class="form-textarea"
                                    placeholder="Alergias, dieta vegetariana, nivel de experiencia, etc."
                                ><?= e($_POST['notes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="alert alert-info" style="margin-bottom: 2rem;">
                                <strong>📋 Importante:</strong><br>
                                • Esta reserva tendrá estado PENDIENTE hasta confirmar el pago<br>
                                • Te contactaremos en máximo 24 horas<br>
                                • Aceptamos transferencias bancarias y pagos en efectivo<br>
                                • Política de cancelación: 48 horas antes sin costo
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                                Confirmar Reserva
                            </button>
                        </form>
                    </div>
                    
                    <!-- Booking Summary -->
                    <div>
                        <div class="card" style="position: sticky; top: 100px;">
                            <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Resumen de Reserva</h3>
                            
                            <div style="margin-bottom: 1.5rem;">
                                <h4 style="font-size: 1.125rem; margin-bottom: 0.5rem;">
                                    <?= e($schedule['title']) ?>
                                </h4>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">
                                    📍 <?= e($schedule['destination_name']) ?>, <?= e($schedule['province']) ?>
                                </div>
                            </div>
                            
                            <div style="border-top: 1px solid var(--gray-medium); padding-top: 1.5rem; margin-bottom: 1.5rem;">
                                <div style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>📅 Fecha de inicio:</span>
                                        <span style="font-weight: 600;"><?= format_date($schedule['start_date']) ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>📅 Fecha de fin:</span>
                                        <span style="font-weight: 600;"><?= format_date($schedule['end_date']) ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>⏱️ Duración:</span>
                                        <span style="font-weight: 600;"><?= days_diff($schedule['start_date'], $schedule['end_date']) ?> días</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>👥 Cupos disponibles:</span>
                                        <span style="font-weight: 600; color: var(--primary-color);"><?= $schedule['seats_available'] ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="border-top: 1px solid var(--gray-medium); padding-top: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span style="font-weight: 600;">Precio por persona:</span>
                                    <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);">
                                        <?= format_price($schedule['base_price'], $schedule['currency']) ?>
                                    </span>
                                </div>
                                
                                <div id="total-calculation" style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                                    <span>Total para <span id="total-pax">1</span> persona(s): </span>
                                    <strong id="total-price" style="color: var(--primary-color);">
                                        <?= format_price($schedule['base_price'], $schedule['currency']) ?>
                                    </strong>
                                </div>
                                
                                <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.4;">
                                    * El precio puede variar según servicios adicionales<br>
                                    * Pagos aceptados: transferencia bancaria, efectivo
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- No Schedule Selected -->
                <div class="text-center" style="max-width: 600px; margin: 0 auto;">
                    <div class="card" style="padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">❓</div>
                        <h1 style="color: var(--primary-color); margin-bottom: 1rem;">Selecciona una Fecha</h1>
                        <p style="margin-bottom: 2rem;">
                            Para realizar una reserva, primero debes seleccionar un tour y una fecha disponible.
                        </p>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error" style="margin-bottom: 2rem;">
                                <?php foreach ($errors as $error): ?>
                                    <div><?= e($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="/" class="btn btn-primary">Explorar Tours</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background-color: var(--text-primary); color: white; padding: 3rem 0 2rem;">
        <div class="container">
            <div class="text-center">
                <h4 style="color: var(--primary-color); margin-bottom: 1rem;">¿Necesitas Ayuda?</h4>
                <p style="margin-bottom: 1.5rem; opacity: 0.8;">
                    Nuestro equipo está disponible para ayudarte con tu reserva
                </p>
                <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <div>
                        <div style="margin-bottom: 0.5rem;">📱 WhatsApp</div>
                        <div style="font-weight: 600;">+593 98 765 4321</div>
                    </div>
                    <div>
                        <div style="margin-bottom: 0.5rem;">📧 Email</div>
                        <div style="font-weight: 600;">info@toursec.com</div>
                    </div>
                    <div>
                        <div style="margin-bottom: 0.5rem;">🕒 Horario</div>
                        <div style="font-weight: 600;">Lun-Dom 8:00-18:00</div>
                    </div>
                </div>
                
                <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 2rem; opacity: 0.7;">
                    <p>&copy; <?= date('Y') ?> ToursEC. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
    
    <?php if ($schedule): ?>
    <script>
        // Actualizar cálculo de precio total
        document.getElementById('pax').addEventListener('change', function() {
            const pax = parseInt(this.value);
            const pricePerPerson = <?= $schedule['base_price'] ?>;
            const currency = '<?= $schedule['currency'] ?>';
            const total = pax * pricePerPerson;
            
            document.getElementById('total-pax').textContent = pax;
            document.getElementById('total-price').textContent = window.ToursApp.formatPrice(total, currency);
        });
    </script>
    <?php endif; ?>
</body>
</html>
