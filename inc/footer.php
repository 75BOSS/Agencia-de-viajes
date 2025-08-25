<footer class="site-footer">
  <div class="container">© <?=date('Y')?> ToursEC — Todos los derechos reservados</div>
</footer>
<!--
  Utilizamos ruta absoluta para el script del menú móvil para
  evitar fallos cuando se carga desde subdirectorios. Con `/` al
  inicio se busca siempre en la raíz del dominio.
-->
<script src="/assets/js/menu.js"></script>
<!--
  Cargamos también el script principal de la aplicación (app.js) para
  inicializar animaciones, parallax y otras interacciones. Al usar
  rutas absolutas nos aseguramos de que el archivo se cargue
  correctamente desde cualquier ruta.
-->
<script src="/assets/js/app.js"></script>
</body></html>
