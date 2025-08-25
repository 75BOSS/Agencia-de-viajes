<?php
/**
 * Tab de gestión de reservas
 */

// Filtros
$status_filter = $_GET['status'] ?? '';
$schedule_filter = $_GET['schedule_id'] ?? '';

// Construir query de reservas
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($schedule_filter) {
    $where_conditions[] = "r.schedule_id = ?";
    $params[] = $schedule_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Obtener reservas
$reservations_sql = "
    SELECT r.*, s.start_date, s.end_date, s.seats_total, s.seats_taken,
           t.title, t.base_price, t.currency, d.name as destination_name, d.province
    FROM reservations r
    JOIN schedules s ON r.schedule_id = s.id
    JOIN tours t ON s.tour_id = t.id
    JOIN destinations d ON t.destination_id = d.id
    $where_clause
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($reservations_sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Obtener stats de reservas
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM reservations r
    JOIN schedules s ON r.schedule_id = s.id
    WHERE s.start_date >= CURDATE()
";
$reservation_stats = $pdo->query($stats_sql)->fetch();
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>📋 Gestión de Reservas</h1>
        
        <!-- Filters -->
        <div style="display: flex; gap: 1rem; align-items: center;">
            <select onchange="window.location.href='?tab=reservations&status=' + this.value + '<?= $schedule_filter ? '&schedule_id=' . $schedule_filter : '' ?>'" style="padding: 0.5rem; border-radius: 8px; border: 1px solid var(--gray-medium);">
                <option value="">Todos los estados</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pendientes</option>
                <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmadas</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Canceladas</option>
            </select>
            
            <?php if ($status_filter || $schedule_filter): ?>
                <a href="?tab=reservations" class="btn btn-secondary btn-small">Limpiar Filtros</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-number"><?= $reservation_stats['total'] ?></div>
            <div>Total Reservas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #f59e0b;"><?= $reservation_stats['pending'] ?></div>
            <div>Pendientes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #10b981;"><?= $reservation_stats['confirmed'] ?></div>
            <div>Confirmadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #ef4444;"><?= $reservation_stats['cancelled'] ?></div>
            <div>Canceladas</div>
        </div>
    </div>
    
    <?php if (!empty($reservations)): ?>
        <div class="card">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tour</th>
                            <th>Fecha</th>
                            <th>Personas</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Reservado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <strong><?= e($reservation['customer_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        📧 <?= e($reservation['customer_email']) ?><br>
                                        <?php if ($reservation['customer_phone']): ?>
                                            📱 <?= e($reservation['customer_phone']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= e($reservation['title']) ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        <?= e($reservation['destination_name']) ?>, <?= e($reservation['province']) ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= format_date($reservation['start_date']) ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        al <?= format_date($reservation['end_date']) ?>
                                    </small>
                                </td>
                                <td style="text-align: center;">
                                    <strong style="color: var(--primary-color);">
                                        <?= $reservation['pax'] ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong><?= format_price($reservation['base_price'] * $reservation['pax'], $reservation['currency']) ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        <?= format_price($reservation['base_price'], $reservation['currency']) ?>/persona
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $reservation['status'] === 'confirmed' ? 'success' : ($reservation['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                        <?= e(translate_status($reservation['status'])) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem; color: var(--text-muted);">
                                    <?= format_date($reservation['created_at'], 'd/m/Y H:i') ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <a href="/admin/actions/reservation_update.php?action=confirm&id=<?= $reservation['id'] ?>" 
                                               class="btn btn-success btn-small"
                                               data-confirm="¿Confirmar esta reserva? Se actualizarán los cupos ocupados.">
                                                Confirmar
                                            </a>
                                            <a href="/admin/actions/reservation_update.php?action=cancel&id=<?= $reservation['id'] ?>" 
                                               class="btn btn-danger btn-small"
                                               data-confirm="¿Cancelar esta reserva? Se liberarán los cupos.">
                                                Cancelar
                                            </a>
                                        <?php elseif ($reservation['status'] === 'confirmed'): ?>
                                            <a href="/admin/actions/reservation_update.php?action=cancel&id=<?= $reservation['id'] ?>" 
                                               class="btn btn-warning btn-small"
                                               data-confirm="¿Cancelar esta reserva confirmada? Se liberarán los cupos.">
                                                Cancelar
                                            </a>
                                        <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                            <a href="/admin/actions/reservation_update.php?action=confirm&id=<?= $reservation['id'] ?>" 
                                               class="btn btn-success btn-small"
                                               data-confirm="¿Reactivar esta reserva? Se ocuparán los cupos nuevamente.">
                                                Reactivar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-secondary btn-small" 
                                                onclick="viewReservationDetails(<?= htmlspecialchars(json_encode($reservation)) ?>)">
                                            Ver
                                        </button>
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
            <?php if ($status_filter || $schedule_filter): ?>
                <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No se encontraron reservas</h3>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">No hay reservas que coincidan con los filtros seleccionados</p>
                <a href="?tab=reservations" class="btn btn-secondary">Ver Todas las Reservas</a>
            <?php else: ?>
                <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No hay reservas registradas</h3>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Las reservas aparecerán aquí cuando los clientes hagan reservas</p>
                <a href="?tab=schedules" class="btn btn-primary">Gestionar Fechas</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para ver detalles de reserva -->
<div id="reservation-details-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>Detalles de la Reserva</h3>
        
        <div id="reservation-details-content">
            <!-- Content will be populated by JavaScript -->
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <button class="btn btn-secondary" onclick="closeReservationDetailsModal()">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
function viewReservationDetails(reservation) {
    const modal = document.getElementById('reservation-details-modal');
    const content = document.getElementById('reservation-details-content');
    
    const statusColors = {
        'pending': '#f59e0b',
        'confirmed': '#10b981',
        'cancelled': '#ef4444'
    };
    
    const statusLabels = {
        'pending': 'Pendiente',
        'confirmed': 'Confirmada',
        'cancelled': 'Cancelada'
    };
    
    content.innerHTML = `
        <div style="display: grid; gap: 1.5rem;">
            <div class="card" style="background-color: var(--gray-light);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">👤 Información del Cliente</h4>
                <div style="display: grid; gap: 0.5rem;">
                    <div><strong>Nombre:</strong> ${reservation.customer_name}</div>
                    <div><strong>Email:</strong> ${reservation.customer_email}</div>
                    <div><strong>Teléfono:</strong> ${reservation.customer_phone || 'No proporcionado'}</div>
                </div>
            </div>
            
            <div class="card" style="background-color: var(--gray-light);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">🎒 Detalles del Tour</h4>
                <div style="display: grid; gap: 0.5rem;">
                    <div><strong>Tour:</strong> ${reservation.title}</div>
                    <div><strong>Destino:</strong> ${reservation.destination_name}, ${reservation.province}</div>
                    <div><strong>Fecha:</strong> ${new Date(reservation.start_date).toLocaleDateString('es-ES')} - ${new Date(reservation.end_date).toLocaleDateString('es-ES')}</div>
                    <div><strong>Personas:</strong> ${reservation.pax}</div>
                    <div><strong>Precio total:</strong> ${window.ToursApp.formatPrice(reservation.base_price * reservation.pax, reservation.currency)}</div>
                </div>
            </div>
            
            <div class="card" style="background-color: var(--gray-light);">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">📋 Estado de la Reserva</h4>
                <div style="display: grid; gap: 0.5rem;">
                    <div><strong>Estado:</strong> 
                        <span style="color: ${statusColors[reservation.status]}; font-weight: 600;">
                            ${statusLabels[reservation.status]}
                        </span>
                    </div>
                    <div><strong>Reservado:</strong> ${new Date(reservation.created_at).toLocaleDateString('es-ES')} ${new Date(reservation.created_at).toLocaleTimeString('es-ES')}</div>
                    <div><strong>ID de reserva:</strong> #${reservation.id}</div>
                    <div><strong>ID de fecha:</strong> #${reservation.schedule_id}</div>
                </div>
            </div>
            
            ${reservation.notes ? `
                <div class="card" style="background-color: var(--gray-light);">
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">💬 Comentarios</h4>
                    <div style="background: white; padding: 1rem; border-radius: 8px; font-style: italic;">
                        "${reservation.notes}"
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.add('active');
}

function closeReservationDetailsModal() {
    document.getElementById('reservation-details-modal').classList.remove('active');
}

// Close modal when clicking outside or on close button
document.getElementById('reservation-details-modal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-close')) {
        closeReservationDetailsModal();
    }
});
</script>
