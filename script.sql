-- Script SQL pour créer la base de données d'évaluation de produits

-- Création de la base de données
-- CREATE DATABASE product_evaluation;

-- Utiliser la base de données
-- \c product_evaluation;

-- Table des utilisateurs
CREATE TABLE users (
                       id SERIAL PRIMARY KEY,
                       username VARCHAR(50) UNIQUE NOT NULL,
                       email VARCHAR(100) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des produits
CREATE TABLE products (
                          id SERIAL PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          description TEXT,
                          price DECIMAL(10,2),
                          image_url VARCHAR(255),
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commentaires/évaluations
CREATE TABLE comments (
                          id SERIAL PRIMARY KEY,
                          user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                          product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
                          rating INTEGER CHECK (rating >= 1 AND rating <= 5),
                          comment TEXT,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion de données d'exemple

-- Utilisateurs
INSERT INTO users (username, email, password) VALUES
                                                  ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
                                                  ('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
                                                  ('marie_martin', 'marie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
                                                  ('pierre_dupont', 'pierre@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Produits
INSERT INTO products (name, description, price, image_url) VALUES
                                                               ('iPhone 15 Pro', 'Le dernier smartphone d''Apple avec puce A17 Pro et appareil photo professionnel', 1229.00, 'https://via.placeholder.com/300x200?text=iPhone+15+Pro'),
                                                               ('Samsung Galaxy S24', 'Smartphone Android haut de gamme avec IA intégrée', 899.00, 'https://via.placeholder.com/300x200?text=Samsung+Galaxy+S24'),
                                                               ('MacBook Air M3', 'Ordinateur portable ultra-fin avec puce M3 d''Apple', 1299.00, 'https://via.placeholder.com/300x200?text=MacBook+Air+M3'),
                                                               ('Dell XPS 13', 'Ultrabook Windows avec écran InfinityEdge', 999.00, 'https://via.placeholder.com/300x200?text=Dell+XPS+13'),
                                                               ('AirPods Pro 2', 'Écouteurs sans fil avec réduction de bruit active', 279.00, 'https://via.placeholder.com/300x200?text=AirPods+Pro+2'),
                                                               ('Sony WH-1000XM5', 'Casque audio sans fil avec réduction de bruit', 399.00, 'https://via.placeholder.com/300x200?text=Sony+WH-1000XM5');

-- Commentaires/Évaluations
INSERT INTO comments (user_id, product_id, rating, comment) VALUES
                                                                (2, 1, 5, 'Excellent smartphone ! L''appareil photo est vraiment impressionnant et la performance est au top.'),
                                                                (3, 1, 4, 'Très bon produit mais le prix est un peu élevé. La qualité Apple est au rendez-vous.'),
                                                                (4, 1, 5, 'Je recommande vivement ! Meilleur iPhone à ce jour.'),

                                                                (2, 2, 4, 'Bon smartphone Android, l''IA est intéressante mais pas révolutionnaire.'),
                                                                (3, 2, 5, 'J''adore mon Galaxy S24 ! L''écran est magnifique et la batterie tient bien.'),

                                                                (4, 3, 5, 'MacBook parfait pour le travail. Silencieux et très rapide avec la puce M3.'),
                                                                (2, 3, 4, 'Excellent ordinateur portable, mais j''aurais aimé plus de ports.'),

                                                                (3, 4, 4, 'Bon ultrabook Windows, l''écran est vraiment beau.'),
                                                                (4, 4, 3, 'Correct mais je m''attendais à mieux pour ce prix.'),

                                                                (2, 5, 5, 'Les meilleurs écouteurs que j''ai jamais eus ! Réduction de bruit exceptionnelle.'),
                                                                (4, 5, 4, 'Très bons AirPods, le son est clair et la réduction de bruit fonctionne bien.'),

                                                                (3, 6, 5, 'Casque audio exceptionnel ! Le son est d''une qualité remarquable.'),
                                                                (2, 6, 4, 'Très bon casque Sony, confortable pour de longues sessions d''écoute.');

-- Création d'index pour améliorer les performances
CREATE INDEX idx_comments_product_id ON comments(product_id);
CREATE INDEX idx_comments_user_id ON comments(user_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);