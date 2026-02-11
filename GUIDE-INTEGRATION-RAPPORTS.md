# 📊 Guide d'intégration des rapports HTML

## 🎯 Fonctionnalité

Système automatique de détection et d'affichage des rapports SEO mensuels à partir de fichiers HTML.

## ✨ Ce qui a été ajouté

### Nouvelles fonctionnalités

1. **Détection automatique** des fichiers HTML dans `public/reports/`
2. **Convention de nommage** simple : `2025-11.html` ou `rapport-2025-11.html`
3. **Extraction automatique** des métadonnées (stats clés) depuis le HTML
4. **Visualisation** en iframe directement dans l'application
5. **Synchronisation** en une commande
6. **Navigation** intuitive entre les rapports

### Nouveaux fichiers créés

**Entités :**
- `Report.php` → `src/Entity/Report.php`

**Repositories :**
- `ReportRepository.php` → `src/Repository/ReportRepository.php`

**Services :**
- `ReportManager.php` → `src/Service/ReportManager.php`

**Contrôleurs :**
- `ReportController.php` → `src/Controller/ReportController.php`
- `DashboardController-v2.php` → `src/Controller/DashboardController.php` (remplacer l'ancien)

**Commandes :**
- `SyncReportsCommand.php` → `src/Command/SyncReportsCommand.php`

**Templates :**
- `report-list.html.twig` → `templates/report/list.html.twig`
- `report-view.html.twig` → `templates/report/view.html.twig`
- `dashboard-v2.html.twig` → `templates/dashboard/index.html.twig` (remplacer l'ancien)
- `base.html.twig` (mis à jour avec le lien "Rapports" dans le menu)

## 📦 Installation

### 1️⃣ Copier les nouveaux fichiers

```bash
# Copier les fichiers dans votre projet
# Suivez la structure indiquée ci-dessus
```

### 2️⃣ Installer les dépendances supplémentaires

```bash
# DomCrawler pour parser le HTML
composer require symfony/dom-crawler

# CSS Selector pour faciliter la navigation dans le DOM
composer require symfony/css-selector
```

### 3️⃣ Créer la migration de base de données

```bash
php bin/console make:migration
```

Vérifiez que la migration contient la table `report` avec toutes les colonnes.

### 4️⃣ Exécuter la migration

```bash
php bin/console doctrine:migrations:migrate
```

### 5️⃣ Créer le dossier pour les rapports

```bash
# Windows
mkdir public\reports

# Linux/Mac
mkdir -p public/reports
```

### 6️⃣ Ajouter vos rapports HTML

Copiez vos fichiers HTML dans `public/reports/` et renommez-les selon la convention :

**Exemples valides :**
- `2025-11.html` ✅
- `2025-12.html` ✅
- `rapport-2025-01.html` ✅
- `seo-2025-02.html` ✅

**Exemples invalides :**
- `novembre-2025.html` ❌ (pas de chiffres)
- `2025-nov.html` ❌ (mois en lettres)
- `rapport-novembre.html` ❌ (pas de format date)

### 7️⃣ Synchroniser les rapports

```bash
php bin/console app:sync-reports
```

Cette commande va :
- Scanner le dossier `public/reports/`
- Extraire les métadonnées de chaque fichier HTML
- Créer les entrées en base de données
- Afficher un résumé

### 8️⃣ Vider le cache

```bash
php bin/console cache:clear
```

## 🎨 Utilisation

### Pour les utilisateurs

1. **Dashboard** : Voir les 6 derniers rapports
2. **Menu "Rapports"** : Voir tous les rapports disponibles
3. **Cliquer sur un rapport** : Le visualiser en plein écran dans l'interface
4. **Bouton "Ouvrir dans un nouvel onglet"** : Voir le rapport HTML pur

### Pour les administrateurs

**Bouton "Synchroniser"** disponible sur la page des rapports pour détecter automatiquement les nouveaux fichiers.

## 📝 Convention de nommage

### Format requis

```
[préfixe-optionnel-]YYYY-MM.html
```

**Exemples :**
- `2025-11.html`
- `2025-12.html`
- `rapport-2025-11.html`
- `seo-monitoring-2025-12.html`

### Ce qui est extrait automatiquement

Le système extrait automatiquement du HTML :

1. **Titre** (balise `<title>`)
2. **Date** (depuis le nom du fichier)
3. **Statistiques** :
   - Total Impressions
   - Total Clicks
   - Average CTR
   - Average Position
   - Organic Sessions

Ces données sont extraites en cherchant les classes CSS :
- `.hero-meta-item`
- `.kpi-card`
- `.kpi-value`
- `.kpi-label`

## 🔧 Workflow d'ajout d'un nouveau rapport

### Méthode automatique (recommandée)

```bash
# 1. Copier le fichier HTML
copy nouveau-rapport.html public\reports\2026-01.html

# 2. Synchroniser
php bin/console app:sync-reports

# 3. Vérifier sur le site
# → Le rapport apparaît automatiquement !
```

### Méthode manuelle (via interface web)

1. Copier le fichier dans `public/reports/`
2. Se connecter en tant qu'admin
3. Aller sur la page "Rapports"
4. Cliquer sur "🔄 Synchroniser les rapports"

## 🎯 Prochaines améliorations possibles

### Phase 2 - Upload via interface
- [ ] Formulaire d'upload de fichiers HTML
- [ ] Validation automatique du format
- [ ] Édition des métadonnées

### Phase 3 - Génération automatique
- [ ] Stocker les données en base
- [ ] Générer le HTML depuis les données
- [ ] Templates personnalisables

### Phase 4 - Analytics avancés
- [ ] Comparaison entre périodes
- [ ] Graphiques d'évolution
- [ ] Export PDF des rapports
- [ ] Alertes et notifications

## 🐛 Dépannage

### Les rapports n'apparaissent pas

```bash
# Vérifier que les fichiers sont dans le bon dossier
dir public\reports

# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT * FROM report"

# Re-synchroniser
php bin/console app:sync-reports
```

### Erreur "Class not found"

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier l'autoload
composer dump-autoload
```

### Les métadonnées ne sont pas extraites

Vérifiez que votre HTML contient les classes CSS attendues :
- `.kpi-card` pour les cartes de statistiques
- `.kpi-value` pour les valeurs
- `.kpi-label` pour les labels

## 📊 Structure de la base de données

```sql
CREATE TABLE report (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period VARCHAR(7) UNIQUE NOT NULL,      -- Format: 2025-11
    filename VARCHAR(255) NOT NULL,          -- Nom du fichier
    title TEXT,                              -- Titre du rapport
    description TEXT,                        -- Description
    impressions INT,                         -- Total impressions
    clicks INT,                              -- Total clicks
    ctr FLOAT,                               -- CTR moyen
    position INT,                            -- Position moyenne
    organic_sessions INT,                    -- Sessions organiques
    report_date DATETIME,                    -- Date du rapport
    created_at DATETIME NOT NULL,            -- Date de création
    is_active BOOLEAN NOT NULL DEFAULT 1     -- Actif ou non
);
```

## ✅ Vérification de l'installation

Checklist finale :

- [ ] Dossier `public/reports/` existe
- [ ] Au moins un fichier HTML avec le bon format de nom
- [ ] Migration exécutée (`report` table existe)
- [ ] Commande `app:sync-reports` fonctionne
- [ ] Rapports visibles sur `/reports`
- [ ] Clic sur un rapport l'affiche en iframe
- [ ] Menu "Rapports" apparaît dans la navbar

## 🎉 Résultat attendu

Après installation, vous devriez avoir :

1. **Dashboard** avec liste des rapports récents
2. **Page "Rapports"** avec tous les rapports en grille
3. **Visualisation** de chaque rapport dans l'interface
4. **Synchronisation automatique** des nouveaux fichiers
5. **Extraction automatique** des statistiques clés

---

**Questions ?** Consultez les logs avec `php bin/console app:sync-reports -vv`
