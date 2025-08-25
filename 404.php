<?php
/**
 * Página 404 - No encontrado
 *
 * Se emite un código de estado 404 y luego se carga el header y el footer
 * del sistema para mantener el diseño consistente en toda la web. El
 * contenido central muestra un mensaje informando que la página no se
 * encuentra y ofrece enlaces de navegación de vuelta al inicio o a los tours.
 */

http_response_code(404);

// Variables de título y descripción para la cabecera
$page_title = "Página no encontrada | ToursEC";
$page_description = "La página que buscas no existe o ha sido movida. Explora nuestros destinos y tours increíbles.";

require __DIR__ . '/inc/header.php';

?>

<main class="section" style="margin-top: 80px; min-height: 70vh; display: flex; align-items: center;">
  <div class="container">
    <div class="text-center" style="max-width: 600px; margin: 0 auto;">
      <div style="font-size: 8rem; font-weight: 800; color: var(--primary-color); margin-bottom: 1rem; line-height: 1;">
        404
      </div>
      <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-primary);">
        ¡Oops! Página no encontrada
      </h1>
      <p style="font-size: 1.125rem; color: var(--text-muted); margin-bottom: 2rem;">
        Parece que te has perdido en nuestras aventuras. La página que buscas no existe o ha sido movida.
      </p>
      <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem;">
        <a href="/" class="btn btn-primary">🏠 Volver al Inicio</a>
        <a href="/destinos.php" class="btn btn-secondary">🎒 Ver Destinos</a>
      </div>
      <!-- Buscador de respaldo -->
      <div class="card" style="text-align: left; max-width: 400px; margin: 0 auto;">
        <h3 style="margin-bottom: 1rem; color: var(--primary-color);">¿Buscas algo específico?</h3>
        <form action="/" method="GET">
          <div style="display: flex; gap: 0.5rem;">
            <input 
              type="text" 
              name="search" 
              placeholder="Buscar destinos o tours..."
              class="form-input"
              style="flex: 1;"
            >
            <button type="submit" class="btn btn-primary">
              Buscar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>