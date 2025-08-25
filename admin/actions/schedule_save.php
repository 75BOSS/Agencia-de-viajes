<?php
/**
 * Procesar acciones de fechas/schedules
 */

require_once '../../inc/db.php';
require_once '../../inc/auth.php';
require_once '../../inc/helpers.php';

require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? 'save';

try {
    if ($action === 'delete') {
        // Eliminar schedule
        $id = $_GET['id'] ?? 0;
        
        // Verificar que no tenga reservas
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE schedule_id = ?");
        $stmt->execute([$id]);
        $reservations_count = $stmt->fetch()['count'];
        
        if ($reservations_count > 0) {
            throw new Exception("No se puede eliminar una fecha que tiene reservas");
        }
        
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$id]);
        
        redirect('/admin/dashboard.php?tab=schedules');
        
    } elseif ($action === 'save') {
        // Guardar/actualizar schedule
        
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token CSRF inválido");
        }
        
        $id = $_POST['id'] ?? '';
        $tour_id = $_POST['tour_id'] ?? null;
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $seats_total = (int)($_POST['seats_total'] ?? 20);
        $seats_taken = (int)($_POST['seats_taken'] ?? 0);
        
        // Validaciones
        if (empty($tour_id)) {
            throw new Exception("Debes seleccionar un tour");
        }
        
        if (empty($start_date) || empty($end_date)) {
            throw new Exception("Las fechas de inicio y fin son obligatorias");
        }
        
        if (strtotime($end_date) < strtotime($start_date)) {
            throw new Exception("La fecha de fin no puede ser anterior a la fecha de inicio");
        }
        
        if ($seats_total < 1 || $seats_total > 50) {
            throw new Exception("Los cupos totales deben estar entre 1 y 50");
        }
        
        if ($seats_taken < 0 || $seats_taken > $seats_total) {
            throw new Exception("Los cupos ocupados no pueden ser negativos o mayor al total");
        }
        
        // Para fechas pasadas, no permitir crear nuevas
        if (!$id && strtotime($start_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("No se pueden crear fechas en el pasado");
        }
        
        // Verificar que no exista la misma fecha para el mismo tour
        $duplicate_check_sql = "SELECT id FROM schedules WHERE tour_id = ? AND start_date = ? AND end_date = ?";
        $params = [$tour_id, $start_date, $end_date];
        
        if ($id) {
            $duplicate_check_sql .= " AND id != ?";
            $params[] = $id;
        }
        
        $stmt = $pdo->prepare($duplicate_check_sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una fecha programada para este tour en estas fechas");
        }
        
        if ($id) {
            // Actualizar
            $sql = "
                UPDATE schedules SET 
                    tour_id = ?, start_date = ?, end_date = ?, 
                    seats_total = ?, seats_taken = ?
                WHERE id = ?
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $tour_id, $start_date, $end_date,
                $seats_total, $seats_taken, $id
            ]);
            
        } else {
            // Crear nuevo
            $sql = "
                INSERT INTO schedules (tour_id, start_date, end_date, seats_total, seats_taken)
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $tour_id, $start_date, $end_date,
                $seats_total, $seats_taken
            ]);
        }
        
        redirect('/admin/dashboard.php?tab=schedules');
        
    } else {
        throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    // En caso de error, redirigir con mensaje de error
    $error_msg = urlencode($e->getMessage());
    redirect("/admin/dashboard.php?tab=schedules&error=$error_msg");
}
?>
