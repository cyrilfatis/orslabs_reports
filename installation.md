# 🚀 Guide d'installation - ORS Labs SEO Reports (Symfony)

## 📋 Prérequis

- WAMP installé (Windows + Apache + MySQL + PHP 8.4)
- Composer installé
- PHP >= 8.2
- MySQL >= 8.0

## 🔧 Installation étape par étape

### 1️⃣ Créer le projet Symfony

Ouvrez un terminal (CMD ou PowerShell) et naviguez vers votre dossier WAMP :

```bash
cd C:\wamp64\www

# Créer le projet
composer create-project symfony/skeleton:"7.2.*" orslabs-reports

cd orslabs-reports

# Installer les dépendances nécessaires
composer require webapp
composer require symfony/security-bundle
composer require symfony/maker-bundle --dev
composer require symfony/form
composer require symfony/validator
composer require symfony/asset
composer require doctrine/doctrine-bundle
composer require doctrine/orm
```

### 2️⃣ Créer la base de données

1. Ouvrez phpMyAdmin : http://localhost/phpmyadmin
2. Créez une nouvelle base de données :
    - Nom : `orslabs_reports`
    - Interclassement : `utf8mb4_unicode_ci`

### 3️⃣ Configurer l'environnement

Créez un fichier `.env.local` à la racine du projet avec ce contenu :

```env
###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://root:@127.0.0.1:3306/orslabs_reports?serverVersion=8.0.32&charset=utf8mb4"
###< doctrine/doctrine-bundle ###

###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=changez_cette_cle_par_une_valeur_aleatoire_de_32_caracteres_minimum
###< symfony/framework-bundle ###
```

**Note :** Si vous avez un mot de passe MySQL dans WAMP, modifiez la ligne DATABASE_URL :
```
DATABASE_URL="mysql://root:votremotdepasse@127.0.0.1:3306/orslabs_reports?serverVersion=8.0.32&charset=utf8mb4"
```

### 4️⃣ Copier les fichiers fournis

Copiez les fichiers que je vous ai fournis dans votre projet :

**Entités et Repositories :**
```
src/Entity/User.php                          ← User.php
src/Repository/UserRepository.php            ← UserRepository.php
```

**Controllers :**
```
src/Controller/SecurityController.php        ← SecurityController.php
src/Controller/DashboardController.php       ← DashboardController.php
```

**Configuration :**
```
config/packages/security.yaml                ← security.yaml
```

**Templates :**
```
templates/base.html.twig                     ← base.html.twig
templates/security/login.html.twig           ← login.html.twig
templates/dashboard/index.html.twig          ← dashboard.html.twig
```

**Commandes :**
```
src/Command/CreateAdminCommand.php           ← CreateAdminCommand.php
```

### 5️⃣ Créer les tables en base de données

```bash
# Générer la migration
php bin/console make:migration

# Exécuter la migration
php bin/console doctrine:migrations:migrate
```

Confirmez avec `yes` quand demandé.

### 6️⃣ Créer votre premier utilisateur

```bash
php bin/console app:create-admin
```

Suivez les instructions. Valeurs par défaut suggérées :
- Email : `admin@orslabs.fr`
- Prénom : `Admin`
- Nom : `ORS Labs`
- Mot de passe : `admin123` (vous pourrez le changer plus tard)

### 7️⃣ Configurer Apache (Facultatif mais recommandé)

Pour avoir une URL propre comme `http://orslabs-reports.local`, créez un VirtualHost :

1. Ouvrez : `C:\wamp64\bin\apache\apache2.x.x\conf\extra\httpd-vhosts.conf`

2. Ajoutez à la fin :
```apache
<VirtualHost *:80>
    ServerName orslabs-reports.local
    DocumentRoot "C:/wamp64/www/orslabs-reports/public"
    
    <Directory "C:/wamp64/www/orslabs-reports/public">
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

3. Modifiez le fichier hosts Windows :
    - Ouvrez `C:\Windows\System32\drivers\etc\hosts` en tant qu'administrateur
    - Ajoutez : `127.0.0.1    orslabs-reports.local`

4. Redémarrez WAMP

### 8️⃣ Tester l'installation

**Option A - Avec VirtualHost :**
Ouvrez : http://orslabs-reports.local

**Option B - Sans VirtualHost :**
Ouvrez : http://localhost/orslabs-reports/public/

Vous devriez voir la page de connexion ! 🎉

## 🔐 Connexion

Utilisez les identifiants que vous avez créés à l'étape 6 :
- Email : `admin@orslabs.fr`
- Mot de passe : `admin123`

## 📁 Structure du projet

```
orslabs-reports/
├── config/
│   └── packages/
│       └── security.yaml           # Configuration sécurité
├── public/
│   └── index.php                   # Point d'entrée
├── src/
│   ├── Command/
│   │   └── CreateAdminCommand.php  # Commande création utilisateur
│   ├── Controller/
│   │   ├── DashboardController.php # Page d'accueil
│   │   └── SecurityController.php  # Login/Logout
│   ├── Entity/
│   │   └── User.php                # Entité utilisateur
│   └── Repository/
│       └── UserRepository.php      # Requêtes utilisateur
├── templates/
│   ├── base.html.twig              # Template de base
│   ├── dashboard/
│   │   └── index.html.twig         # Dashboard
│   └── security/
│       └── login.html.twig         # Page de connexion
├── var/                            # Cache, logs
├── vendor/                         # Dépendances
├── .env                            # Config par défaut
├── .env.local                      # Config locale (à créer)
└── composer.json                   # Dépendances PHP
```

## ✅ Vérifications

Si vous rencontrez des problèmes :

1. **Vérifier les permissions** :
```bash
# Dans le dossier du projet
php bin/console cache:clear
```

2. **Vérifier la connexion à la base** :
```bash
php bin/console doctrine:database:create  # Si la BDD n'existe pas
php bin/console doctrine:schema:validate
```

3. **Vérifier les routes** :
```bash
php bin/console debug:router
```

Vous devriez voir :
- `app_login` (GET/POST /login)
- `app_logout` (GET /logout)
- `app_dashboard` (GET /)

## 🎯 Prochaines étapes

Maintenant que votre système d'authentification fonctionne, nous pouvons :
1. ✅ Intégrer les rapports HTML existants
2. ✅ Créer une gestion des rapports mensuels
3. ✅ Ajouter la navigation entre les rapports
4. ✅ Stocker les données en base
5. ✅ Créer un système de comparaison
6. ✅ Ajouter des graphiques interactifs

## 🆘 Problèmes courants

### "Access denied for user 'root'@'localhost'"
→ Vérifiez le mot de passe MySQL dans `.env.local`

### "Table 'user' doesn't exist"
→ Exécutez : `php bin/console doctrine:migrations:migrate`

### Page blanche
→ Vérifiez les logs : `var/log/dev.log`

### "An error occurred while loading the web debug toolbar"
→ C'est normal en développement, rechargez la page

## 📞 Support

Si vous avez des questions, n'hésitez pas à me demander !