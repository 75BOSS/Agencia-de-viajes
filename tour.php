<?php
/**
 * Página de detalle de tour
 * Muestra información completa del tour y fechas disponibles
 */

require_once 'inc/db.php';
require_once 'inc/helpers.php';

// Variables para el header
$page_title = "Tour | ToursEC";
$page_description = "Tour increíble en Ecuador";

require __DIR__."/inc/header.php";

// Obtener slug del tour de la URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /");
    exit;
}

// Obtener información del tour
$sql_tour = "
    SELECT t.*, d.name as destination_name, d.slug as destination_slug, d.province, d.description as destination_description
    FROM tours t 
    LEFT JOIN destinations d ON t.destination_id = d.id 
    WHERE t.slug = ? AND t.is_active = 1
";

$stmt_tour = $pdo->prepare($sql_tour);
$stmt_tour->execute([$slug]);
$tour = $stmt_tour->fetch();

if (!$tour) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Obtener fechas disponibles (schedules con cupos disponibles)
$sql_schedules = "
    SELECT s.*, 
           (s.seats_total - s.seats_taken) as seats_available,
           DATEDIFF(s.end_date, s.start_date) + 1 as duration_days
    FROM schedules s 
    WHERE s.tour_id = ? 
    AND s.start_date >= CURDATE()
    AND (s.seats_total - s.seats_taken) > 0
    ORDER BY s.start_date ASC
";

$stmt_schedules = $pdo->prepare($sql_schedules);
$stmt_schedules->execute([$tour['id']]);
$schedules = $stmt_schedules->fetchAll();

// Obtener galería del destino
$destination_gallery = sane_json($tour['image_url'] ?? '[]');
if (!is_array($destination_gallery)) {
    $destination_gallery = [];
}

// Agregar imagen principal del tour al inicio
if ($tour['image_url']) {
    array_unshift($destination_gallery, $tour['image_url']);
}

// Obtener highlights
$highlights = sane_json($tour['highlights']);

// Meta tags para SEO
$page_title = e($tour['title']) . " - " . e($tour['destination_name']);
$page_description = e(truncate($tour['description'] ?? $tour['destination_description'] ?? '', 160));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | ToursEC</title>
    <meta name="description" content="<?= $page_description ?>">
    <meta name="keywords" content="<?= e($tour['destination_name']) ?>, <?= e($tour['title']) ?>, tour ecuador, <?= e($tour['province']) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $page_title ?>">
    <meta property="og:description" content="<?= $page_description ?>">
    <meta property="og:image" content="<?= e($tour['image_url']) ?>">
    <meta property="og:type" content="article">
    
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        .tour-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 300px 150px;
            gap: 0.5rem;
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .gallery-main {
            grid-row: span 2;
        }
        
        .gallery-item {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .booking-card {
            position: sticky;
            top: 100px;
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-medium);
            border: 2px solid var(--primary-color);
        }
        
        .schedule-option {
            border: 2px solid var(--gray-medium);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .schedule-option:hover,
        .schedule-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(214, 111, 43, 0.05);
        }
        
        .schedule-option input[type="radio"] {
            display: none;
        }
        
        @media (max-width: 768px) {
            .tour-gallery {
                grid-template-columns: 1fr;
                grid-template-rows: 250px 150px 150px;
            }
            
            .gallery-main {
                grid-row: span 1;
            }
        }
    </style>
</head>
<body>

    <!-- Breadcrumb -->
    <!--
      La navegación principal se carga ahora desde inc/header.php.
      Eliminamos la barra de navegación duplicada para evitar estilos rotos.
    -->
    <section style="background-color: var(--gray-light); padding: 1rem 0; margin-top: 70px;">
        <div class="container">
            <nav style="font-size: 0.875rem; color: var(--text-muted);">
                <a href="/" style="color: var(--primary-color); text-decoration: none;">Inicio</a>
                <span> › </span>
                <a href="/destino/<?= e($tour['destination_slug']) ?>" style="color: var(--primary-color); text-decoration: none;">
                    <?= e($tour['destination_name']) ?>
                </a>
                <span> › </span>
                <span><?= e($tour['title']) ?></span>
            </nav>
        </div>
    </section>

    <!-- Tour Details -->
    <section class="section">
        <div class="container">
            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem; align-items: start;">
                
                <!-- Main Content -->
                <div>
                    <!-- Gallery -->
                    <?php if (!empty($destination_gallery)): ?>
                        <div class="tour-gallery">
                            <?php if (isset($destination_gallery[0])): ?>
                                <img src="<?= e($destination_gallery[0]) ?>" alt="<?= e($tour['title']) ?>" class="gallery-item gallery-main">
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i < min(5, count($destination_gallery)); $i++): ?>
                                <img src="<?= e($destination_gallery[$i]) ?>" alt="<?= e($tour['title']) ?>" class="gallery-item">
                            <?php endfor; ?>
                            
                            <?php if (count($destination_gallery) > 5): ?>
                                <div class="gallery-item" style="background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    +<?= count($destination_gallery) - 4 ?> fotos
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tour Header -->
                    <div class="mb-4">
                        <div class="mb-2">
                            <span class="badge badge-<?= $tour['difficulty'] === 'easy' ? 'success' : ($tour['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                                <?= e(translate_difficulty($tour['difficulty'])) ?>
                            </span>
                            <span style="margin-left: 1rem; color: var(--text-muted);">
                                📍 <?= e($tour['destination_name']) ?>, <?= e($tour['province']) ?>
                            </span>
                        </div>
                        
                        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-primary);">
                            <?= e($tour['title']) ?>
                        </h1>
                        
                        <div style="display: flex; gap: 2rem; color: var(--text-muted); margin-bottom: 2rem;">
                            <span>📅 <?= $tour['duration_days'] ?> día<?= $tour['duration_days'] > 1 ? 's' : '' ?></span>
                            <span>👥 Grupo pequeño</span>
                            <span>🎒 Aventura</span>
                        </div>
                    </div>

                    <!-- Highlights -->
                    <?php if (!empty($highlights)): ?>
                        <div class="card mb-4">
                            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">✨ Lo que incluye este tour</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                <?php foreach ($highlights as $highlight): ?>
                                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                        <span style="color: var(--primary-color); margin-top: 0.25rem;">✓</span>
                                        <span><?= e($highlight) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="card mb-4">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">📝 Descripción del Tour</h3>
                        <div style="line-height: 1.7;">
                            <?php if ($tour['description']): ?>
                                <?= nl2br(e($tour['description'])) ?>
                            <?php else: ?>
                                <?= nl2br(e($tour['destination_description'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Available Dates -->
                    <?php if (!empty($schedules)): ?>
                        <div class="card">
                            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">📅 Fechas Disponibles</h3>
                            
                            <div style="display: grid; gap: 1rem;">
                                <?php foreach ($schedules as $schedule): ?>
                                    <div class="schedule-card" style="border: 1px solid var(--gray-medium); border-radius: var(--radius-md); padding: 1rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                                    <?= format_date($schedule['start_date']) ?> - <?= format_date($schedule['end_date']) ?>
                                                </div>
                                                <div style="color: var(--text-muted); font-size: 0.875rem;">
                                                    <?= $schedule['duration_days'] ?> día<?= $schedule['duration_days'] > 1 ? 's' : '' ?> • 
                                                    <?= $schedule['seats_available'] ?> cupos disponibles
                                                </div>
                                            </div>
                                            
                                            <div style="text-align: right;">
                                                <div class="card-price" style="margin-bottom: 0.5rem;">
                                                    <?= format_price($tour['base_price'], $tour['currency']) ?>
                                                </div>
                                                <a href="/reserva.php?schedule_id=<?= $schedule['id'] ?>" class="btn btn-primary btn-small">
                                                    Reservar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">📅 Fechas Disponibles</h3>
                            <div class="alert alert-warning">
                                ⚠️ Actualmente no hay fechas disponibles para este tour. 
                                <a href="/#contacto" style="color: var(--primary-color);">Contáctanos</a> para más información.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Booking Sidebar -->
                <div>
                    <div class="booking-card">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                                Precio desde
                            </div>
                            <div class="card-price" style="font-size: 2.5rem; margin-bottom: 0.5rem;">
                                <?= format_price($tour['base_price'], $tour['currency']) ?>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                por persona
                            </div>
                        </div>

                        <?php if (!empty($schedules)): ?>
                            <!-- Quick Booking Form -->
                            <form action="/reserva.php" method="GET" style="margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Seleccionar fecha:</label>
                                    <select name="schedule_id" class="form-select" required>
                                        <option value="">Elige una fecha...</option>
                                        <?php foreach ($schedules as $schedule): ?>
                                            <option value="<?= $schedule['id'] ?>">
                                                <?= format_date($schedule['start_date']) ?> 
                                                (<?= $schedule['seats_available'] ?> cupos)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                    Reservar Ahora
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div style="text-align: center;">
                            <a href="/#contacto" class="btn btn-secondary" style="width: 100%;">
                                Consultar por WhatsApp
                            </a>
                        </div>
                        
                        <!-- Tour Features -->
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-medium);">
                            <h4 style="margin-bottom: 1rem; font-size: 1rem;">Este tour incluye:</h4>
                            <ul style="list-style: none; padding: 0;">
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: var(--primary-color);">✓</span>
                                    Guía profesional
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: var(--primary-color);">✓</span>
                                    Transporte incluido
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: var(--primary-color);">✓</span>
                                    Seguro de viaje
                                </li>
                                <li style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: var(--primary-color);">✓</span>
                                    Grupos pequeños
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Tours -->
    <?php
    // Obtener tours relacionados del mismo destino
    $sql_related = "
        SELECT t.*, d.name as destination_name 
        FROM tours t 
        LEFT JOIN destinations d ON t.destination_id = d.id 
        WHERE t.destination_id = ? AND t.id != ? AND t.is_active = 1 
        ORDER BY t.created_at DESC 
        LIMIT 3
    ";
    $stmt_related = $pdo->prepare($sql_related);
    $stmt_related->execute([$tour['destination_id'], $tour['id']]);
    $related_tours = $stmt_related->fetchAll();
    ?>

    <?php if (!empty($related_tours)): ?>
    <section class="section" style="background-color: var(--gray-light);">
        <div class="container">
            <h2 class="text-center mb-4">Otros tours en <?= e($tour['destination_name']) ?></h2>
            
            <div class="grid grid-3">
                <?php foreach ($related_tours as $related): ?>
                    <div class="card">
                        <?php if ($related['image_url']): ?>
                            <img src="<?= e($related['image_url']) ?>" alt="<?= e($related['title']) ?>" class="card-image">
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <div class="card-subtitle">
                                <?= e($related['destination_name']) ?>
                                <span class="badge badge-<?= $related['difficulty'] === 'easy' ? 'success' : ($related['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                                    <?= e(translate_difficulty($related['difficulty'])) ?>
                                </span>
                            </div>
                            
                            <h3 class="card-title"><?= e($related['title']) ?></h3>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="card-price">
                                    <?= format_price($related['base_price'], $related['currency']) ?>
                                </div>
                                <a href="/tour/<?= e($related['slug']) ?>" class="btn btn-primary btn-small">
                                    Ver Tour
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer style="background-color: var(--text-primary); color: white; padding: 3rem 0 2rem;">
        <div class="container">
            <div class="grid grid-4" style="margin-bottom: 2rem;">
                <div>
                    <h4 style="color: var(--primary-color); margin-bottom: 1rem;">ToursEC</h4>
                    <p style="opacity: 0.8;">
                        Descubre la magia del Ecuador con tours únicos y experiencias inolvidables.
                    </p>
                </div>
                
                <div>
                    <h5 style="margin-bottom: 1rem;">Contacto</h5>
                    <div style="opacity: 0.8;">
                        <p>📧 info@toursec.com</p>
                        <p>📱 +593 98 765 4321</p>
                        <p>📍 Quito, Ecuador</p>
                    </div>
                </div>
                
                <div>
                    <h5 style="margin-bottom: 1rem;">Horarios</h5>
                    <div style="opacity: 0.8;">
                        <p>Lun-Vie: 8:00-18:00</p>
                        <p>Sáb-Dom: 9:00-17:00</p>
                        <p>Respuesta en WhatsApp 24/7</p>
                    </div>
                </div>
                
                <div>
                    <h5 style="margin-bottom: 1rem;">Síguenos</h5>
                    <div style="opacity: 0.8;">
                        <p>🌐 Facebook</p>
                        <p>📷 Instagram</p>
                        <p>💼 LinkedIn</p>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 2rem; text-align: center; opacity: 0.7;">
                <p>&copy; <?= date('Y') ?> ToursEC. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
    
    <script>
        // Gallery modal functionality
        document.querySelectorAll('.gallery-item').forEach(img => {
            if (img.tagName === 'IMG') {
                img.addEventListener('click', function() {
                    const modal = document.createElement('div');
                    modal.style.cssText = `
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                        background: rgba(0,0,0,0.9); display: flex; align-items: center;
                        justify-content: center; z-index: 9999; cursor: pointer;
                    `;
                    
                    const imgClone = this.cloneNode();
                    imgClone.style.cssText = 'max-width: 90vw; max-height: 90vh; object-fit: contain;';
                    
                    modal.appendChild(imgClone);
                    document.body.appendChild(modal);
                    
                    modal.addEventListener('click', () => {
                        document.body.removeChild(modal);
                    });
                });
            }
        });
    </script>
</body>
</html>
