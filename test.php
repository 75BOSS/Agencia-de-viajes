<?php
// Variables para el header
$page_title = "Test - ToursEC";
$page_description = "Página de prueba para verificar estilos";

require __DIR__."/inc/header.php";
?>

<main style="padding: 2rem 0;">
    <div class="container">
        <h1 class="h1">✅ Test de Estilos</h1>
        <p>Si ves esta página con estilos aplicados, todo funciona correctamente.</p>
        
        <div class="grid grid-2" style="margin: 2rem 0;">
            <div class="card" style="padding: 1.5rem;">
                <h3>Navbar Móvil</h3>
                <p>En móvil (&lt;1024px): Botón hamburguesa visible</p>
                <p>En desktop (≥1024px): Menú horizontal visible</p>
            </div>
            
            <div class="card" style="padding: 1.5rem;">
                <h3>Imágenes</h3>
                <p>Las proporciones de imágenes deben estar corregidas</p>
                <img src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=400&h=200&fit=crop" alt="Test" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
            </div>
        </div>
        
        <div class="stats" style="margin: 2rem 0;">
            <div style="background: var(--pill); padding: 1rem; border-radius: var(--radius); text-align: center;">
                <strong>CSS</strong><br>✅ Cargado
            </div>
            <div style="background: var(--pill); padding: 1rem; border-radius: var(--radius); text-align: center;">
                <strong>JavaScript</strong><br>✅ Cargado
            </div>
            <div style="background: var(--pill); padding: 1rem; border-radius: var(--radius); text-align: center;">
                <strong>Fuentes</strong><br>✅ Inter
            </div>
        </div>
        
        <button class="btn" style="margin: 1rem 0;">Botón de Prueba</button>
        <button class="btn secondary">Botón Secundario</button>
    </div>
</main>

<?php require __DIR__."/inc/footer.php"; ?>
