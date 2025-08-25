<?php
/**
 * Página de listado de destinos
 */

require_once 'inc/db.php';
require_once 'inc/helpers.php';

// Primero obtenemos el término de búsqueda y filtros desde la URL.
// De este modo `search` estará definido antes de que se utilice para
// determinar el título de la página.
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$province_filter = $_GET['province'] ?? '';

// Variables para el header. Utilizamos `e($search)` de helpers para escapar
// correctamente el término en el título cuando sea necesario. Si no hay
// búsqueda se define un título genérico.
$page_title = $search ? "Búsqueda: " . e($search) . " - Destinos | ToursEC" : "Destinos Increíbles del Ecuador | ToursEC";
$page_description = "Descubre todos nuestros destinos únicos en Ecuador. Desde volcanes hasta selvas amazónicas, encuentra tu próxima aventura.";

require __DIR__."/inc/header.php";

// Parámetros de paginación
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Obtener categorías para filtros
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Obtener provincias únicas para filtros
$provinces = $pdo->query("SELECT DISTINCT province FROM destinations WHERE province IS NOT NULL AND province != '' ORDER BY province")->fetchAll();

// Construir query de destinos
$where_conditions = ['d.is_active = 1'];
$params = [];

if ($search) {
    $where_conditions[] = "(d.name LIKE ? OR d.short_desc LIKE ? OR d.description LIKE ? OR d.province LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($category_filter) {
    $where_conditions[] = "d.category_id = ?";
    $params[] = $category_filter;
}

if ($province_filter) {
    $where_conditions[] = "d.province = ?";
    $params[] = $province_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Contar total para paginación
$count_sql = "
    SELECT COUNT(*) as total 
    FROM destinations d 
    LEFT JOIN categories c ON d.category_id = c.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_destinations = $count_stmt->fetch()['total'];
$total_pages = ceil($total_destinations / $limit);

// Obtener destinos
$destinations_sql = "
    SELECT d.*, c.name as category_name,
           (SELECT COUNT(*) FROM tours WHERE destination_id = d.id AND is_active = 1) as tours_count
    FROM destinations d 
    LEFT JOIN categories c ON d.category_id = c.id 
    $where_clause
    ORDER BY d.created_at DESC 
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($destinations_sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$destinations = $stmt->fetchAll();
?>



    <!-- Hero Section -->
    <section class="hero" style="height: 60vh; min-height: 400px;">
        <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&h=1080&fit=crop&q=80" 
             alt="Destinos increíbles del Ecuador" class="hero-bg">
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <h1 class="hero-title animate-fade-in-up" style="font-size: 3.5rem;">
                Destinos Únicos
            </h1>
            <p class="hero-subtitle animate-fade-in-up" style="animation-delay: 0.2s;">
                Explora la diversidad natural y cultural del Ecuador
            </p>
        </div>
    </section>

    <!-- Filters Section -->
    <section style="padding: 2rem 0; background-color: var(--white); box-shadow: var(--shadow-soft);">
        <div class="container">
            <form method="GET" class="filters-form">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                    
                    <!-- Search -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="search" class="form-label">Buscar destinos</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            value="<?= e($search) ?>"
                            placeholder="Nombre, descripción o provincia..."
                            class="form-input"
                        >
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="category" class="form-label">Categoría</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Province Filter -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="province" class="form-label">Provincia</label>
                        <select id="province" name="province" class="form-select">
                            <option value="">Todas las provincias</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= e($province['province']) ?>" <?= $province_filter === $province['province'] ? 'selected' : '' ?>>
                                    <?= e($province['province']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Submit -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <?php if ($search || $category_filter || $province_filter): ?>
                            <a href="/destinos.php" class="btn btn-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Results Section -->
    <section class="section">
        <div class="container">
            
            <!-- Results Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="margin-bottom: 0.5rem;">
                        <?php if ($search || $category_filter || $province_filter): ?>
                            Resultados de Búsqueda
                        <?php else: ?>
                            Todos los Destinos
                        <?php endif; ?>
                    </h2>
                    <p style="color: var(--text-muted); margin: 0;">
                        <?= $total_destinations ?> destino<?= $total_destinations !== 1 ? 's' : '' ?> encontrado<?= $total_destinations !== 1 ? 's' : '' ?>
                        <?php if ($search): ?>
                            para "<?= e($search) ?>"
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Sort Options -->
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span style="color: var(--text-muted); font-size: 0.875rem;">Ordenar:</span>
                    <select onchange="window.location.href = this.value" style="padding: 0.5rem; border-radius: 8px; border: 1px solid var(--gray-medium);">
                        <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'newest'])) ?>">Más recientes</option>
                        <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'name'])) ?>">Nombre A-Z</option>
                        <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'province'])) ?>">Por provincia</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($destinations)): ?>
                <!-- Destinations Grid -->
                <div class="grid grid-3">
                    <?php foreach ($destinations as $destination): ?>
                        <div class="card animate-on-scroll">
                            <?php if ($destination['image_url']): ?>
                                <div style="position: relative; overflow: hidden; border-radius: var(--radius-md) var(--radius-md) 0 0; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                                    <img src="<?= e($destination['image_url']) ?>" 
                                         alt="<?= e($destination['name']) ?>" 
                                         style="width: 100%; height: 250px; object-fit: cover; transition: transform var(--transition-normal);"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                    
                                    <?php if ($destination['tours_count'] > 0): ?>
                                        <div style="position: absolute; top: 1rem; right: 1rem; background: rgba(214, 111, 43, 0.9); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-pill); font-size: 0.875rem; font-weight: 600;">
                                            <?= $destination['tours_count'] ?> tour<?= $destination['tours_count'] > 1 ? 's' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 1rem;">
                                <?php if ($destination['category_name']): ?>
                                    <span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><?= e($destination['category_name']) ?></span>
                                <?php endif; ?>
                                
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; color: var(--text-muted);">📍</span>
                                    <span style="font-size: 0.875rem; color: var(--text-muted);"><?= e($destination['province']) ?></span>
                                </div>
                            </div>
                            
                            <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">
                                <a href="/destino/<?= e($destination['slug']) ?>" 
                                   style="text-decoration: none; color: var(--text-primary);">
                                    <?= e($destination['name']) ?>
                                </a>
                            </h3>
                            
                            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.6;">
                                <?= e(truncate($destination['short_desc'], 120)) ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <a href="/destino/<?= e($destination['slug']) ?>" class="btn btn-primary">
                                    Explorar Destino
                                </a>
                                
                                <?php if ($destination['tours_count'] > 0): ?>
                                    <a href="/destino/<?= e($destination['slug']) ?>#tours" 
                                       style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem; font-weight: 600;">
                                        Ver Tours →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 3rem; gap: 1rem;">
                        
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               class="btn btn-secondary">
                                ← Anterior
                            </a>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span style="padding: 0.5rem 1rem; background: var(--primary-color); color: white; border-radius: var(--radius-md); font-weight: 600;">
                                        <?= $i ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       style="padding: 0.5rem 1rem; border: 1px solid var(--gray-medium); border-radius: var(--radius-md); text-decoration: none; color: var(--text-primary); transition: all var(--transition-fast);"
                                       onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)'"
                                       onmouseout="this.style.borderColor='var(--gray-medium)'; this.style.color='var(--text-primary)'">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               class="btn btn-secondary">
                                Siguiente →
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                        Página <?= $page ?> de <?= $total_pages ?> - Total: <?= $total_destinations ?> destinos
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Results -->
                <div class="card text-center" style="padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                    <h3 style="color: var(--text-muted); margin-bottom: 1rem;">No se encontraron destinos</h3>
                    
                    <?php if ($search || $category_filter || $province_filter): ?>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">
                            No hay destinos que coincidan con los filtros seleccionados.
                        </p>
                        <a href="/destinos.php" class="btn btn-primary">Ver Todos los Destinos</a>
                    <?php else: ?>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">
                            Aún no hay destinos registrados en el sistema.
                        </p>
                        <a href="/" class="btn btn-primary">Volver al Inicio</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section" style="background-color: var(--gray-light);">
        <div class="container">
            <div class="card text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 3rem;">
                <h2 style="color: white; margin-bottom: 1rem;">¿No encuentras lo que buscas?</h2>
                <p style="font-size: 1.125rem; opacity: 0.9; margin-bottom: 2rem;">
                    Contáctanos y diseñaremos el tour perfecto para ti
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="/#contacto" class="btn btn-ghost">Contáctanos</a>
                    <a href="/" class="btn btn-secondary">Ver Tours</a>
                </div>
            </div>
        </div>
    </section>

<?php require __DIR__."/inc/footer.php"; ?>
