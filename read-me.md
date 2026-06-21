# CYNA — Plateforme E-commerce SaaS Cybersécurité

> Projet fil rouge B3 — SUP DE VINCI  
> Groupe 2 — Plateforme de vente de solutions SaaS (SOC, EDR, XDR)

---

## 📋 Présentation

CYNA est une plateforme e-commerce mobile-first dédiée à la vente de solutions SaaS de cybersécurité pour les PME. Elle permet aux clients de s'abonner à des services de type SOC, EDR et XDR, de gérer leurs abonnements, et aux administrateurs de piloter l'activité via un backoffice complet.

**Entreprise :** CYNA-IT  
**Adresse :** 10 Rue de Penthièvre, 75008 Paris  
**SIRET :** 91371103200015  

---

## 🛠 Stack technique

| Composant | Technologie |
|-----------|------------|
| Backend | PHP 7.4 |
| Base de données | MySQL 5.7+ |
| Serveur local | WAMP (Windows) |
| Paiement | Stripe (tokenisation JS + API REST) |
| Emails | PHPMailer + SMTP Gmail |
| Frontend | Bootstrap 5.3 + CSS custom |
| Graphiques | Chart.js 4.4 |
| Chatbot | API Anthropic Claude |
| Dépendances | Composer (PHPMailer) |

---

## ⚙️ Installation

### Prérequis
- WAMP Server 3.x (PHP 7.4+, MySQL 5.7+)
- Composer
- Compte Stripe (clés de test gratuites)
- Compte Gmail (pour PHPMailer)

### Étapes

**1. Cloner / copier le projet**
```bash
# Placer le dossier dans C:\wamp64\www\
C:\wamp64\www\Cyna\
```

**2. Installer les dépendances**
```bash
cd C:\wamp64\www\Cyna
composer install
```

**3. Créer la base de données**

Ouvrir phpMyAdmin → créer une base `cyna` → importer le fichier SQL :
```
database/cyna.sql
```

**4. Configurer la connexion BDD**

Éditer `config/config.php` :
```php
$host     = 'localhost';
$dbname   = 'cyna';
$username = 'root';
$password = '';
```

**5. Configurer Stripe**

Éditer `config/stripe_config.php` :
```php
define('STRIPE_SECRET_KEY',      'sk_test_VOTRE_CLE');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_VOTRE_CLE');
define('SITE_URL', 'http://localhost/Cyna');
```

Clés disponibles sur : https://dashboard.stripe.com/test/apikeys

**6. Accéder au site**

| URL | Description |
|-----|-------------|
| `http://localhost/Cyna` | Site public |
| `http://localhost/Cyna/admin` | Backoffice admin |

**Compte admin par défaut :**
- Email : `admin@cyna.com`
- Mot de passe : `Admin1234!`

---

## 📁 Structure du projet

```
Cyna/
├── admin/                  # Backoffice administrateur
│   ├── index.php           # Dashboard avec graphiques
│   ├── products.php        # Gestion produits
│   ├── categories.php      # Gestion catégories
│   ├── orders.php          # Gestion commandes
│   ├── users.php           # Gestion utilisateurs
│   ├── slides.php          # Carrousel homepage
│   ├── chat_logs.php       # Logs chatbot
│   ├── promo_codes.php     # Codes promotionnels
│   ├── login.php           # Connexion 2FA
│   ├── header.php          # Layout admin
│   └── footer.php
│
├── config/
│   ├── config.php          # Connexion BDD
│   └── stripe_config.php   # Clés Stripe
│
├── cron/
│   └── cron_renouvellement.php  # Emails renouvellement abonnements
│
├── includes/
│   ├── cart_repository.php      # Fonctions panier
│   ├── catalog_repository.php   # Fonctions catalogue
│   ├── home_repository.php      # Fonctions homepage
│   ├── product_repository.php   # Fonctions produits
│   ├── function.php             # Fonctions utilitaires
│   └── lang.php                 # Système multi-langue FR/EN
│
├── public/                 # Pages publiques
│   ├── index.php           # Accueil
│   ├── catalogue.php       # Catalogue produits
│   ├── produit.php         # Fiche produit
│   ├── recherche.php       # Recherche avec filtres
│   ├── panier.php          # Panier
│   ├── checkout.php        # Tunnel de paiement
│   ├── checkout_submit.php # Traitement paiement Stripe
│   ├── confirmation.php    # Confirmation commande
│   ├── connexion.php       # Connexion
│   ├── inscription.php     # Inscription
│   ├── mon-compte.php      # Espace client
│   ├── mes-abonnements.php # Gestion abonnements
│   ├── mes-commandes.php   # Historique commandes
│   ├── adresses.php        # Carnet d'adresses
│   ├── paiements.php       # Méthodes de paiement
│   ├── Contact.php         # Contact + Chatbot
│   ├── Cgu.php             # CGU
│   ├── mention_legales.php # Mentions légales
│   ├── a-propos.php        # À propos
│   ├── stripe_return.php   # Retour 3D Secure
│   ├── stripe_webhook.php  # Webhooks Stripe
│   └── check_promo.php     # Vérification codes promo
│
└── vendor/                 # Dépendances Composer (PHPMailer)
```

---

## 🗄️ Structure de la base de données

| Table | Description |
|-------|-------------|
| `utilisateurs` | Comptes utilisateurs (clients + admins) |
| `categories` | Catégories de services |
| `products` | Services SaaS (SOC, EDR, XDR) |
| `orders` | Commandes |
| `order_items` | Lignes de commande |
| `subscriptions` | Abonnements actifs |
| `user_addresses` | Carnet d'adresses |
| `user_payment_methods` | Cartes enregistrées |
| `homepage_slides` | Slides du carrousel |
| `homepage_content` | Texte homepage |
| `chat_logs` | Logs conversations chatbot |
| `promo_codes` | Codes promotionnels |

---

## 💳 Cartes de test Stripe

| Carte | Numéro | Résultat |
|-------|--------|----------|
| Acceptée | `4242 4242 4242 4242` | Paiement validé |
| Refusée | `4000 0000 0000 0002` | Paiement refusé |
| 3D Secure | `4000 0025 0000 3155` | Authentification 3DS |

Date : n'importe quelle date future · CVV : n'importe quoi

---

## 🔐 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes préparées PDO (protection injection SQL)
- Tokenisation carte bancaire côté client (Stripe.js)
- 2FA par email pour l'accès admin
- Sessions PHP sécurisées (`session_regenerate_id`)
- Validation et sanitisation des inputs (`htmlspecialchars`, `filter_var`)

---

## 📧 Emails automatiques

| Déclencheur | Email envoyé |
|-------------|-------------|
| Inscription | Confirmation d'email |
| Commande validée | Récapitulatif de commande |
| Résiliation abonnement | Confirmation de résiliation |
| J-7 renouvellement | Rappel de renouvellement |
| Mot de passe oublié | Lien de réinitialisation |
| Connexion admin (2FA) | Code à 6 chiffres |

**Cron renouvellement :**
```bash
# Exécuter tous les jours à 8h
0 8 * * * php C:\wamp64\www\Cyna\cron\cron_renouvellement.php

# Ou via navigateur (clé secrète requise)
http://localhost/Cyna/cron/cron_renouvellement.php?secret=cyna2025
```

---

## 🌐 Multi-langue

Le système de traduction est dans `includes/lang.php`.

```php
// Dans une page PHP
require_once '../includes/lang.php';
echo t('hero_title');      // Affiche selon la langue active
echo lang_switcher();      // Affiche le bouton FR/EN
```

Changer la langue : `?lang=en` ou `?lang=fr` dans l'URL.

---

## 📊 Dashboard Admin

Le tableau de bord (`admin/index.php`) affiche :
- **KPI** : catégories, produits, commandes, CA total
- **Histogramme ventes** : 7 derniers jours ou 5 dernières semaines (switchable)
- **Histogramme paniers moyens** : par catégorie sur 30 jours
- **Camembert** : répartition CA par catégorie
- **Dernières commandes** : 6 plus récentes avec lien détail

---

## 🤖 Chatbot

Le chatbot sur la page Contact utilise l'API Claude (Anthropic).

**Configuration :**
```php
// Dans Contact.php, ligne ~95
'x-api-key: ' . 'VOTRE_CLE_ANTHROPIC'
```

En l'absence de clé, un système de fallback local répond aux questions fréquentes par mots-clés.

---

## 👥 Équipe

Projet réalisé dans le cadre du B3 Dev — SUP DE VINCI  
Année 2024-2025