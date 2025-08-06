<?php
require_once 'config.php';

$message = '';
$message_type = '';
$products = [];
$search_performed = false;
$total_results = 0;

// Paramètres de recherche
$search_query = trim($_GET['search'] ?? '');
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$min_rating = $_GET['min_rating'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Traitement de la recherche
if (!empty($search_query) || !empty($min_price) || !empty($max_price) || !empty($min_rating)) {
    $search_performed = true;

    try {
        // Construction de la requête SQL
        $sql = "
            SELECT p.*, 
                   COUNT(c.id) as comment_count,
                   COALESCE(AVG(c.rating), 0) as avg_rating
            FROM products p 
            LEFT JOIN comments c ON p.id = c.product_id 
            WHERE 1=1
        ";

        // Recherche sécurisée
        $params = [];
        // Recherche textuelle
        if (!empty($search_query)) {
            $sql .= " AND p.name = LOWER(?)";
            $params[] = $search_query;
            /*$sql .= " AND (LOWER(p.name) LIKE LOWER(?) OR LOWER(p.description) LIKE LOWER(?))";
            $search_param = "%$search_query%";
            $params[] = $search_param;
            $params[] = $search_param;*/
        }

        // Filtre par prix minimum
        if (!empty($min_price) && is_numeric($min_price)) {
            $sql .= " AND p.price >= ?";
            $params[] = (float)$min_price;
        }

        // Filtre par prix maximum
        if (!empty($max_price) && is_numeric($max_price)) {
            $sql .= " AND p.price <= ?";
            $params[] = (float)$max_price;
        }

        $sql .= " GROUP BY p.id, p.name, p.description, p.price, p.image_url, p.created_at";

        // Filtre par note moyenne
        if (!empty($min_rating) && is_numeric($min_rating)) {
            $sql .= " HAVING COALESCE(AVG(c.rating), 0) >= ?";
            $params[] = (float)$min_rating;
        }
        // Tri
        $allowed_sorts = ['name', 'price', 'avg_rating', 'comment_count', 'created_at'];
        $allowed_orders = ['ASC', 'DESC'];

        if (in_array($sort_by, $allowed_sorts) && in_array($sort_order, $allowed_orders)) {
            if ($sort_by === 'avg_rating' || $sort_by === 'comment_count') {
                $sql .= " ORDER BY $sort_by $sort_order";
            } else {
                $sql .= " ORDER BY p.$sort_by $sort_order";
            }
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results = count($products);

        /*// Recherche non sécurisée
        // Recherche textuelle
        if (!empty($search_query)) {
            $search_param = "%$search_query%";
         //   $sql .= " AND (LOWER(p.name) LIKE LOWER('$search_param') OR LOWER(p.description) LIKE LOWER('$search_param'))";
            $sql .= " AND p.name = '$search_query'";
        }

        // Filtre par prix minimum
        if (!empty($min_price) && is_numeric($min_price)) {
            $min_price = floatval($min_price);
            $sql .= " AND p.price >= $min_price";
        }

        // Filtre par prix maximum
        if (!empty($max_price) && is_numeric($max_price)) {
            $max_price = floatval($max_price);
            $sql .= " AND p.price <= $max_price";
        }

        $sql .= " GROUP BY p.id, p.name, p.description, p.price, p.image_url, p.created_at";

        // Filtre par note moyenne
        if (!empty($min_rating) && is_numeric($min_rating)) {
            $min_rating = floatval($min_rating);
            $sql .= " HAVING COALESCE(AVG(c.rating), 0) >= $min_rating";
        }
        // Tri
        $allowed_sorts = ['name', 'price', 'avg_rating', 'comment_count', 'created_at'];
        $allowed_orders = ['ASC', 'DESC'];

        if (in_array($sort_by, $allowed_sorts) && in_array($sort_order, $allowed_orders)) {
            if ($sort_by === 'avg_rating' || $sort_by === 'comment_count') {
                $sql .= " ORDER BY $sort_by $sort_order";
            } else {
                $sql .= " ORDER BY p.$sort_by $sort_order";
            }
        }

        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results = count($products);
        */
        if ($total_results === 0) {
            $message = "Aucun produit trouvé pour votre recherche.";
            $message_type = 'error';
        } else {
            $message = "$total_results produit(s) trouvé(s) pour votre recherche.";
            $message_type = 'success';
        }

    } catch(PDOException $e) {
        $message = "Erreur lors de la recherche.";
        $message_type = 'error';
    }
}

// Récupérer quelques statistiques pour les filtres
try {
    $stats_stmt = $pdo->query("
        SELECT 
            MIN(price) as min_price,
            MAX(price) as max_price,
            COUNT(*) as total_products
        FROM products
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $stats = ['min_price' => 0, 'max_price' => 2000, 'total_products' => 0];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de Produits - Évaluation de Produits</title>
    <?= getCommonCSS() ?>
    <style>
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .search-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .price-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0.5rem;
            align-items: center;
        }

        .search-input {
            position: relative;
        }

        .search-input input {
            padding-left: 3rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.2rem;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .filter-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .sort-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .sort-controls select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
        }

        .product-grid-search {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card-compact {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .product-card-compact:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .product-image-compact {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .product-info-compact {
            padding: 1.2rem;
        }

        .product-name-compact {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .product-price-compact {
            font-size: 1.3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.8rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #ffc107;
        }

        .product-description-compact {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .quick-filters {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .quick-filter {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .quick-filter:hover {
            background: white;
            color: #667eea;
            border-color: #667eea;
        }

        .clear-filters {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .clear-filters:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .search-row {
                grid-template-columns: 1fr;
            }

            .results-header {
                flex-direction: column;
                align-items: stretch;
            }

            .sort-controls {
                justify-content: space-between;
            }

            .product-grid-search {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <?= getNavigation() ?>

    <div class="search-container">
        <h2 style="text-align: center; margin-bottom: 2rem; color: #333;">
            🔍 Recherche de Produits
        </h2>

        <!-- Formulaire de recherche -->
        <form method="GET" class="search-form">
            <div class="search-row">
                <div class="form-group">
                    <label for="search">Rechercher :</label>
                    <div class="search-input">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="search" name="search"
                               value="<?= htmlspecialchars($search_query) ?>"
                               placeholder="Nom du produit, description...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Gamme de prix (€) :</label>
                    <div class="price-range">
                        <input type="number" name="min_price"
                               value="<?= htmlspecialchars($min_price) ?>"
                               placeholder="Prix min" min="0" step="0.01">
                        <span>à</span>
                        <input type="number" name="max_price"
                               value="<?= htmlspecialchars($max_price) ?>"
                               placeholder="Prix max" min="0" step="0.01">
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-title">
                    ⚙️ Filtres avancés
                </div>

                <div class="search-row">
                    <div class="form-group">
                        <label for="min_rating">Note minimum :</label>
                        <select id="min_rating" name="min_rating">
                            <option value="">Toutes les notes</option>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $min_rating == $i ? 'selected' : '' ?>>
                                    <?= str_repeat('⭐', $i) ?> et plus
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_by">Trier par :</label>
                        <select id="sort_by" name="sort_by">
                            <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Nom</option>
                            <option value="price" <?= $sort_by === 'price' ? 'selected' : '' ?>>Prix</option>
                            <option value="avg_rating" <?= $sort_by === 'avg_rating' ? 'selected' : '' ?>>Note</option>
                            <option value="comment_count" <?= $sort_by === 'comment_count' ? 'selected' : '' ?>>Nombre d'avis</option>
                            <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Ordre :</label>
                        <select id="sort_order" name="sort_order">
                            <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                            <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">🔍 Rechercher</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Filtres rapides -->
        <div class="quick-filters">
            <a href="?min_price=0&max_price=500" class="quick-filter">
                💰 Moins de 500€
            </a>
            <a href="?min_price=500&max_price=1000" class="quick-filter">
                💸 500€ - 1000€
            </a>
            <a href="?min_price=1000" class="quick-filter">
                💎 Plus de 1000€
            </a>
            <a href="?min_rating=4" class="quick-filter">
                ⭐ Très bien notés
            </a>
            <a href="?sort_by=created_at&sort_order=DESC" class="quick-filter">
                🆕 Nouveautés
            </a>
            <a href="search.php" class="clear-filters">
                🗑️ Effacer les filtres
            </a>
        </div>

        <div style="text-align: center; color: #666; font-size: 0.9rem;">
            Base de données : <?= $stats['total_products'] ?> produits
            (Prix : <?= number_format($stats['min_price'], 2) ?>€ - <?= number_format($stats['max_price'], 2) ?>€)
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <?= showAlert($message, $message_type) ?>
    <?php endif; ?>

    <?php if ($search_performed): ?>
        <div class="products-container">
            <?php if (!empty($products)): ?>
                <div class="results-header">
                    <h3 style="color: #333; margin: 0;">
                        Résultats de recherche (<?= $total_results ?>)
                    </h3>
                    <div class="sort-controls">
                        <span style="color: #666;">Trier par :</span>
                        <select onchange="updateSort(this)" id="quick-sort">
                            <option value="name-ASC" <?= ($sort_by === 'name' && $sort_order === 'ASC') ? 'selected' : '' ?>>
                                Nom A-Z
                            </option>
                            <option value="price-ASC" <?= ($sort_by === 'price' && $sort_order === 'ASC') ? 'selected' : '' ?>>
                                Prix croissant
                            </option>
                            <option value="price-DESC" <?= ($sort_by === 'price' && $sort_order === 'DESC') ? 'selected' : '' ?>>
                                Prix décroissant
                            </option>
                            <option value="avg_rating-DESC" <?= ($sort_by === 'avg_rating' && $sort_order === 'DESC') ? 'selected' : '' ?>>
                                Mieux notés
                            </option>
                        </select>
                    </div>
                </div>

                <div class="product-grid-search">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card-compact">
                            <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="product-image-compact"
                            >

                            <div class="product-info-compact">
                                <h4 class="product-name-compact">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h4>

                                <div class="product-price-compact">
                                    <?= number_format($product['price'], 2) ?>€
                                </div>

                                <div class="product-meta">
                                    <div class="rating-display">
                                        <?php if ($product['avg_rating'] > 0): ?>
                                            <span><?= str_repeat('⭐', round($product['avg_rating'])) ?></span>
                                            <span>(<?= round($product['avg_rating'], 1) ?>/5)</span>
                                        <?php else: ?>
                                            <span style="color: #999;">Pas encore noté</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #666;">
                                        <?= $product['comment_count'] ?> avis
                                    </div>
                                </div>

                                <p class="product-description-compact">
                                    <?= htmlspecialchars($product['description']) ?>
                                </p>

                                <div style="margin-top: 1rem;">
                                    <a href="products.php#product_<?= $product['id'] ?>" class="btn"
                                       style="width: 100%; text-align: center; padding: 10px;">
                                        Voir le produit
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche ou utilisez les filtres rapides.</p>
                    <div style="margin-top: 2rem;">
                        <a href="search.php" class="btn">Nouvelle recherche</a>
                        <a href="products.php" class="btn" style="margin-left: 1rem; background: linear-gradient(45deg, #28a745, #20c997);">
                            Voir tous les produits
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="products-container">
            <div style="text-align: center; padding: 3rem;">
                <h3 style="color: #333; margin-bottom: 1rem;">
                    Découvrez nos produits
                </h3>
                <p style="color: #666; margin-bottom: 2rem;">
                    Utilisez le formulaire ci-dessus pour rechercher des produits par nom, prix ou note.
                </p>
                <a href="products.php" class="btn">
                    Voir tous les produits
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= getCommonJS() ?>

<script>
    // Fonction pour mettre à jour le tri rapide
    function updateSort(select) {
        const value = select.value.split('-');
        const sortBy = value[0];
        const sortOrder = value[1];

        const url = new URL(window.location.href);
        url.searchParams.set('sort_by', sortBy);
        url.searchParams.set('sort_order', sortOrder);

        window.location.href = url.toString();
    }

    // Recherche en temps réel (avec délai)
    let searchTimeout;
    document.getElementById('search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Auto-submit après 1 seconde d'inactivité
            if (this.value.length >= 3 || this.value.length === 0) {
                document.querySelector('.search-form').submit();
            }
        }, 1000);
    });

    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.product-card-compact');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Validation des prix
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });

    // Validation de cohérence des prix
    document.querySelector('input[name="min_price"]').addEventListener('change', function() {
        const maxPriceInput = document.querySelector('input[name="max_price"]');
        if (maxPriceInput.value && parseFloat(this.value) > parseFloat(maxPriceInput.value)) {
            maxPriceInput.value = this.value;
        }
    });

    document.querySelector('input[name="max_price"]').addEventListener('change', function() {
        const minPriceInput = document.querySelector('input[name="min_price"]');
        if (minPriceInput.value && parseFloat(this.value) < parseFloat(minPriceInput.value)) {
            minPriceInput.value = this.value;
        }
    });

    // Surligner les termes recherchés
    function highlightSearchTerms() {
        const searchTerm = '<?= addslashes($search_query) ?>';
        if (searchTerm) {
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            document.querySelectorAll('.product-name-compact, .product-description-compact').forEach(element => {
                element.innerHTML = element.innerHTML.replace(regex, '<mark style="background: #fff3cd; padding: 2px;">$1</mark>');
            });
        }
    }

    // Appliquer la surbrillance si une recherche a été effectuée
    <?php if (!empty($search_query)): ?>
    highlightSearchTerms();
    <?php endif; ?>

    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K pour focus sur la recherche
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('search').focus();
        }

        // Escape pour effacer la recherche
        if (e.key === 'Escape' && document.activeElement === document.getElementById('search')) {
            document.getElementById('search').value = '';
        }
    });

    // Afficher un indicateur sur l'input de recherche actif
    document.getElementById('search').addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
        this.parentElement.style.transition = 'transform 0.2s ease';
    });

    document.getElementById('search').addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
</script>
</body>
</html>