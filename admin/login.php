<?php
require __DIR__.'/../inc/db.php';
session_start();
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=strtolower(trim($_POST['email']??''));
  $pass=$_POST['password']??'';
  try{
    $st=$pdo->prepare("SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    $u=$st->fetch();
    if($u && password_verify($pass,$u['password_hash'])){
      $_SESSION['uid']=$u['id']; $_SESSION['name']=$u['name']; $_SESSION['role']=$u['role'];
      header('Location: dashboard.php'); exit;
    }else{ $error="Credenciales incorrectas."; }
  }catch(Exception $e){ $error="Error de servidor."; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ToursEC</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); min-height: 100vh; display: flex; align-items: center; justify-content: center;">

    <div class="card" style="width: 100%; max-width: 400px; margin: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">ToursEC Admin</h1>
            <p style="color: var(--text-muted);">Panel de Administración</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    placeholder="admin@campingec.com"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input"
                    required
                    placeholder="Tu contraseña"
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                Iniciar Sesión
            </button>
        </form>

        <div style="text-align: center; padding-top: 1rem; border-top: 1px solid var(--gray-medium);">
            <div style="font-size: 0.875rem; color: var(--text-muted);">
                <strong>Credenciales de prueba:</strong><br>
                Email: admin@campingec.com<br>
                Contraseña: admin123
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="/" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem;">
                ← Volver al sitio web
            </a>
        </div>
    </div>

</body>
</html>
