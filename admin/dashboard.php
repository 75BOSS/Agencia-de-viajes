<?php require __DIR__.'/../inc/auth.php'; require_admin();
/**
 * Dashboard de administración
 * Panel principal con tabs para gestionar destinos, tours, fechas y reservas
 */

require_once '../inc/db.php';
require_once '../inc/helpers.php';

$current_user = ['name' => $_SESSION['name'] ?? 'Admin', 'email' => 'admin@campingec.com'];

// Obtener estadísticas
$stats_sql = "
    SELECT 
        (SELECT COUNT(*) FROM destinations WHERE is_active = 1) as active_destinations,
        (SELECT COUNT(*) FROM tours WHERE is_active = 1) as active_tours,
        (SELECT COUNT(*) FROM schedules WHERE start_date >= CURDATE()) as future_schedules,
        (SELECT COUNT(*) FROM reservations WHERE status = 'pending') as pending_reservations,
        (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed') as confirmed_reservations,
        (SELECT COUNT(*) FROM reservations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)) as recent_reservations
";
$stats = $pdo->query($stats_sql)->fetch();

// Obtener reservas recientes
$recent_reservations_sql = "
    SELECT r.*, s.start_date, s.end_date, t.title, d.name as destination_name
    FROM reservations r
    JOIN schedules s ON r.schedule_id = s.id
    JOIN tours t ON s.tour_id = t.id
    JOIN destinations d ON t.destination_id = d.id
    ORDER BY r.created_at DESC
    LIMIT 10
";
$recent_reservations = $pdo->query($recent_reservations_sql)->fetchAll();

// Tab activo
$active_tab = $_GET['tab'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | ToursEC</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: var(--text-primary);
            color: white;
            padding: 2rem 0;
        }
        
        .admin-main {
            background: var(--gray-light);
            padding: 2rem;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
        }
        
        .admin-nav li {
            margin-bottom: 0.5rem;
        }
        
        .admin-nav a {
            display: block;
            padding: 0.75rem 2rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all var(--transition-fast);
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .admin-sidebar {
                padding: 1rem 0;
            }
            
            .admin-nav {
                display: flex;
                overflow-x: auto;
                padding: 0 1rem;
            }
            
            .admin-nav li {
                margin-bottom: 0;
                margin-right: 0.5rem;
                flex-shrink: 0;
            }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div style="padding: 0 2rem; margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 0.5rem;">ToursEC</h2>
                <div style="font-size: 0.875rem; opacity: 0.8;">Panel de Administración</div>
            </div>
            
            <nav>
                <ul class="admin-nav">
                    <li><a href="?tab=overview" class="<?= $active_tab === 'overview' ? 'active' : '' ?>">📊 Resumen</a></li>
                    <li><a href="?tab=destinations" class="<?= $active_tab === 'destinations' ? 'active' : '' ?>">🏔️ Destinos</a></li>
                    <li><a href="?tab=tours" class="<?= $active_tab === 'tours' ? 'active' : '' ?>">🎒 Tours</a></li>
                    <li><a href="?tab=schedules" class="<?= $active_tab === 'schedules' ? 'active' : '' ?>">📅 Fechas</a></li>
                    <li><a href="?tab=reservations" class="<?= $active_tab === 'reservations' ? 'active' : '' ?>">📋 Reservas</a></li>
                </ul>
            </nav>
            
            <div style="padding: 2rem; border-top: 1px solid rgba(255,255,255,0.2); margin-top: 2rem;">
                <div style="margin-bottom: 1rem; font-size: 0.875rem;">
                    <strong><?= e($current_user['name']) ?></strong><br>
                    <span style="opacity: 0.8;"><?= e($current_user['email']) ?></span>
                </div>
                <a href="/admin/logout.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.875rem;">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <?php if ($active_tab === 'overview'): ?>
                <!-- Overview Tab -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h1>Dashboard</h1>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                            Última actualización: <?= date('d/m/Y H:i') ?>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['active_destinations'] ?></div>
                            <div>Destinos Activos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['active_tours'] ?></div>
                            <div>Tours Disponibles</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['future_schedules'] ?></div>
                            <div>Fechas Futuras</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['pending_reservations'] ?></div>
                            <div>Reservas Pendientes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['confirmed_reservations'] ?></div>
                            <div>Reservas Confirmadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['recent_reservations'] ?></div>
                            <div>Reservas (7 días)</div>
                        </div>
                    </div>
                    
                    <!-- Recent Reservations -->
                    <div class="card">
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">📋 Reservas Recientes</h3>
                        
                        <?php if (!empty($recent_reservations)): ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Tour</th>
                                            <th>Fecha</th>
                                            <th>Pax</th>
                                            <th>Estado</th>
                                            <th>Reservado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reservations as $reservation): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($reservation['customer_name']) ?></strong><br>
                                                    <small style="color: var(--text-muted);"><?= e($reservation['customer_email']) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= e($reservation['title']) ?></strong><br>
                                                    <small style="color: var(--text-muted);"><?= e($reservation['destination_name']) ?></small>
                                                </td>
                                                <td>
                                                    <?= format_date($reservation['start_date']) ?><br>
                                                    <small style="color: var(--text-muted);">al <?= format_date($reservation['end_date']) ?></small>
                                                </td>
                                                <td><?= $reservation['pax'] ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $reservation['status'] === 'confirmed' ? 'success' : ($reservation['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                                        <?= e(translate_status($reservation['status'])) ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 0.875rem; color: var(--text-muted);">
                                                    <?= format_date($reservation['created_at']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="?tab=reservations" class="btn btn-secondary">Ver Todas las Reservas</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No hay reservas recientes.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'destinations'): ?>
                <!-- Destinations Tab -->
                <?php include 'tabs/destinations.php'; ?>
                
            <?php elseif ($active_tab === 'tours'): ?>
                <!-- Tours Tab -->
                <?php include 'tabs/tours.php'; ?>
                
            <?php elseif ($active_tab === 'schedules'): ?>
                <!-- Schedules Tab -->
                <?php include 'tabs/schedules.php'; ?>
                
            <?php elseif ($active_tab === 'reservations'): ?>
                <!-- Reservations Tab -->
                <?php include 'tabs/reservations.php'; ?>
                
            <?php else: ?>
                <div class="alert alert-error">Tab no encontrado.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    
    <script>
        // Confirmaciones para acciones destructivas
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', function(e) {
                const message = this.getAttribute('data-confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
