<?php
require_once 'config.php';

$message = '';
$message_type = '';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('products.php');
}

// Traitement de l'inscription
if (isset($_POST['action']) ? $_POST['action'] : '' === 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password)) {
        $message = "Tous les champs sont requis.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $message = "Ce nom d'utilisateur ou cette adresse email existe déjà.";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);

                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                $message_type = 'success';

                // Redirection après 2 secondes
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e) {
            $message = "Erreur lors de l'inscription.";
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
    <title>Inscription - Évaluation de Produits</title>
    <?= getCommonCSS() ?>
</head>
<body>
<div class="container">
    <?= getNavigation() ?>

    <?php if (!empty($message)): ?>
        <?= showAlert($message, $message_type) ?>
    <?php endif; ?>

    <div class="form-container">
        <h2 class="text-center" style="margin-bottom: 2rem; color: #333;">Inscription</h2>
        <form method="POST">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : '') ?>"
                       placeholder="Entrez votre nom d'utilisateur">
            </div>

            <div class="form-group">
                <label for="email">Adresse email :</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : '') ?>"
                       placeholder="Entrez votre adresse email">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required
                       placeholder="Minimum 6 caractères">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Confirmez votre mot de passe">
            </div>

            <div class="text-center">
                <button type="submit" class="btn">S'inscrire</button>
            </div>
        </form>

        <div class="text-center" style="margin-top: 1rem;">
            <a href="index.php" style="color: #667eea; text-decoration: none;">
                Déjà inscrit ? Se connecter
            </a>
        </div>
    </div>
</div>

<?= getCommonJS() ?>

<script>
    // Validation spécifique pour l'inscription
    document.querySelector('form').addEventListener('submit', (e) => {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            document.getElementById('confirm_password').style.borderColor = '#dc3545';
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères.');
            document.getElementById('password').style.borderColor = '#dc3545';
            return;
        }
    });

    // Validation en temps réel des mots de passe
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;

        if (password && confirmPassword) {
            if (password === confirmPassword) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        }
    });
</script>
</body>
</html>