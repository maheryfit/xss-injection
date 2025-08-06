<?php
require_once 'config.php';

$message = '';
$message_type = '';

$connection = pg_connect("host=localhost port=5432 user=postgres password=postgres dbname=product_evaluation");


// Traitement de la déconnexion
if (($_GET['action'] ?? '') === 'logout') {
    session_destroy();
    redirect('products.php');
}

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('products.php');
}

// Traitement de la connexion
if ($_POST['action'] ?? '' === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Tous les champs sont requis.";
        $message_type = 'error';
    } else {
        try {
            /*
            // Ne Fonctionne pas pour l'injection SQL
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Redirection vers les produits
                redirect('products.php?login_success=1');
            } else {
                $message = "Nom d'utilisateur/email ou mot de passe incorrect.";
                $message_type = 'error';
            }
            */
            // Fonctionne pour l'injection SQL
            $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
            $result = $pdo->query($sql);
            $user = $result->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                redirect('products.php?login_success=1');
            } else {
                $message = "Nom d'utilisateur/email ou mot de passe incorrect.";
                $message_type = 'error';
            }
        } catch(PDOException $e) {
            $message = "Erreur lors de la connexion.";
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Évaluation de Produits</title>
    <?= getCommonCSS() ?>
</head>
<body>
<div class="container">
    <?= getNavigation() ?>

    <?php if (!empty($message)): ?>
        <?= showAlert($message, $message_type) ?>
    <?php endif; ?>

    <div class="form-container">
        <h2 class="text-center" style="margin-bottom: 2rem; color: #333;">Connexion</h2>
        <form method="POST">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="username">Nom d'utilisateur ou Email :</label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : '') ?>"
                       placeholder="Entrez votre nom d'utilisateur ou email">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required
                       placeholder="Entrez votre mot de passe">
            </div>

            <div class="text-center">
                <button type="submit" class="btn">Se connecter</button>
            </div>
        </form>

        <div class="text-center" style="margin-top: 1rem;">
            <a href="register.php" style="color: #667eea; text-decoration: none;">
                Pas encore inscrit ? S'inscrire
            </a>
        </div>
    </div>

    <!-- Section informative -->
    <div class="form-container">
        <h3 style="color: #333; margin-bottom: 1rem;">Comptes de démonstration</h3>
        <p style="color: #666; margin-bottom: 1rem;">
            Vous pouvez utiliser ces comptes pour tester l'application :
        </p>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; font-family: monospace;">
            <strong>Utilisateur :</strong> admin<br>
            <strong>Mot de passe :</strong> password<br><br>
            <strong>Utilisateur :</strong> john_doe<br>
            <strong>Mot de passe :</strong> password
        </div>
    </div>
</div>

<?= getCommonJS() ?>

<script>
    // Animation d'entrée pour le formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const formContainer = document.querySelector('.form-container');
        formContainer.style.transform = 'translateY(20px)';
        formContainer.style.opacity = '0';
        formContainer.style.transition = 'all 0.6s ease';

        setTimeout(() => {
            formContainer.style.transform = 'translateY(0)';
            formContainer.style.opacity = '1';
        }, 200);
    });

    // Focus automatique sur le premier champ
    document.getElementById('username').focus();

    // Gestion de la touche Entrée
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const form = document.querySelector('form');
            if (form) {
                form.submit();
            }
        }
    });
</script>
</body>
</html>