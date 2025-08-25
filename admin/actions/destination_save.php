<?php
/**
 * Procesar acciones de destinos
 */

require_once '../../inc/db.php';
require_once '../../inc/auth.php';
require_once '../../inc/helpers.php';

require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? 'save';

try {
    if ($action === 'toggle') {
        // Activar/Desactivar destino
        $id = $_GET['id'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE destinations SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        
        redirect('/admin/dashboard.php?tab=destinations');
        
    } elseif ($action === 'save') {
        // Guardar/actualizar destino
        
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token CSRF inválido");
        }
        
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $category_id = $_POST['category_id'] ?: null;
        $province = trim($_POST['province'] ?? '');
        $short_desc = trim($_POST['short_desc'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $gallery = trim($_POST['gallery'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validaciones
        if (empty($name)) {
            throw new Exception("El nombre del destino es obligatorio");
        }
        
        // Auto-generar slug si está vacío
        if (empty($slug)) {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }
        
        // Validar que el slug sea único
        $slug_check_sql = "SELECT id FROM destinations WHERE slug = ?";
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
        
        // Validar JSON de galería
        if (!empty($gallery)) {
            $gallery_array = json_decode($gallery, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("El formato de la galería no es JSON válido");
            }
            $gallery = json_encode($gallery_array);
        } else {
            $gallery = null;
        }
        
        if ($id) {
            // Actualizar
            $sql = "
                UPDATE destinations SET 
                    name = ?, slug = ?, category_id = ?, province = ?,
                    short_desc = ?, description = ?, image_url = ?, gallery = ?,
                    is_active = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $slug, $category_id, $province,
                $short_desc, $description, $image_url, $gallery,
                $is_active, $id
            ]);
            
        } else {
            // Crear nuevo
            $sql = "
                INSERT INTO destinations (name, slug, category_id, province, short_desc, description, image_url, gallery, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $slug, $category_id, $province,
                $short_desc, $description, $image_url, $gallery,
                $is_active
            ]);
        }
        
        redirect('/admin/dashboard.php?tab=destinations');
        
    } else {
        throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    // En caso de error, redirigir con mensaje de error
    $error_msg = urlencode($e->getMessage());
    redirect("/admin/dashboard.php?tab=destinations&error=$error_msg");
}
?>
