<?php
/**
 * Tab de gestión de destinos
 */

// Obtener destinos
$destinations_sql = "
    SELECT d.*, c.name as category_name,
           (SELECT COUNT(*) FROM tours WHERE destination_id = d.id) as tours_count
    FROM destinations d
    LEFT JOIN categories c ON d.category_id = c.id
    ORDER BY d.created_at DESC
";
$destinations = $pdo->query($destinations_sql)->fetchAll();

// Obtener categorías para el formulario
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>🏔️ Gestión de Destinos</h1>
        <button class="btn btn-primary" data-modal="destination-modal" onclick="openDestinationModal()">
            Nuevo Destino
        </button>
    </div>
    
    <?php if (!empty($destinations)): ?>
        <div class="card">
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Destino</th>
                            <th>Categoría</th>
                            <th>Provincia</th>
                            <th>Tours</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($destinations as $dest): ?>
                            <tr>
                                <td>
                                    <?php if ($dest['image_url']): ?>
                                        <img src="<?= e($dest['image_url']) ?>" 
                                             alt="<?= e($dest['name']) ?>"
                                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 40px; background: var(--gray-medium); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                            Sin imagen
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($dest['name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e(truncate($dest['short_desc'], 50)) ?></small>
                                </td>
                                <td>
                                    <?php if ($dest['category_name']): ?>
                                        <span class="badge badge-secondary"><?= e($dest['category_name']) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Sin categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($dest['province']) ?></td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary-color);">
                                        <?= $dest['tours_count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($dest['is_active']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn btn-secondary btn-small" 
                                                onclick="editDestination(<?= htmlspecialchars(json_encode($dest)) ?>)">
                                            Editar
                                        </button>
                                        <a href="/admin/actions/destination_save.php?action=toggle&id=<?= $dest['id'] ?>" 
                                           class="btn btn-<?= $dest['is_active'] ? 'warning' : 'success' ?> btn-small"
                                           data-confirm="¿Estás seguro de cambiar el estado?">
                                            <?= $dest['is_active'] ? 'Desactivar' : 'Activar' ?>
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
            <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No hay destinos registrados</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Comienza agregando tu primer destino</p>
            <button class="btn btn-primary" onclick="openDestinationModal()">
                Crear Primer Destino
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Destino -->
<div id="destination-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="destination-modal-title">Nuevo Destino</h3>
        
        <form id="destination-form" action="/admin/actions/destination_save.php" method="POST">
            <input type="hidden" id="destination-id" name="id">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label for="destination-name" class="form-label">Nombre del Destino *</label>
                <input type="text" id="destination-name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="destination-slug" class="form-label">Slug URL</label>
                <input type="text" id="destination-slug" name="slug" class="form-input" 
                       placeholder="Se genera automáticamente si se deja vacío">
            </div>
            
            <div class="form-group">
                <label for="destination-category" class="form-label">Categoría</label>
                <select id="destination-category" name="category_id" class="form-select">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="destination-province" class="form-label">Provincia</label>
                <input type="text" id="destination-province" name="province" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="destination-short-desc" class="form-label">Descripción Corta</label>
                <textarea id="destination-short-desc" name="short_desc" class="form-textarea" 
                          placeholder="Descripción breve para las cards"></textarea>
            </div>
            
            <div class="form-group">
                <label for="destination-description" class="form-label">Descripción Completa</label>
                <textarea id="destination-description" name="description" class="form-textarea" 
                          style="min-height: 120px;" placeholder="Descripción detallada del destino"></textarea>
            </div>
            
            <div class="form-group">
                <label for="destination-image" class="form-label">URL de Imagen Principal</label>
                <input type="url" id="destination-image" name="image_url" class="form-input" 
                       placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label for="destination-gallery" class="form-label">Galería (JSON)</label>
                <textarea id="destination-gallery" name="gallery" class="form-textarea"
                          placeholder='["https://imagen1.jpg", "https://imagen2.jpg"]'></textarea>
                <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Array JSON con URLs de imágenes para la galería
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="destination-active" name="is_active" value="1">
                    Destino activo
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDestinationModal()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar Destino
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDestinationModal(destination = null) {
    const modal = document.getElementById('destination-modal');
    const form = document.getElementById('destination-form');
    const title = document.getElementById('destination-modal-title');
    
    // Reset form
    form.reset();
    
    if (destination) {
        // Edit mode
        title.textContent = 'Editar Destino';
        document.getElementById('destination-id').value = destination.id;
        document.getElementById('destination-name').value = destination.name;
        document.getElementById('destination-slug').value = destination.slug;
        document.getElementById('destination-category').value = destination.category_id || '';
        document.getElementById('destination-province').value = destination.province || '';
        document.getElementById('destination-short-desc').value = destination.short_desc || '';
        document.getElementById('destination-description').value = destination.description || '';
        document.getElementById('destination-image').value = destination.image_url || '';
        document.getElementById('destination-gallery').value = destination.gallery || '';
        document.getElementById('destination-active').checked = destination.is_active == 1;
    } else {
        // Create mode
        title.textContent = 'Nuevo Destino';
        document.getElementById('destination-active').checked = true;
    }
    
    modal.classList.add('active');
}

function editDestination(destination) {
    openDestinationModal(destination);
}

function closeDestinationModal() {
    document.getElementById('destination-modal').classList.remove('active');
}

// Auto-generate slug from name
document.getElementById('destination-name').addEventListener('input', function() {
    if (!document.getElementById('destination-id').value) { // Only for new destinations
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .trim();
        document.getElementById('destination-slug').value = slug;
    }
});

// Close modal when clicking outside or on close button
document.getElementById('destination-modal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-close')) {
        closeDestinationModal();
    }
});
</script>
