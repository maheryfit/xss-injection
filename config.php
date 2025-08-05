<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'product_evaluation';
$username = 'postgres';
$password = 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour afficher les alertes
function showAlert($message, $type = 'success') {
    return "<div class='alert alert-$type'>$message</div>";
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour rediriger
function redirect($url) {
    header("Location: $url");
    exit;
}

// CSS commun
function getCommonCSS() {
    return '
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a, .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .nav-links a:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-container, .products-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .text-center {
            text-align: center;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .product-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .comments-section {
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .comment {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .comment-author {
            font-weight: 600;
            color: #333;
        }

        .comment-rating {
            color: #ffc107;
        }

        .comment-text {
            color: #555;
            line-height: 1.5;
        }

        .comment-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .rating-input {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .rating-input input[type="radio"]:checked ~ label,
        .rating-input label:hover {
            color: #ffc107;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>';
}

// Navigation commune
function getNavigation() {
    $nav = '<div class="header">
        <h1>🛍️ Évaluation de Produits</h1>
        <div class="nav-links">
            <a href="products.php">Produits</a>';

    if (isLoggedIn()) {
        $nav .= '<span style="color: #333; padding: 12px;">Bonjour, ' . htmlspecialchars($_SESSION['username']) . '</span>
            <a href="index.php?action=logout">Déconnexion</a>';
    } else {
        $nav .= '';
    }

    $nav .= '</div></div>';
    return $nav;
}

// JavaScript commun
function getCommonJS() {
    return '
    <script>
        // Validation du formulaire côté client
        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", (e) => {
                const requiredFields = form.querySelectorAll("[required]");
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = "#dc3545";
                        isValid = false;
                    } else {
                        field.style.borderColor = "#e1e5e9";
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert("Veuillez remplir tous les champs requis.");
                }
            });
        });

        // Animation de chargement
        window.addEventListener("load", () => {
            document.body.style.opacity = "1";
            document.body.style.transform = "translateY(0)";
        });

        // Style initial pour l\'animation de chargement
        document.body.style.opacity = "0";
        document.body.style.transform = "translateY(20px)";
        document.body.style.transition = "opacity 0.5s ease, transform 0.5s ease";
    </script>';
}
?>