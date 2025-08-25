<?php
/**
 * Procesar acciones de tours
 */

require_once '../../inc/db.php';
require_once '../../inc/auth.php';
require_once '../../inc/helpers.php';

require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? 'save';

try {
    if ($action === 'toggle') {
        // Activar/Desactivar tour
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE tours SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        
        redirect('/admin/dashboard.php?tab=tours');
        
    } elseif ($action === 'save') {
        // Guardar/actualizar tour
        
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token CSRF inválido");
        }
        
        $id = $_POST['id'] ?? '';
        $destination_id = $_POST['destination_id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $duration_days = (int)($_POST['duration_days'] ?? 1);
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $base_price = (float)($_POST['base_price'] ?? 0);
        $currency = $_POST['currency'] ?? 'USD';
        $highlights = trim($_POST['highlights'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validaciones
        if (empty($destination_id)) {
            throw new Exception("Debes seleccionar un destino");
        }
        
        if (empty($title)) {
            throw new Exception("El título del tour es obligatorio");
        }
        
        if ($duration_days < 1 || $duration_days > 30) {
            throw new Exception("La duración debe estar entre 1 y 30 días");
        }
        
        if ($base_price <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }
        
        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            throw new Exception("Dificultad no válida");
        }
        
        // Auto-generar slug si está vacío
        if (empty($slug)) {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }
        
        // Validar que el slug sea único
        $slug_check_sql = "SELECT id FROM tours WHERE slug = ?";
        $params = [$slug];
        
        if ($id) {
            $slug_check_sql .= " AND id != ?";
            $params[] = $id;
        }
        
        $stmt = $pdo->prepare($slug_check_sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        // Validar JSON de highlights
        if (!empty($highlights)) {
            $highlights_array = json_decode($highlights, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("El formato de highlights no es JSON válido");
            }
            $highlights = json_encode($highlights_array);
        } else {
            $highlights = null;
        }
        
        if ($id) {
            // Actualizar
            $sql = "
                UPDATE tours SET 
                    destination_id = ?, title = ?, slug = ?, duration_days = ?,
                    difficulty = ?, base_price = ?, currency = ?, highlights = ?,
                    image_url = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $destination_id, $title, $slug, $duration_days,
                $difficulty, $base_price, $currency, $highlights,
                $image_url, $is_active, $id
            ]);
            
        } else {
            // Crear nuevo
            $sql = "
                INSERT INTO tours (destination_id, title, slug, duration_days, difficulty, base_price, currency, highlights, image_url, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $destination_id, $title, $slug, $duration_days,
                $difficulty, $base_price, $currency, $highlights,
                $image_url, $is_active
            ]);
        }
        
        redirect('/admin/dashboard.php?tab=tours');
        
    } else {
        throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    // En caso de error, redirigir con mensaje de error
    $error_msg = urlencode($e->getMessage());
    redirect("/admin/dashboard.php?tab=tours&error=$error_msg");
}
?>
