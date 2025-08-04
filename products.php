<?php
require_once 'config.php';

$message = '';
$message_type = '';

// Message de bienvenue après connexion
if (isset($_GET['login_success'])) {
    $message = "Connexion réussie ! Bienvenue " . htmlspecialchars($_SESSION['username']) . ".";
    $message_type = 'success';
}

// Traitement de l'ajout de commentaire
if (isset($_POST['action']) ? $_POST['action'] : '' === 'add_comment') {
    if (!isLoggedIn()) {
        $message = "Vous devez être connecté pour ajouter un commentaire.";
        $message_type = 'error';
    } else {
        $product_id = (int)$_POST['product_id'];
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);

        if ($rating < 1 || $rating > 5) {
            $message = "La note doit être comprise entre 1 et 5.";
            $message_type = 'error';
        } elseif (empty($comment)) {
            $message = "Le commentaire ne peut pas être vide.";
            $message_type = 'error';
        } elseif (strlen($comment) < 10) {
            $message = "Le commentaire doit contenir au moins 10 caractères.";
            $message_type = 'error';
        } else {
            try {
                // Vérifier si l'utilisateur a déjà commenté ce produit
                $stmt = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);

                if ($stmt->fetch()) {
                    $message = "Vous avez déjà commenté ce produit.";
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO comments (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $product_id, $rating, $comment]);

                    $message = "Votre commentaire a été ajouté avec succès.";
                    $message_type = 'success';
                }
            } catch(PDOException $e) {
                $message = "Erreur lors de l'ajout du commentaire.";
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - Évaluation de Produits</title>
    <?= getCommonCSS() ?>
</head>
<body>
<div class="container">
    <?= getNavigation() ?>

    <?php if (!empty($message)): ?>
        <?= showAlert($message, $message_type) ?>
    <?php endif; ?>

    <div class="products-container">
        <h2 style="text-align: center; margin-bottom: 2rem; color: #333;">Nos Produits</h2>

        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                <strong>💡 Astuce :</strong>
                <a href="index.php" style="color: #0c5460;">Connectez-vous</a> ou
                <a href="register.php" style="color: #0c5460;">inscrivez-vous</a>
                pour pouvoir commenter et noter les produits !
            </div>
        <?php endif; ?>

        <div class="products-grid">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($products as $product):
                    // Récupérer les commentaires pour ce produit
                    $stmt_comments = $pdo->prepare("
                            SELECT c.*, u.username 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.product_id = ? 
                            ORDER BY c.created_at DESC
                        ");
                    $stmt_comments->execute([$product['id']]);
                    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

                    // Calculer la note moyenne
                    $avg_rating = 0;
                    if (!empty($comments)) {
                        $total_rating = array_sum(array_column($comments, 'rating'));
                        $avg_rating = round($total_rating / count($comments), 1);
                    }
                    ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="product-image"
                        >

                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price"><?= number_format($product['price'], 2) ?>€</div>
                            <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>

                            <?php if (!empty($comments)): ?>
                                <div style="margin-bottom: 1rem;">
                                    <strong>Note moyenne : </strong>
                                    <span style="color: #ffc107;">
                                        <?= str_repeat('⭐', floor($avg_rating)) ?>
                                        <?= $avg_rating > floor($avg_rating) ? '⭐' : '' ?>
                                    </span>
                                    <span>(<?= $avg_rating ?>/5 - <?= count($comments) ?> avis)</span>
                                </div>
                            <?php endif; ?>

                            <div class="comments-section">
                                <h4 style="margin-bottom: 1rem; color: #333;">
                                    Commentaires (<?= count($comments) ?>)
                                </h4>

                                <?php if (empty($comments)): ?>
                                    <p style="color: #666; font-style: italic;">
                                        Aucun commentaire pour ce produit. Soyez le premier à donner votre avis !
                                    </p>
                                <?php else: ?>
                                    <?php foreach (array_slice($comments, 0, 3) as $comment): ?>
                                        <div class="comment">
                                            <div class="comment-header">
                                                <span class="comment-author">
                                                    <?= htmlspecialchars($comment['username']) ?>
                                                </span>
                                                <span class="comment-rating">
                                                    <?= str_repeat('⭐', $comment['rating']) ?>
                                                </span>
                                            </div>
                                            <p class="comment-text"><?= $comment['comment'] ?></p>
                                            <small style="color: #999;">
                                                <?= date('d/m/Y à H:i', strtotime($comment['created_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (count($comments) > 3): ?>
                                        <p style="color: #667eea; font-style: italic; margin-top: 1rem;">
                                            Et <?= count($comments) - 3 ?> autre(s) commentaire(s)...
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isLoggedIn()): ?>
                                    <?php
                                    // Vérifier si l'utilisateur a déjà commenté
                                    $stmt_check = $pdo->prepare("SELECT id FROM comments WHERE user_id = ? AND product_id = ?");
                                    $stmt_check->execute([$_SESSION['user_id'], $product['id']]);
                                    $has_commented = $stmt_check->fetch();
                                    ?>

                                    <?php if (!$has_commented): ?>
                                        <div class="comment-form">
                                            <h5 style="margin-bottom: 1rem; color: #333;">Ajouter un commentaire</h5>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="add_comment">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                                                <div class="form-group">
                                                    <label>Note :</label>
                                                    <div class="rating-input" id="rating_<?= $product['id'] ?>">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" id="rating_<?= $product['id'] ?>_<?= $i ?>"
                                                                   name="rating" value="<?= $i ?>" required>
                                                            <label for="rating_<?= $product['id'] ?>_<?= $i ?>">⭐</label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="comment_<?= $product['id'] ?>">Commentaire :</label>
                                                    <textarea id="comment_<?= $product['id'] ?>" name="comment"
                                                              rows="3" required minlength="10" maxlength="500"
                                                              placeholder="Partagez votre expérience avec ce produit (minimum 10 caractères)"></textarea>
                                                    <small style="color: #666;">
                                                        <span id="char_count_<?= $product['id'] ?>">0</span>/500 caractères
                                                    </small>
                                                </div>

                                                <button type="submit" class="btn">Ajouter le commentaire</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                                            <p style="color: #2d5a2d; margin: 0; font-weight: 500;">
                                                ✅ Vous avez déjà commenté ce produit. Merci pour votre avis !
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-top: 1rem; text-align: center;">
                                        <p style="color: #666; margin-bottom: 1rem;">
                                            Vous devez être connecté pour ajouter un commentaire
                                        </p>
                                        <a href="index.php" class="btn" style="margin-right: 1rem;">Se connecter</a>
                                        <a href="register.php" class="btn" style="background: linear-gradient(45deg, #28a745, #20c997);">
                                            S'inscrire
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endforeach;
            } catch(PDOException $e) {
                echo "<div class='alert alert-error'>Erreur lors du chargement des produits.</div>";
            }
            ?>
        </div>
    </div>
</div>

<?= getCommonJS() ?>

<script>
    // Animation des étoiles pour la notation
    document.querySelectorAll('.rating-input').forEach(ratingGroup => {
        const inputs = ratingGroup.querySelectorAll('input[type="radio"]');
        const labels = ratingGroup.querySelectorAll('label');

        labels.forEach((label, index) => {
            label.addEventListener('mouseenter', () => {
                labels.forEach((l, i) => {
                    if (i >= index) {
                        l.style.color = '#ffc107';
                    } else {
                        l.style.color = '#ddd';
                    }
                });
            });

            label.addEventListener('click', () => {
                inputs[index].checked = true;
            });
        });

        ratingGroup.addEventListener('mouseleave', () => {
            const checkedInput = ratingGroup.querySelector('input[type="radio"]:checked');
            if (checkedInput) {
                const checkedIndex = Array.from(inputs).indexOf(checkedInput);
                labels.forEach((l, i) => {
                    if (i >= checkedIndex) {
                        l.style.color = '#ffc107';
                    } else {
                        l.style.color = '#ddd';
                    }
                });
            } else {
                labels.forEach(l => l.style.color = '#ddd');
            }
        });
    });

    // Compteur de caractères pour les commentaires
    document.querySelectorAll('textarea[name="comment"]').forEach(textarea => {
        const productId = textarea.id.split('_')[1];
        const counter = document.getElementById('char_count_' + productId);

        textarea.addEventListener('input', function() {
            const count = this.value.length;
            counter.textContent = count;

            if (count < 10) {
                counter.style.color = '#dc3545';
            } else if (count > 450) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#28a745';
            }
        });
    });

    // Effet de parallaxe léger sur les cartes produits
    document.addEventListener('mousemove', (e) => {
        const cards = document.querySelectorAll('.product-card');
        const mouseX = e.clientX;
        const mouseY = e.clientY;

        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            const cardX = rect.left + rect.width / 2;
            const cardY = rect.top + rect.height / 2;

            const deltaX = (mouseX - cardX) * 0.005;
            const deltaY = (mouseY - cardY) * 0.005;

            card.style.transform = `perspective(1000px) rotateY(${deltaX}deg) rotateX(${-deltaY}deg) translateZ(0)`;
        });
    });

    // Animation d'apparition des éléments au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observer toutes les cartes produits
    document.querySelectorAll('.product-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Validation des formulaires de commentaires
    document.querySelectorAll('form').forEach(form => {
        if (form.querySelector('input[name="action"][value="add_comment"]')) {
            form.addEventListener('submit', (e) => {
                const rating = form.querySelector('input[name="rating"]:checked');
                const comment = form.querySelector('textarea[name="comment"]').value.trim();

                if (!rating) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une note.');
                    return;
                }

                if (comment.length < 10) {
                    e.preventDefault();
                    alert('Le commentaire doit contenir au moins 10 caractères.');

                }
            });
        }
    });

    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Auto-masquer les alertes après 5 secondes
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        });
    }, 5000);
</script>
</body>
</html>