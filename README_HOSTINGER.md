# 🌎 Agencia de Viajes Ecuador - Guía de Despliegue en Hostinger

## 📋 Resumen del Proyecto

Sistema completo de agencia de viajes desarrollado específicamente para **hosting compartido de Hostinger**, con diseño minimalista en tonos terracota que replica el mockup solicitado.

### ✨ Características Principales

- **Frontend**: HTML5, CSS3 puro, JavaScript vanilla
- **Backend**: PHP 8.x sin frameworks, MySQL con PDO
- **Diseño**: Paleta terracota, cards redondeadas, botones pill, glassmorphism
- **Funcionalidades**: Tours, destinos, reservas, panel admin completo
- **Seguridad**: CSRF tokens, prepared statements, validaciones server-side

---

## 🚀 Pasos de Despliegue en Hostinger

### 1. Preparar la Base de Datos

#### 1.1 Crear Base de Datos en hPanel
1. Accede a tu **hPanel de Hostinger**
2. Ve a **Bases de Datos MySQL**
3. Crea una nueva base de datos con estos datos exactos:
   ```
   Nombre: u240362798_ToursEc
   Usuario: u240362798_ToursEc13
   Contraseña: ToursEc1311
   ```

#### 1.2 Importar Estructura y Datos
1. Accede a **phpMyAdmin** desde hPanel
2. Selecciona la base de datos `u240362798_ToursEc`
3. Ve a la pestaña **Importar**
4. Sube el archivo `database.sql`
5. Haz clic en **Continuar**

> **✅ Verificación**: Deberías ver las siguientes tablas creadas:
> - `users` (1 admin creado)
> - `categories` (3 categorías)
> - `destinations` (3 destinos de ejemplo)
> - `tours` (3 tours de ejemplo)
> - `schedules` (10 fechas programadas)
> - `reservations` (4 reservas de ejemplo)

### 2. Subir Archivos del Proyecto

#### 2.1 Via File Manager de Hostinger
1. Accede a **File Manager** en hPanel
2. Ve a la carpeta `public_html`
3. Sube **todo el contenido** de la carpeta `agencia-viajes/` directamente a `public_html`

#### 2.2 Via FTP (Alternativo)
```
Host: ftp.tudominio.com
Usuario: tu_usuario_hostinger
Contraseña: tu_contraseña
Puerto: 21
```

#### 2.3 Estructura Final en public_html
```
public_html/
├── index.php                 # Página principal
├── tour.php                  # Detalle de tour
├── reserva.php               # Sistema de reservas
├── 404.php                   # Página de error
├── .htaccess                 # URLs amigables
├── database.sql              # Script de BD
├── README_HOSTINGER.md       # Esta guía
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── logout.php
│   ├── tabs/
│   └── actions/
├── inc/
│   ├── db.php               # ✅ Preconfigurado con credenciales
│   ├── auth.php
│   └── helpers.php
└── assets/
    ├── css/styles.css       # Design system terracota
    └── js/app.js
```

### 3. Verificar Configuración

#### 3.1 Probar Conexión a BD
1. Visita: `https://tudominio.com/`
2. Si ves la página principal sin errores = ✅ Conexión OK

#### 3.2 Probar Panel Admin
1. Visita: `https://tudominio.com/admin/login.php`
2. Credenciales:
   ```
   Email: admin@campingec.com
   Contraseña: admin123
   ```
3. Deberías acceder al dashboard con estadísticas

### 4. Configuraciones Avanzadas (Opcional)

#### 4.1 SSL/HTTPS
En hPanel → **SSL/TLS**:
- Activar SSL gratuito
- Forzar HTTPS (ya configurado en .htaccess)

#### 4.2 Dominio Personalizado
Si tienes dominio propio:
1. Actualizar DNS en tu registrador
2. Configurar en hPanel → **Dominios**

#### 4.3 Email (Para confirmaciones de reserva)
```php
// En reserva.php línea ~95, descomentar y configurar:
// mail($customer_email, "Confirmación de Reserva", $email_body);
```

---

## 🎯 URLs Importantes

| Función | URL | Descripción |
|---------|-----|-------------|
| **Home** | `/` | Página principal con hero, testimonios y FAQ |
| **Destinos** | `/destinos.php` | Listado completo con filtros y paginación |
| **Destino** | `/destino/banos-agua-santa` | Detalle de destino específico |
| **Tour** | `/tour/aventura-extrema-banos-3-dias` | Detalle de tour específico |
| **Reservas** | `/reserva.php?schedule_id=1` | Proceso de reserva |
| **Admin** | `/admin/login.php` | Panel de administración (🔐 botón en navbar) |
| **phpMyAdmin** | Desde hPanel | Gestión de base de datos |

---

## 🔧 Funcionalidades del Sistema

### 🌐 Frontend Público
- ✅ **Hero Section**: Imagen full-width con overlay terracota cálido y parallax
- ✅ **Búsqueda**: Por destinos y tours con filtros LIKE
- ✅ **Listado Destinos**: Página completa con filtros, paginación y búsqueda
- ✅ **Detalle Destino**: Galería, información y tours asociados
- ✅ **Cards de Tours**: Precio, dificultad, highlights, disponibilidad con hover effects
- ✅ **Detalle de Tour**: Galería, información completa, fechas disponibles
- ✅ **Reservas**: Formulario completo con validación y confirmación
- ✅ **Testimonios**: Slider automático con navegación por dots
- ✅ **FAQ Accordion**: Preguntas frecuentes expandibles
- ✅ **Animaciones**: Estilo Mugen Studio con scroll-triggered reveals
- ✅ **Responsive**: Mobile-first, grid adaptativo
- ✅ **Acceso Admin**: Botón 🔐 Admin visible en navegación

### 🔐 Panel de Administración
- ✅ **Dashboard**: Estadísticas en tiempo real
- ✅ **Destinos**: CRUD completo con categorías
- ✅ **Tours**: Gestión con highlights JSON, precios, dificultad
- ✅ **Fechas**: Calendario de salidas, gestión de cupos
- ✅ **Reservas**: Estados (pending/confirmed/cancelled), filtros

### 🛡️ Seguridad Implementada
- ✅ **PDO Prepared Statements**: Protección contra SQL injection
- ✅ **CSRF Tokens**: En todos los formularios importantes
- ✅ **XSS Protection**: htmlspecialchars() en todas las salidas
- ✅ **Validación Server-Side**: Doble validación en PHP
- ✅ **Sesiones Seguras**: session_regenerate_id()

---

## 📊 Datos de Ejemplo Incluidos

### 🏔️ Destinos
1. **Baños de Agua Santa** - Tungurahua
2. **Quilotoa** - Cotopaxi  
3. **Mindo Cloud Forest** - Pichincha

### 🎒 Tours
1. **Aventura Extrema en Baños 3 Días** - $299 USD
2. **Quilotoa Loop Trekking 2 Días** - $199 USD
3. **Observación de Aves en Mindo 2 Días** - $159 USD

### 📅 Fechas Programadas
- **10 fechas futuras** distribuidas entre febrero-marzo 2024
- **Cupos variados** (8-20 personas por tour)
- **Estados realistas** (algunos con reservas, otros disponibles)

---

## 🎨 Design System Implementado

### 🎨 Paleta de Colores
```css
--primary-color: #d66f2b      /* Terracota principal */
--primary-dark: #b9591f       /* Terracota oscuro */
--secondary-color: #f4f1eb    /* Arena claro (fondo) */
--text-primary: #1f1f1f       /* Texto principal */
```

### 📐 Elementos de Diseño
- **Border Radius**: 18-28px (redondeado suave)
- **Box Shadow**: Sombras suaves terracota
- **Botones**: Estilo "pill" con hover effects
- **Cards**: Glass/blur effects con elevación sutil
- **Tipografía**: Inter font, escalas grandes para títulos

---

## 🔍 Testing y Verificación

### ✅ Checklist Post-Despliegue

#### Frontend
- [ ] Página principal carga sin errores
- [ ] Hero muestra imagen con overlay terracota
- [ ] Tours se muestran con precios y disponibilidad
- [ ] Búsqueda funciona correctamente
- [ ] Detalle de tour muestra información completa
- [ ] Responsive funciona en móvil

#### Sistema de Reservas
- [ ] Formulario de reserva valida campos
- [ ] Se crean reservas con estado "pending"
- [ ] Confirmación muestra datos correctos
- [ ] Cupos se actualizan automáticamente

#### Panel Admin
- [ ] Login funciona con credenciales de prueba
- [ ] Dashboard muestra estadísticas reales
- [ ] CRUD de destinos funciona completo
- [ ] CRUD de tours funciona completo
- [ ] Gestión de fechas permite crear/editar
- [ ] Cambio de estado de reservas funciona
- [ ] Transacciones mantienen consistencia de datos

---

## 🚨 Solución de Problemas Comunes

### ❌ Error de Conexión a BD
```
Error: SQLSTATE[HY000] [1045] Access denied
```
**Solución**: Verificar credenciales en `inc/db.php`

### ❌ Página en Blanco
```
Error 500 Internal Server Error
```
**Solución**: 
1. Verificar PHP error logs en hPanel
2. Confirmar que PHP 8.x está habilitado
3. Verificar permisos de archivos (644 para PHP)

### ❌ CSS/JS no Cargan
```
404 Not Found para assets/
```
**Solución**: Verificar que carpeta `assets/` se subió correctamente

### ❌ URLs Amigables no Funcionan
```
/tour/slug-del-tour da 404
```
**Solución**: Verificar que `.htaccess` se subió y mod_rewrite está habilitado

---

## 📞 Soporte Post-Despliegue

### 📧 Contacto Técnico
- **Email**: admin@campingec.com (admin por defecto)
- **Panel**: `/admin/login.php`

### 🔧 Mantenimiento Recomendado
- **Backup BD**: Semanal via phpMyAdmin
- **Logs de Error**: Revisar mensualmente en hPanel
- **Actualizaciones**: PHP y MySQL automáticas en Hostinger

### 📈 Escalabilidad Futura
- **CDN**: Cloudflare gratuito para imágenes
- **Email Marketing**: Integrar MailChimp para newsletters
- **Pagos**: PayPal/Stripe para reservas online
- **WhatsApp API**: Automatizar confirmaciones

---

## 🎉 ¡Listo para Producción!

Tu agencia de viajes está lista para recibir clientes. El sistema incluye:

✅ **3 destinos** configurados con tours reales  
✅ **10 fechas** programadas para reservas inmediatas  
✅ **Panel admin** completo para gestión diaria  
✅ **Diseño profesional** que convierte visitantes en clientes  
✅ **Sistema seguro** listo para datos reales  

**Next Steps**:
1. Personalizar contenido con tus destinos reales
2. Configurar dominio personalizado
3. Activar confirmaciones por email
4. ¡Comenzar a vender tours! 🚀

---

*Desarrollado específicamente para Hostinger - Sin dependencias externas - 100% compatible*
