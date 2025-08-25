<?php
/**
 * Tab de gestión de fechas/horarios
 */

// Obtener schedules
$schedules_sql = "
    SELECT s.*, t.title, d.name as destination_name, d.province,
           (s.seats_total - s.seats_taken) as seats_available,
           (SELECT COUNT(*) FROM reservations WHERE schedule_id = s.id) as reservations_count
    FROM schedules s
    JOIN tours t ON s.tour_id = t.id
    JOIN destinations d ON t.destination_id = d.id
    ORDER BY s.start_date ASC
";
$schedules = $pdo->query($schedules_sql)->fetchAll();

// Obtener tours para el formulario
$tours = $pdo->query("SELECT t.*, d.name as destination_name FROM tours t JOIN destinations d ON t.destination_id = d.id WHERE t.is_active = 1 ORDER BY d.name, t.title")->fetchAll();
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>📅 Gestión de Fechas</h1>
        <button class="btn btn-primary" onclick="openScheduleModal()">
            Nueva Fecha
        </button>
    </div>
    
    <?php if (!empty($schedules)): ?>
        <div class="card">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Destino</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Duración</th>
                            <th>Cupos Total</th>
                            <th>Ocupados</th>
                            <th>Disponibles</th>
                            <th>Reservas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                            <?php
                            $is_past = strtotime($schedule['start_date']) < time();
                            $is_full = $schedule['seats_available'] <= 0;
                            ?>
                            <tr style="<?= $is_past ? 'opacity: 0.6;' : '' ?>">
                                <td>
                                    <strong><?= e($schedule['title']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= e($schedule['destination_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($schedule['province']) ?></small>
                                </td>
                                <td><?= format_date($schedule['start_date']) ?></td>
                                <td><?= format_date($schedule['end_date']) ?></td>
                                <td><?= days_diff($schedule['start_date'], $schedule['end_date']) ?> días</td>
                                <td><?= $schedule['seats_total'] ?></td>
                                <td>
                                    <span style="<?= $schedule['seats_taken'] > 0 ? 'color: var(--primary-color); font-weight: 600;' : '' ?>">
                                        <?= $schedule['seats_taken'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: <?= $is_full ? 'var(--text-danger)' : 'var(--text-success)' ?>; font-weight: 600;">
                                        <?= $schedule['seats_available'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($schedule['reservations_count'] > 0): ?>
                                        <a href="?tab=reservations&schedule_id=<?= $schedule['id'] ?>" 
                                           style="color: var(--primary-color); text-decoration: none;">
                                            <?= $schedule['reservations_count'] ?> reserva<?= $schedule['reservations_count'] > 1 ? 's' : '' ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_past): ?>
                                        <span class="badge badge-secondary">Pasado</span>
                                    <?php elseif ($is_full): ?>
                                        <span class="badge badge-danger">Lleno</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn btn-secondary btn-small" 
                                                onclick="editSchedule(<?= htmlspecialchars(json_encode($schedule)) ?>)">
                                            Editar
                                        </button>
                                        <?php if (!$is_past && $schedule['reservations_count'] == 0): ?>
                                            <a href="/admin/actions/schedule_save.php?action=delete&id=<?= $schedule['id'] ?>" 
                                               class="btn btn-danger btn-small"
                                               data-confirm="¿Estás seguro de eliminar esta fecha?">
                                                Eliminar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card text-center" style="padding: 3rem;">
            <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No hay fechas programadas</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Agrega fechas disponibles para tus tours</p>
            <button class="btn btn-primary" onclick="openScheduleModal()">
                Programar Primera Fecha
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Schedule -->
<div id="schedule-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="schedule-modal-title">Nueva Fecha</h3>
        
        <form id="schedule-form" action="/admin/actions/schedule_save.php" method="POST">
            <input type="hidden" id="schedule-id" name="id">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label for="schedule-tour" class="form-label">Tour *</label>
                <select id="schedule-tour" name="tour_id" class="form-select" required>
                    <option value="">Seleccionar tour</option>
                    <?php foreach ($tours as $tour): ?>
                        <option value="<?= $tour['id'] ?>" data-duration="<?= $tour['duration_days'] ?>">
                            <?= e($tour['destination_name']) ?> - <?= e($tour['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="schedule-start" class="form-label">Fecha de Inicio *</label>
                    <input type="date" id="schedule-start" name="start_date" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="schedule-end" class="form-label">Fecha de Fin *</label>
                    <input type="date" id="schedule-end" name="end_date" class="form-input" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="schedule-seats-total" class="form-label">Cupos Totales *</label>
                    <input type="number" id="schedule-seats-total" name="seats_total" class="form-input" 
                           min="1" max="50" value="20" required>
                </div>
                
                <div class="form-group">
                    <label for="schedule-seats-taken" class="form-label">Cupos Ocupados</label>
                    <input type="number" id="schedule-seats-taken" name="seats_taken" class="form-input" 
                           min="0" value="0" readonly>
                    <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Se actualiza automáticamente con las reservas
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <strong>💡 Tip:</strong> Al seleccionar un tour, la fecha de fin se calculará automáticamente basada en la duración del tour.
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar Fecha
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal(schedule = null) {
    const modal = document.getElementById('schedule-modal');
    const form = document.getElementById('schedule-form');
    const title = document.getElementById('schedule-modal-title');
    
    // Reset form
    form.reset();
    
    if (schedule) {
        // Edit mode
        title.textContent = 'Editar Fecha';
        document.getElementById('schedule-id').value = schedule.id;
        document.getElementById('schedule-tour').value = schedule.tour_id;
        document.getElementById('schedule-start').value = schedule.start_date;
        document.getElementById('schedule-end').value = schedule.end_date;
        document.getElementById('schedule-seats-total').value = schedule.seats_total;
        document.getElementById('schedule-seats-taken').value = schedule.seats_taken;
        
        // Enable seats_taken field for editing if there are reservations
        if (schedule.seats_taken > 0) {
            document.getElementById('schedule-seats-taken').removeAttribute('readonly');
        }
    } else {
        // Create mode
        title.textContent = 'Nueva Fecha';
        document.getElementById('schedule-seats-total').value = 20;
        document.getElementById('schedule-seats-taken').value = 0;
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('schedule-start').min = today;
        document.getElementById('schedule-end').min = today;
    }
    
    modal.classList.add('active');
}

function editSchedule(schedule) {
    openScheduleModal(schedule);
}

function closeScheduleModal() {
    document.getElementById('schedule-modal').classList.remove('active');
}

// Auto-calculate end date based on tour duration
document.getElementById('schedule-tour').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const duration = parseInt(selectedOption.getAttribute('data-duration') || 1);
    
    const startDateInput = document.getElementById('schedule-start');
    const endDateInput = document.getElementById('schedule-end');
    
    if (startDateInput.value && duration > 0) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + duration - 1);
        
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
});

// Auto-calculate end date when start date changes
document.getElementById('schedule-start').addEventListener('change', function() {
    const tourSelect = document.getElementById('schedule-tour');
    const selectedOption = tourSelect.options[tourSelect.selectedIndex];
    const duration = parseInt(selectedOption.getAttribute('data-duration') || 1);
    
    if (this.value && duration > 0) {
        const startDate = new Date(this.value);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + duration - 1);
        
        document.getElementById('schedule-end').value = endDate.toISOString().split('T')[0];
    }
    
    // Update minimum end date
    document.getElementById('schedule-end').min = this.value;
});

// Validate that end date is not before start date
document.getElementById('schedule-end').addEventListener('change', function() {
    const startDate = document.getElementById('schedule-start').value;
    if (startDate && this.value < startDate) {
        alert('La fecha de fin no puede ser anterior a la fecha de inicio');
        this.value = startDate;
    }
});

// Close modal when clicking outside or on close button
document.getElementById('schedule-modal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-close')) {
        closeScheduleModal();
    }
});
</script>
