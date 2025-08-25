<?php
/**
 * Tab de gestión de tours
 */

// Obtener tours
$tours_sql = "
    SELECT t.*, d.name as destination_name, d.province,
           (SELECT COUNT(*) FROM schedules WHERE tour_id = t.id AND start_date >= CURDATE()) as future_schedules
    FROM tours t
    LEFT JOIN destinations d ON t.destination_id = d.id
    ORDER BY t.created_at DESC
";
$tours = $pdo->query($tours_sql)->fetchAll();

// Obtener destinos para el formulario
$destinations = $pdo->query("SELECT * FROM destinations WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>🎒 Gestión de Tours</h1>
        <button class="btn btn-primary" onclick="openTourModal()">
            Nuevo Tour
        </button>
    </div>
    
    <?php if (!empty($tours)): ?>
        <div class="card">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Tour</th>
                            <th>Destino</th>
                            <th>Duración</th>
                            <th>Dificultad</th>
                            <th>Precio</th>
                            <th>Fechas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tours as $tour): ?>
                            <tr>
                                <td>
                                    <?php if ($tour['image_url']): ?>
                                        <img src="<?= e($tour['image_url']) ?>" 
                                             alt="<?= e($tour['title']) ?>"
                                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 40px; background: var(--gray-medium); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                            Sin imagen
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($tour['title']) ?></strong><br>
                                    <small style="color: var(--text-muted);">Slug: <?= e($tour['slug']) ?></small>
                                </td>
                                <td>
                                    <strong><?= e($tour['destination_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($tour['province']) ?></small>
                                </td>
                                <td><?= $tour['duration_days'] ?> día<?= $tour['duration_days'] > 1 ? 's' : '' ?></td>
                                <td>
                                    <span class="badge badge-<?= $tour['difficulty'] === 'easy' ? 'success' : ($tour['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                                        <?= e(translate_difficulty($tour['difficulty'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= format_price($tour['base_price'], $tour['currency']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($tour['currency']) ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary-color);">
                                        <?= $tour['future_schedules'] ?>
                                    </span> próximas
                                </td>
                                <td>
                                    <?php if ($tour['is_active']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn btn-secondary btn-small" 
                                                onclick="editTour(<?= htmlspecialchars(json_encode($tour)) ?>)">
                                            Editar
                                        </button>
                                        <a href="/admin/actions/tour_save.php?action=toggle&id=<?= $tour['id'] ?>" 
                                           class="btn btn-<?= $tour['is_active'] ? 'warning' : 'success' ?> btn-small"
                                           data-confirm="¿Estás seguro de cambiar el estado?">
                                            <?= $tour['is_active'] ? 'Desactivar' : 'Activar' ?>
                                        </a>
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
            <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No hay tours registrados</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Comienza agregando tu primer tour</p>
            <button class="btn btn-primary" onclick="openTourModal()">
                Crear Primer Tour
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Tour -->
<div id="tour-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="tour-modal-title">Nuevo Tour</h3>
        
        <form id="tour-form" action="/admin/actions/tour_save.php" method="POST">
            <input type="hidden" id="tour-id" name="id">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label for="tour-destination" class="form-label">Destino *</label>
                <select id="tour-destination" name="destination_id" class="form-select" required>
                    <option value="">Seleccionar destino</option>
                    <?php foreach ($destinations as $destination): ?>
                        <option value="<?= $destination['id'] ?>"><?= e($destination['name']) ?> - <?= e($destination['province']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tour-title" class="form-label">Título del Tour *</label>
                <input type="text" id="tour-title" name="title" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="tour-slug" class="form-label">Slug URL</label>
                <input type="text" id="tour-slug" name="slug" class="form-input" 
                       placeholder="Se genera automáticamente si se deja vacío">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="tour-duration" class="form-label">Duración (días) *</label>
                    <input type="number" id="tour-duration" name="duration_days" class="form-input" 
                           min="1" max="30" required>
                </div>
                
                <div class="form-group">
                    <label for="tour-difficulty" class="form-label">Dificultad *</label>
                    <select id="tour-difficulty" name="difficulty" class="form-select" required>
                        <option value="easy">Fácil</option>
                        <option value="medium" selected>Moderado</option>
                        <option value="hard">Difícil</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="tour-price" class="form-label">Precio Base *</label>
                    <input type="number" id="tour-price" name="base_price" class="form-input" 
                           step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="tour-currency" class="form-label">Moneda</label>
                    <select id="tour-currency" name="currency" class="form-select">
                        <option value="USD" selected>USD</option>
                        <option value="EUR">EUR</option>
                        <option value="PEN">PEN</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="tour-highlights" class="form-label">Highlights (JSON)</label>
                <textarea id="tour-highlights" name="highlights" class="form-textarea"
                          placeholder='["Actividad 1", "Actividad 2", "Actividad 3"]'></textarea>
                <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Array JSON con las actividades incluidas en el tour
                </div>
            </div>
            
            <div class="form-group">
                <label for="tour-image" class="form-label">URL de Imagen Principal</label>
                <input type="url" id="tour-image" name="image_url" class="form-input" 
                       placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="tour-active" name="is_active" value="1">
                    Tour activo
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeTourModal()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar Tour
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openTourModal(tour = null) {
    const modal = document.getElementById('tour-modal');
    const form = document.getElementById('tour-form');
    const title = document.getElementById('tour-modal-title');
    
    // Reset form
    form.reset();
    
    if (tour) {
        // Edit mode
        title.textContent = 'Editar Tour';
        document.getElementById('tour-id').value = tour.id;
        document.getElementById('tour-destination').value = tour.destination_id;
        document.getElementById('tour-title').value = tour.title;
        document.getElementById('tour-slug').value = tour.slug;
        document.getElementById('tour-duration').value = tour.duration_days;
        document.getElementById('tour-difficulty').value = tour.difficulty;
        document.getElementById('tour-price').value = tour.base_price;
        document.getElementById('tour-currency').value = tour.currency;
        document.getElementById('tour-highlights').value = tour.highlights || '';
        document.getElementById('tour-image').value = tour.image_url || '';
        document.getElementById('tour-active').checked = tour.is_active == 1;
    } else {
        // Create mode
        title.textContent = 'Nuevo Tour';
        document.getElementById('tour-active').checked = true;
        document.getElementById('tour-duration').value = 1;
        document.getElementById('tour-difficulty').value = 'medium';
        document.getElementById('tour-currency').value = 'USD';
    }
    
    modal.classList.add('active');
}

function editTour(tour) {
    openTourModal(tour);
}

function closeTourModal() {
    document.getElementById('tour-modal').classList.remove('active');
}

// Auto-generate slug from title
document.getElementById('tour-title').addEventListener('input', function() {
    if (!document.getElementById('tour-id').value) { // Only for new tours
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .trim();
        document.getElementById('tour-slug').value = slug;
    }
});

// Close modal when clicking outside or on close button
document.getElementById('tour-modal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-close')) {
        closeTourModal();
    }
});
</script>
