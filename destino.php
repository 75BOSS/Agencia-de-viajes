<?php
/**
 * Página de detalle de destino individual
 */

require_once 'inc/db.php';
require_once 'inc/helpers.php';

// Obtener slug del destino de la URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /destinos.php");
    exit;
}

// Variables para el header
$page_title = "Destino | ToursEC";
$page_description = "Descubre destino único en Ecuador";

require __DIR__."/inc/header.php";

// Obtener información del destino
$sql_destination = "
    SELECT d.*, c.name as category_name, c.slug as category_slug
    FROM destinations d 
    LEFT JOIN categories c ON d.category_id = c.id 
    WHERE d.slug = ? AND d.is_active = 1
";

$stmt_destination = $pdo->prepare($sql_destination);
$stmt_destination->execute([$slug]);
$destination = $stmt_destination->fetch();

if (!$destination) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Obtener tours de este destino
$sql_tours = "
    SELECT t.*, 
           MIN(s.start_date) as next_date,
           MIN(s.seats_total - s.seats_taken) as min_seats_available,
           COUNT(s.id) as schedules_count
    FROM tours t 
    LEFT JOIN schedules s ON t.id = s.tour_id AND s.start_date >= CURDATE()
    WHERE t.destination_id = ? AND t.is_active = 1
    GROUP BY t.id 
    ORDER BY t.created_at DESC
";

$stmt_tours = $pdo->prepare($sql_tours);
$stmt_tours->execute([$destination['id']]);
$tours = $stmt_tours->fetchAll();

// Obtener galería del destino
$gallery = sane_json($destination['gallery']);
if (!is_array($gallery)) {
    $gallery = [];
}

// Agregar imagen principal al inicio si no está en la galería
if ($destination['image_url'] && !in_array($destination['image_url'], $gallery)) {
    array_unshift($gallery, $destination['image_url']);
}

// Meta tags para SEO
$page_title = e($destination['name']) . ", " . e($destination['province']);
$page_description = e(truncate($destination['description'] ?? $destination['short_desc'] ?? '', 160));
?>



    <!-- Hero Section -->
    <section class="destination-hero">
        <img src="<?= e($destination['image_url']) ?>" alt="<?= e($destination['name']) ?>" class="destination-hero-bg">
        <div class="destination-hero-overlay"></div>
        
        <div class="destination-hero-content">
            <div style="margin-bottom: 1rem;">
                <?php if ($destination['category_name']): ?>
                    <span class="badge badge-primary" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <?= e($destination['category_name']) ?>
                    </span>
                <?php endif; ?>
                <span style="margin-left: 1rem; color: rgba(255,255,255,0.9);">📍 <?= e($destination['province']) ?></span>
            </div>
            
            <h1 style="font-size: 4rem; margin-bottom: 1rem; text-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                <?= e($destination['name']) ?>
            </h1>
            
            <p style="font-size: 1.25rem; opacity: 0.9; max-width: 600px; margin: 0 auto; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                <?= e($destination['short_desc']) ?>
            </p>
        </div>
    </section>

    <!-- Breadcrumb -->
    <section style="background-color: var(--gray-light); padding: 1rem 0;">
        <div class="container">
            <nav style="font-size: 0.875rem; color: var(--text-muted);">
                <a href="/" style="color: var(--primary-color); text-decoration: none;">Inicio</a>
                <span> › </span>
                <a href="/destinos.php" style="color: var(--primary-color); text-decoration: none;">Destinos</a>
                <?php if ($destination['category_name']): ?>
                    <span> › </span>
                    <a href="/destinos.php?category=<?= $destination['category_id'] ?>" style="color: var(--primary-color); text-decoration: none;">
                        <?= e($destination['category_name']) ?>
                    </a>
                <?php endif; ?>
                <span> › </span>
                <span><?= e($destination['name']) ?></span>
            </nav>
        </div>
    </section>

    <!-- Destination Details -->
    <section class="section">
        <div class="container">
            
            <!-- Gallery -->
            <?php if (count($gallery) > 1): ?>
                <div class="destination-gallery">
                    <?php if (isset($gallery[0])): ?>
                        <img src="<?= e($gallery[0]) ?>" alt="<?= e($destination['name']) ?>" class="gallery-item gallery-main">
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i < min(5, count($gallery)); $i++): ?>
                        <img src="<?= e($gallery[$i]) ?>" alt="<?= e($destination['name']) ?>" class="gallery-item">
                    <?php endfor; ?>
                    
                    <?php if (count($gallery) > 5): ?>
                        <div class="gallery-item" style="background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; cursor: pointer;">
                            +<?= count($gallery) - 4 ?> fotos
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (isset($gallery[0])): ?>
                <div style="margin-bottom: 2rem;">
                    <img src="<?= e($gallery[0]) ?>" alt="<?= e($destination['name']) ?>" 
                         style="width: 100%; height: 400px; object-fit: cover; border-radius: var(--radius-lg);">
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem; align-items: start;">
                
                <!-- Main Content -->
                <div>
                    <!-- Description -->
                    <div class="card mb-4">
                        <h2 style="color: var(--primary-color); margin-bottom: 1rem;">📍 Sobre <?= e($destination['name']) ?></h2>
                        <div style="line-height: 1.8; font-size: 1.125rem;">
                            <?php if ($destination['description']): ?>
                                <?= nl2br(e($destination['description'])) ?>
                            <?php else: ?>
                                <?= nl2br(e($destination['short_desc'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tours Section -->
                    <?php if (!empty($tours)): ?>
                        <div id="tours">
                            <h2 style="color: var(--primary-color); margin-bottom: 2rem;">🎒 Tours en <?= e($destination['name']) ?></h2>
                            
                            <div style="display: grid; gap: 1.5rem;">
                                <?php foreach ($tours as $tour): ?>
                                    <div class="card" style="display: grid; grid-template-columns: 250px 1fr auto; gap: 1.5rem; align-items: center;">
                                        
                                        <!-- Tour Image -->
                                        <?php if ($tour['image_url']): ?>
                                            <img src="<?= e($tour['image_url']) ?>" 
                                                 alt="<?= e($tour['title']) ?>"
                                                 style="width: 250px; height: 180px; object-fit: cover; border-radius: var(--radius-md);">
                                        <?php else: ?>
                                            <div style="width: 250px; height: 180px; background: var(--gray-medium); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                                Sin imagen
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Tour Info -->
                                        <div>
                                            <div style="margin-bottom: 0.5rem;">
                                                <span class="badge badge-<?= $tour['difficulty'] === 'easy' ? 'success' : ($tour['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                                                    <?= e(translate_difficulty($tour['difficulty'])) ?>
                                                </span>
                                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                                    📅 <?= $tour['duration_days'] ?> día<?= $tour['duration_days'] > 1 ? 's' : '' ?>
                                                </span>
                                            </div>
                                            
                                            <h3 style="margin-bottom: 0.75rem; font-size: 1.25rem;">
                                                <a href="/tour/<?= e($tour['slug']) ?>" style="text-decoration: none; color: var(--text-primary);">
                                                    <?= e($tour['title']) ?>
                                                </a>
                                            </h3>
                                            
                                            <?php 
                                            $highlights = sane_json($tour['highlights']);
                                            if (!empty($highlights)): 
                                            ?>
                                                <div style="margin-bottom: 1rem;">
                                                    <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.25rem;">Incluye:</div>
                                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                                        <?= e(implode(' • ', array_slice($highlights, 0, 3))) ?>
                                                        <?php if (count($highlights) > 3): ?>
                                                            <span style="color: var(--text-muted);"> y más...</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.875rem; color: var(--text-muted);">
                                                <?php if ($tour['next_date']): ?>
                                                    <span>🗓️ Próximo: <?= format_date($tour['next_date']) ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($tour['schedules_count'] > 0): ?>
                                                    <span>📅 <?= $tour['schedules_count'] ?> fecha<?= $tour['schedules_count'] > 1 ? 's' : '' ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($tour['min_seats_available']): ?>
                                                    <span style="color: var(--primary-color);">👥 <?= $tour['min_seats_available'] ?> cupos</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Tour Action -->
                                        <div style="text-align: center;">
                                            <div style="margin-bottom: 1rem;">
                                                <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary-color);">
                                                    <?= format_price($tour['base_price'], $tour['currency']) ?>
                                                </div>
                                                <div style="font-size: 0.875rem; color: var(--text-muted);">por persona</div>
                                            </div>
                                            
                                            <a href="/tour/<?= e($tour['slug']) ?>" class="btn btn-primary">
                                                Ver Tour
                                            </a>
                                            
                                            <?php if (!$tour['min_seats_available']): ?>
                                                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                                                    Sin fechas disponibles
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card text-center" style="padding: 2rem;">
                            <h3 style="color: var(--text-muted); margin-bottom: 1rem;">Tours Próximamente</h3>
                            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                                Estamos preparando tours increíbles para este destino.
                            </p>
                            <a href="/#contacto" class="btn btn-primary">Consultar Tours Personalizados</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Quick Info -->
                    <div class="card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">ℹ️ Información Rápida</h3>
                        
                        <div style="display: grid; gap: 1rem;">
                            <div>
                                <strong>Provincia:</strong><br>
                                <span style="color: var(--text-muted);"><?= e($destination['province']) ?></span>
                            </div>
                            
                            <?php if ($destination['category_name']): ?>
                                <div>
                                    <strong>Categoría:</strong><br>
                                    <span class="badge badge-secondary"><?= e($destination['category_name']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <strong>Tours Disponibles:</strong><br>
                                <span style="color: var(--primary-color); font-weight: 600; font-size: 1.125rem;">
                                    <?= count($tours) ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($tours)): ?>
                                <div>
                                    <strong>Desde:</strong><br>
                                    <span style="color: var(--primary-color); font-weight: 600; font-size: 1.25rem;">
                                        <?= format_price(min(array_column($tours, 'base_price')), $tours[0]['currency']) ?>
                                    </span>
                                    <span style="font-size: 0.875rem; color: var(--text-muted);">por persona</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact CTA -->
                    <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; text-align: center;">
                        <h4 style="color: white; margin-bottom: 1rem;">¿Necesitas más información?</h4>
                        <p style="opacity: 0.9; margin-bottom: 1.5rem;">
                            Nuestros expertos están listos para ayudarte a planificar tu aventura perfecta.
                        </p>
                        <a href="/#contacto" class="btn btn-ghost" style="width: 100%;">
                            📱 Contáctanos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Destinations -->
    <?php
    // Obtener destinos relacionados de la misma provincia o categoría
    $related_sql = "
        SELECT d.*, c.name as category_name,
               (SELECT COUNT(*) FROM tours WHERE destination_id = d.id AND is_active = 1) as tours_count
        FROM destinations d 
        LEFT JOIN categories c ON d.category_id = c.id 
        WHERE d.id != ? AND d.is_active = 1 
        AND (d.province = ? OR d.category_id = ?)
        ORDER BY (d.province = ?) DESC, d.created_at DESC 
        LIMIT 3
    ";
    $stmt_related = $pdo->prepare($related_sql);
    $stmt_related->execute([
        $destination['id'], 
        $destination['province'], 
        $destination['category_id'],
        $destination['province']
    ]);
    $related_destinations = $stmt_related->fetchAll();
    ?>

    <?php if (!empty($related_destinations)): ?>
    <section class="section" style="background-color: var(--gray-light);">
        <div class="container">
            <h2 class="text-center mb-4">Otros destinos que te pueden interesar</h2>
            
            <div class="grid grid-3">
                <?php foreach ($related_destinations as $related): ?>
                    <div class="card">
                        <?php if ($related['image_url']): ?>
                            <img src="<?= e($related['image_url']) ?>" 
                                 alt="<?= e($related['name']) ?>" 
                                 class="card-image">
                        <?php endif; ?>
                        
                        <div>
                            <div style="margin-bottom: 1rem;">
                                <?php if ($related['category_name']): ?>
                                    <span class="badge badge-secondary"><?= e($related['category_name']) ?></span>
                                <?php endif; ?>
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                    📍 <?= e($related['province']) ?>
                                </span>
                            </div>
                            
                            <h3 class="card-title"><?= e($related['name']) ?></h3>
                            
                            <p style="color: var(--text-muted); margin-bottom: 1rem;">
                                <?= e(truncate($related['short_desc'], 100)) ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <a href="/destino/<?= e($related['slug']) ?>" class="btn btn-primary btn-small">
                                    Explorar
                                </a>
                                
                                <?php if ($related['tours_count'] > 0): ?>
                                    <span style="font-size: 0.875rem; color: var(--text-muted);">
                                        <?= $related['tours_count'] ?> tour<?= $related['tours_count'] > 1 ? 's' : '' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="/destinos.php" class="btn btn-secondary">Ver Todos los Destinos</a>
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

        // Responsive tour cards
        function adjustTourCards() {
            const tourCards = document.querySelectorAll('[style*="grid-template-columns: 250px 1fr auto"]');
            tourCards.forEach(card => {
                if (window.innerWidth <= 768) {
                    card.style.gridTemplateColumns = '1fr';
                    card.style.textAlign = 'center';
                } else {
                    card.style.gridTemplateColumns = '250px 1fr auto';
                    card.style.textAlign = 'left';
                }
            });
        }

        window.addEventListener('resize', adjustTourCards);
        adjustTourCards();
    </script>
</body>
</html>
