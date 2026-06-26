# CYNA Front

Site vitrine et espace client en **PHP natif** + back-office admin.  
Connexion **MySQL directe** (PDO). Projet distinct de l’API Laravel **cyna-api**.

| Environnement | URL exemple |
|---------------|-------------|
| Local | `http://localhost/mon-projet/public/` |
| Production | `https://votre-domaine.com/public/` |

Politique RGPD : voir le dépôt `cyna-api` → `docs/politique-rgpd-cyna.pdf`

---

## Installation rapide

### 1. Prérequis

- PHP 8.1+ (`pdo_mysql`, `curl`, `openssl`, `mbstring`)
- MySQL / MariaDB
- Apache (WAMP, XAMPP, Laragon…)
- Composer 2.x
- Compte Stripe (mode test en dev)

### 2. Mise en place

```bash
# Cloner dans le dossier web du serveur local
cd /chemin/vers/www
git clone <url-du-repo> mon-projet
cd mon-projet
composer install
```

Exemples de dossiers web selon l’outil :

| Outil | Dossier type |
|-------|--------------|
| WAMP | `C:\wamp64\www\mon-projet` |
| XAMPP | `C:\xampp\htdocs\mon-projet` |
| Laragon | `C:\laragon\www\mon-projet` |
| Linux | `/var/www/html/mon-projet` |

### 3. Base de données

```sql
CREATE DATABASE cyna CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importer le dump SQL fourni (`cyna.sql` ou équivalent).

### 4. Configuration

**`config/config.php`** — adapter host, utilisateur et mot de passe MySQL :

```php
$host     = '127.0.0.1';
$dbname   = 'cyna';
$username = 'root';
$password = 'votre_mot_de_passe';
```

**`config/stripe_config.php`** — clés Stripe + URL publique du site :

```php
define('STRIPE_SECRET_KEY',      'sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
define('STRIPE_WEBHOOK_SECRET',  'whsec_...');
define('SITE_URL', 'http://localhost/mon-projet/public');
```

> Ne jamais committer les mots de passe ni les clés secrètes.

### 5. Démarrer et tester

1. Lancer Apache + MySQL  
2. Ouvrir `http://localhost/mon-projet/public/catalogue.php`  
3. Admin : `http://localhost/mon-projet/admin/login.php`

**Virtual host (optionnel)** — pointer `DocumentRoot` vers le dossier `public/` :

```apache
DocumentRoot "/chemin/vers/www/mon-projet/public"
```

---

## Structure

```
mon-projet/
├── admin/          # Back-office
├── config/         # DB + Stripe
├── includes/       # Sessions, CSRF, langues
├── public/         # Pages web (point d'entrée)
├── vendor/
└── index.php
```

---

## URLs utiles

| Page | Chemin |
|------|--------|
| Catalogue | `/public/catalogue.php` |
| Connexion client | `/public/connexion.php` |
| Panier | `/public/panier.php` |
| Checkout | `/public/checkout.php` (client connecté) |
| Admin | `/admin/login.php` |
| Webhook Stripe | `/public/stripe_webhook.php` |

---

## 6. Authentification & protection

| Accès | Condition |
|-------|-----------|
| **Public** | Aucune connexion |
| **Client** | Session `utilisateur_id` + compte confirmé (`est_confirme = 1`) |
| **Admin** | Login + code OTP e-mail + `is_admin = 1` |
| **Système** | Webhook Stripe (signature requise) |
| **Action** | Endpoint POST — pas une page de navigation directe |

---

## 7. Catalogue des routes / pages

Chemins relatifs à la racine du projet. En local : `http://localhost/mon-projet/…`

### 10.1 Vitrine (`public/`)

| Route (fichier) | Accès | Description | Données personnelles |
|-----------------|-------|-------------|----------------------|
| `index.php` | **Public** | Redirection vers le catalogue | Aucune |
| `public/catalogue.php` | **Public** | Liste des produits | Session technique |
| `public/produit.php?id=` | **Public** | Fiche produit | Session technique |
| `public/recherche.php` | **Public** | Recherche produits | Session technique |
| `public/a-propos.php` | **Public** | Page À propos | Aucune |
| `public/Contact.php` | **Public** | Contact + chatbot | E-mail, message, `chat_logs` |
| `public/Cgu.php` | **Public** | Conditions générales | Aucune |
| `public/mention_legales.php` | **Public** | Mentions légales | Aucune |
| `public/inscription.php` | **Public** | Création de compte | Prénom, nom, e-mail, MDP hashé |
| `public/connexion.php` | **Public** | Connexion client | E-mail (MDP en transit) |
| `public/deconnexion.php` | **Client** | Déconnexion | Révocation session |
| `public/confirmer-email.php?token=` | **Public** | Activation compte | Token de vérification |
| `public/renvoyer_confirmation.php` | **Public** | Renvoi e-mail confirmation | E-mail |
| `public/mot_de_passe_oublie.php` | **Public** | Demande reset MDP | E-mail |
| `public/reinitialiser_mot_de_passe.php?token=` | **Public** | Nouveau MDP | Token + nouveau hash |
| `public/panier.php` | **Public** | Panier (session) | Contenu panier en session |
| `public/panier_add.php` | **Public** | **Action POST** — ajout panier | Session panier |
| `public/check_promo.php` | **Public** | **Action AJAX** — code promo | Aucune |
| `public/checkout.php` | **Client** | Formulaire commande | Nom, adresse facturation |
| `public/checkout_submit.php` | **Client** | **Action POST** — commande Stripe | Facturation, 4 derniers chiffres carte |
| `public/stripe_return.php` | **Client** | Retour 3D Secure | ID commande, session |
| `public/confirmation.php?order_id=` | **Client** | Succès commande | ID commande |
| `public/paiement_refuse.php` | **Public** | Échec paiement | Aucune PII |
| `public/stripe_webhook.php` | **Système** | Webhook Stripe | Métadonnées paiement |
| `public/mon-compte.php` | **Client** | Profil compte | Prénom, nom, e-mail, MDP |
| `public/mes-commandes.php` | **Client** | Historique commandes | Commandes du compte |
| `public/mes-abonnements.php` | **Client** | Abonnements actifs | Abonnements Stripe |
| `public/adresses.php` | **Client** | Adresses livraison | Adresses postales |
| `public/paiements.php` | **Client** | Cartes enregistrées | 4 derniers chiffres, ref. Stripe |

### 10.2 Back-office (`admin/`)

| Route (fichier) | Accès | Description | Données personnelles |
|-----------------|-------|-------------|----------------------|
| `admin/login.php` | **Public** | Connexion admin + OTP | Identifiants + code OTP |
| `admin/logout.php` | **Admin** | Déconnexion admin | Révocation session |
| `admin/index.php` | **Admin** | Tableau de bord | Stats agrégées |
| `admin/categories.php` | **Admin** | Liste catégories | Aucune |
| `admin/category_save.php` | **Admin** | **Action** — catégorie | Aucune |
| `admin/category_delete.php` | **Admin** | **Action** — suppression | Aucune |
| `admin/products.php` | **Admin** | Liste produits | Aucune |
| `admin/product_edit.php` | **Admin** | Édition produit | Aucune |
| `admin/product_save.php` | **Admin** | **Action** — produit | Aucune |
| `admin/product_delete.php` | **Admin** | **Action** — suppression | Aucune |
| `admin/orders.php` | **Admin** | Liste commandes | Données clients |
| `admin/order_view.php?id=` | **Admin** | Détail commande | Facturation client |
| `admin/users.php` | **Admin** | Gestion utilisateurs | Profils clients |
| `admin/promo_codes.php` | **Admin** | Codes promo | Aucune |
| `admin/slides.php` | **Admin** | Carrousel accueil | Aucune |
| `admin/slide_save.php` | **Admin** | **Action** — slide | Aucune |
| `admin/slide_delete.php` | **Admin** | **Action** — suppression | Aucune |
| `admin/home_text.php` | **Admin** | Textes homepage | Aucune |
| `admin/chat_logs.php` | **Admin** | Logs chatbot | Messages utilisateurs |

---

## Stripe

Configurer dans le [dashboard Stripe](https://dashboard.stripe.com/webhooks) :

- URL : `{SITE_URL}/stripe_webhook.php`
- Événements : `payment_intent.succeeded`, `payment_intent.payment_failed`

---

## Dépannage

| Problème | Solution |
|----------|----------|
| Erreur PDO | Vérifier `config/config.php` et que la base `cyna` existe |
| Compte non confirmé | Cliquer le lien e-mail ou `est_confirme = 1` en dev |
| Checkout → connexion | Normal si non connecté |
| Commande bloquée en `pending` | Vérifier webhook + `STRIPE_WEBHOOK_SECRET` |
| Admin ne se connecte pas | Vérifier SMTP (OTP) et sessions PHP |

---
