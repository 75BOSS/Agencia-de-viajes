<?php
/**
 * Página principal - Agencia de Viajes Ecuador
 * Hero section + listados de destinos y tours
 */

require_once 'inc/db.php';
require_once 'inc/helpers.php';

// Variables para el header
$page_title = $search ? "Búsqueda: " . e($search) . " - ToursEC" : "ToursEC - Descubre la magia del Ecuador";
$page_description = "Descubre los destinos más increíbles del Ecuador. Tours, campings y aventuras únicas en Baños, Quilotoa, Mindo y más.";

require __DIR__."/inc/header.php";

// Parámetros de búsqueda
$search = $_GET['search'] ?? '';
$limit = 6;

// Obtener destinos recientes
$sql_destinations = "
    SELECT d.*, c.name as category_name 
    FROM destinations d 
    LEFT JOIN categories c ON d.category_id = c.id 
    WHERE d.is_active = 1
";

if ($search) {
    $sql_destinations .= " AND (d.name LIKE ? OR d.short_desc LIKE ? OR d.province LIKE ?)";
}

$sql_destinations .= " ORDER BY d.created_at DESC LIMIT ?";

$stmt_dest = $pdo->prepare($sql_destinations);

if ($search) {
    $search_param = "%{$search}%";
    $stmt_dest->execute([$search_param, $search_param, $search_param, $limit]);
} else {
    $stmt_dest->execute([$limit]);
}

$destinations = $stmt_dest->fetchAll();

// Obtener tours recientes con información de destino
$sql_tours = "
    SELECT t.*, d.name as destination_name, d.province,
           MIN(s.start_date) as next_date,
           MIN(s.seats_total - s.seats_taken) as min_seats_available
    FROM tours t 
    LEFT JOIN destinations d ON t.destination_id = d.id
    LEFT JOIN schedules s ON t.id = s.tour_id AND s.start_date >= CURDATE()
    WHERE t.is_active = 1
";

if ($search) {
    $sql_tours .= " AND (t.title LIKE ? OR d.name LIKE ? OR t.highlights LIKE ?)";
}

$sql_tours .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT ?";

$stmt_tours = $pdo->prepare($sql_tours);

if ($search) {
    $search_param = "%{$search}%";
    $stmt_tours->execute([$search_param, $search_param, $search_param, $limit]);
} else {
    $stmt_tours->execute([$limit]);
}

$tours = $stmt_tours->fetchAll();

// Estadísticas para el hero
$stats_sql = "
    SELECT 
        (SELECT COUNT(*) FROM destinations WHERE is_active = 1) as total_destinations,
        (SELECT COUNT(*) FROM tours WHERE is_active = 1) as total_tours,
        (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed') as total_bookings
";
$stats = $pdo->query($stats_sql)->fetch();
?>



    <!-- Hero Section -->
    <section class="hero">
        <img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1920&h=1080&fit=crop&q=80" 
             alt="Paisajes espectaculares del Ecuador" class="hero-bg">
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <h1 class="hero-title reveal-text">
                Descubre la Magia del Ecuador
            </h1>
            <p class="hero-subtitle reveal-text delay-1">
                Aventuras únicas entre volcanes, selvas y paisajes que te robarán el aliento
            </p>
            
            <!-- Search Form -->
            <form id="search-form" class="hero-search reveal-text delay-2">
                <div style="display: flex; max-width: 500px; margin: 0 auto; gap: 1rem;">
                    <input 
                        type="text" 
                        id="search-tours" 
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="¿A dónde quieres ir? (Baños, Quilotoa, Mindo...)"
                        class="form-input"
                        style="flex: 1; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);"
                    >
                    <button type="submit" class="btn btn-primary btn-micro magnetic">
                        Buscar
                    </button>
                </div>
            </form>
            
            <!-- Stats -->
            <div class="hero-stats reveal-text delay-3" style="margin-top: 2rem; display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap;">
                <div class="stat-item floating" style="text-align: center; color: white; animation-delay: 0s;">
                    <div class="counter" data-target="<?= $stats['total_destinations'] ?>" style="font-size: 2.5rem; font-weight: 800;">0</div>
                    <div style="opacity: 0.9;">Destinos Únicos</div>
                </div>
                <div class="stat-item floating" style="text-align: center; color: white; animation-delay: 0.5s;">
                    <div class="counter" data-target="<?= $stats['total_tours'] ?>" style="font-size: 2.5rem; font-weight: 800;">0</div>
                    <div style="opacity: 0.9;">Tours Disponibles</div>
                </div>
                <div class="stat-item floating" style="text-align: center; color: white; animation-delay: 1s;">
                    <div class="counter" data-target="<?= $stats['total_bookings'] ?>" style="font-size: 2.5rem; font-weight: 800;">0</div>
                    <div style="opacity: 0.9;">Viajeros Felices</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Results or Featured Content -->
    <?php if ($search && (empty($destinations) && empty($tours))): ?>
        <!-- No Results -->
        <section class="section">
            <div class="container">
                <div class="text-center">
                    <h2>No encontramos resultados para "<?= e($search) ?>"</h2>
                    <p class="text-muted">Intenta con otros términos como "aventura", "naturaleza" o el nombre de una provincia.</p>
                    <a href="/" class="btn btn-primary">Ver todos los destinos</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($search && (!empty($destinations) || !empty($tours))): ?>
        <!-- Search Results -->
        <section class="section">
            <div class="container">
                <h2 class="text-center mb-4">
                    Resultados para "<?= e($search) ?>"
                </h2>
            </div>
        </section>
    <?php endif; ?>

    <!-- Destinos Section -->
    <?php if (!empty($destinations)): ?>
    <section id="destinos" class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2><?= $search ? 'Destinos Encontrados' : 'Destinos Destacados' ?></h2>
                <p class="text-muted">Lugares mágicos que no puedes perderte</p>
            </div>
            
            <div class="grid grid-3">
                <?php foreach ($destinations as $destination): ?>
                    <div class="card card-hover animate-on-scroll image-reveal">
                        <?php if ($destination['image_url']): ?>
                            <img src="<?= e($destination['image_url']) ?>" 
                                 alt="<?= e($destination['name']) ?>" 
                                 class="card-image">
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <div class="card-subtitle">
                                <?= e($destination['province']) ?>
                                <?php if ($destination['category_name']): ?>
                                    <span class="badge badge-secondary"><?= e($destination['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="card-title"><?= e($destination['name']) ?></h3>
                            
                            <p class="text-muted mb-3">
                                <?= e(truncate($destination['short_desc'], 120)) ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <a href="/destino/<?= e($destination['slug']) ?>" class="btn btn-primary btn-small btn-micro magnetic">
                                    Ver Destino
                                </a>
                                
                                <?php 
                                // Contar tours disponibles para este destino
                                $tours_count_sql = "SELECT COUNT(*) as count FROM tours WHERE destination_id = ? AND is_active = 1";
                                $stmt_count = $pdo->prepare($tours_count_sql);
                                $stmt_count->execute([$destination['id']]);
                                $tours_count = $stmt_count->fetch()['count'];
                                ?>
                                
                                <?php if ($tours_count > 0): ?>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        <?= $tours_count ?> tour<?= $tours_count > 1 ? 's' : '' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$search): ?>
                <div class="text-center mt-5">
                    <a href="/destinos.php" class="btn btn-secondary">Ver Todos los Destinos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Tours Section -->
    <?php if (!empty($tours)): ?>
    <section id="tours" class="section" style="background-color: var(--gray-light);">
        <div class="container">
            <div class="text-center mb-5">
                <h2><?= $search ? 'Tours Encontrados' : 'Tours Populares' ?></h2>
                <p class="text-muted">Experiencias diseñadas para aventureros como tú</p>
            </div>
            
            <div class="grid grid-2">
                <?php foreach ($tours as $tour): ?>
                    <div class="card card-hover animate-on-scroll image-reveal">
                        <?php if ($tour['image_url']): ?>
                            <img src="<?= e($tour['image_url']) ?>" 
                                 alt="<?= e($tour['title']) ?>" 
                                 class="card-image">
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <div class="card-subtitle">
                                <?= e($tour['destination_name']) ?>, <?= e($tour['province']) ?>
                                <span class="badge badge-<?= $tour['difficulty'] === 'easy' ? 'success' : ($tour['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                                    <?= e(translate_difficulty($tour['difficulty'])) ?>
                                </span>
                            </div>
                            
                            <h3 class="card-title"><?= e($tour['title']) ?></h3>
                            
                            <div class="tour-details mb-3">
                                <div style="display: flex; gap: 1rem; font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                                    <span>📅 <?= $tour['duration_days'] ?> día<?= $tour['duration_days'] > 1 ? 's' : '' ?></span>
                                    <?php if ($tour['next_date']): ?>
                                        <span>🗓️ Próximo: <?= format_date($tour['next_date']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($tour['min_seats_available']): ?>
                                        <span style="color: var(--primary-color);">👥 <?= $tour['min_seats_available'] ?> cupos</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php 
                                $highlights = sane_json($tour['highlights']);
                                if (!empty($highlights)): 
                                ?>
                                    <div class="highlights mb-3">
                                        <strong style="font-size: 0.875rem; color: var(--text-primary);">Incluye:</strong>
                                        <ul style="margin: 0.5rem 0 0 1rem; font-size: 0.875rem; color: var(--text-muted);">
                                            <?php foreach (array_slice($highlights, 0, 3) as $highlight): ?>
                                                <li><?= e($highlight) ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($highlights) > 3): ?>
                                                <li><em>y más...</em></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="card-price">
                                    <?= format_price($tour['base_price'], $tour['currency']) ?>
                                    <span style="font-size: 0.875rem; font-weight: 400; color: var(--text-muted);">/persona</span>
                                </div>
                                
                                <a href="/tour/<?= e($tour['slug']) ?>" class="btn btn-primary btn-micro magnetic">
                                    Ver Tour
                                </a>
                            </div>
                            
                            <?php if (!$tour['min_seats_available']): ?>
                                <div class="alert alert-warning" style="margin-top: 1rem; padding: 0.5rem; font-size: 0.875rem;">
                                    ⚠️ Sin fechas disponibles próximamente
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$search): ?>
                <div class="text-center mt-5">
                    <a href="/tours.php" class="btn btn-secondary">Ver Todos los Tours</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <?php if (!$search): ?>
    <section class="section">
        <div class="container">
            <div class="card card-glass text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 3rem;">
                <h2 style="color: white; margin-bottom: 1rem;">¿Listo para tu próxima aventura?</h2>
                <p style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 2rem;">
                    Únete a miles de viajeros que han descubierto la magia del Ecuador con nosotros
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="#tours" class="btn btn-ghost btn-micro magnetic">Explorar Tours</a>
                    <a href="/contacto.php" class="btn btn-secondary btn-micro magnetic">Contactar Ahora</a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonios Section -->
    <section class="section section-transition" style="background-color: var(--white);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="reveal-text">💬 Lo que dicen nuestros viajeros</h2>
                <p class="text-muted reveal-text delay-1">Experiencias reales de aventureros como tú</p>
            </div>
            
            <div class="testimonial-slider" style="max-width: 800px; margin: 0 auto;">
                <div class="testimonial-slide active">
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-size: 1.125rem; font-style: italic; margin-bottom: 2rem;">
                            "Una experiencia increíble en Baños. El tour estuvo perfectamente organizado y nuestro guía fue excepcional. Las aguas termales y el puenting fueron lo mejor del viaje."
                        </p>
                        <div>
                            <strong>María González</strong><br>
                            <span style="color: var(--text-muted);">Tour Aventura Extrema - Enero 2024</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-slide">
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-size: 1.125rem; font-style: italic; margin-bottom: 2rem;">
                            "El Quilotoa Loop fue una aventura que jamás olvidaré. Los paisajes son espectaculares y la organización impecable. ToursEC superó todas nuestras expectativas."
                        </p>
                        <div>
                            <strong>Carlos Mendoza</strong><br>
                            <span style="color: var(--text-muted);">Quilotoa Trekking - Febrero 2024</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-slide">
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-size: 1.125rem; font-style: italic; margin-bottom: 2rem;">
                            "Mindo es un paraíso para los amantes de la naturaleza. Vimos más de 50 especies de aves y la experiencia del canopy fue única. Totalmente recomendado."
                        </p>
                        <div>
                            <strong>Ana López</strong><br>
                            <span style="color: var(--text-muted);">Observación de Aves - Marzo 2024</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-nav">
                <div class="testimonial-dot active"></div>
                <div class="testimonial-dot"></div>
                <div class="testimonial-dot"></div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section section-transition" style="background-color: var(--gray-light);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="reveal-text">❓ Preguntas Frecuentes</h2>
                <p class="text-muted reveal-text delay-1">Resolvemos tus dudas más comunes</p>
            </div>
            
            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="faq-item">
                        <button class="faq-question">
                            ¿Qué incluyen los tours?
                        </button>
                        <div class="faq-answer">
                            <p>Nuestros tours incluyen transporte, guía profesional, entradas a los sitios, seguro de viaje y almuerzo. Los detalles específicos de cada tour se encuentran en la página de descripción.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question">
                            ¿Cómo puedo hacer una reserva?
                        </button>
                        <div class="faq-answer">
                            <p>Puedes reservar directamente en nuestra web seleccionando el tour y fecha de tu preferencia. También puedes contactarnos por WhatsApp para reservas personalizadas o grupales.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question">
                            ¿Cuál es la política de cancelación?
                        </button>
                        <div class="faq-answer">
                            <p>Ofrecemos cancelación gratuita hasta 48 horas antes del tour. Para cancelaciones con menos tiempo, se aplica una tarifa del 50% del valor total.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question">
                            ¿Qué nivel de condición física necesito?
                        </button>
                        <div class="faq-answer">
                            <p>Cada tour tiene un nivel de dificultad indicado (Fácil, Moderado, Difícil). Los tours fáciles son aptos para toda la familia, mientras que los difíciles requieren buena condición física.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <button class="faq-question">
                            ¿Proporcionan equipos de seguridad?
                        </button>
                        <div class="faq-answer">
                            <p>Sí, proporcionamos todos los equipos de seguridad necesarios para actividades de aventura como puenting, canopy, rafting, etc. Nuestro equipo está certificado y se revisa regularmente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require __DIR__."/inc/footer.php"; ?>
