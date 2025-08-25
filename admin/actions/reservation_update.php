<?php
/**
 * Procesar cambios de estado de reservas
 */

require_once '../../inc/db.php';
require_once '../../inc/auth.php';
require_once '../../inc/helpers.php';

require_admin();

$action = $_GET['action'] ?? '';
$reservation_id = $_GET['id'] ?? 0;

if (!$action || !$reservation_id) {
    redirect('/admin/dashboard.php?tab=reservations');
}

try {
    // Obtener información de la reserva
    $reservation_sql = "
        SELECT r.*, s.seats_total, s.seats_taken 
        FROM reservations r 
        JOIN schedules s ON r.schedule_id = s.id 
        WHERE r.id = ?
    ";
    
    $stmt = $pdo->prepare($reservation_sql);
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        throw new Exception("Reserva no encontrada");
    }
    
    // Iniciar transacción para operaciones seguras
    $pdo->beginTransaction();
    
    if ($action === 'confirm') {
        // Confirmar reserva
        
        if ($reservation['status'] === 'confirmed') {
            throw new Exception("La reserva ya está confirmada");
        }
        
        // Si la reserva está cancelada, verificar disponibilidad de cupos
        if ($reservation['status'] === 'cancelled') {
            $available_seats = $reservation['seats_total'] - $reservation['seats_taken'];
            if ($available_seats < $reservation['pax']) {
                throw new Exception("No hay suficientes cupos disponibles para reactivar esta reserva");
            }
        }
        
        // Actualizar estado de reserva
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$reservation_id]);
        
        // Actualizar cupos ocupados solo si la reserva estaba pendiente o cancelada
        if ($reservation['status'] !== 'confirmed') {
            $stmt = $pdo->prepare("UPDATE schedules SET seats_taken = seats_taken + ? WHERE id = ?");
            $stmt->execute([$reservation['pax'], $reservation['schedule_id']]);
        }
        
        $message = "Reserva confirmada exitosamente";
        
    } elseif ($action === 'cancel') {
        // Cancelar reserva
        
        if ($reservation['status'] === 'cancelled') {
            throw new Exception("La reserva ya está cancelada");
        }
        
        // Actualizar estado de reserva
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$reservation_id]);
        
        // Liberar cupos solo si la reserva estaba confirmada o pendiente
        if ($reservation['status'] === 'confirmed' || $reservation['status'] === 'pending') {
            $stmt = $pdo->prepare("UPDATE schedules SET seats_taken = GREATEST(0, seats_taken - ?) WHERE id = ?");
            $stmt->execute([$reservation['pax'], $reservation['schedule_id']]);
        }
        
        $message = "Reserva cancelada exitosamente";
        
    } else {
        throw new Exception("Acción no válida");
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Redirigir con mensaje de éxito
    $success_msg = urlencode($message);
    redirect("/admin/dashboard.php?tab=reservations&success=$success_msg");
    
} catch (Exception $e) {
    // Rollback en caso de error
    $pdo->rollBack();
    
    // Redirigir con mensaje de error
    $error_msg = urlencode($e->getMessage());
    redirect("/admin/dashboard.php?tab=reservations&error=$error_msg");
}
?>
