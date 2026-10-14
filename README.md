# UNITECH – Système de Gestion Académique

## Présentation

Cette application est une refonte du template d'administration existant en une interface de gestion académique pour l'Université Internationale des Nouvelles Technologies (UNITECH). Elle conserve la structure visuelle du dashboard initial tout en réorganisant les blocs pour refléter un espace étudiant moderne, confortable et fonctionnel.

## Technologies utilisées

- HTML5
- CSS3 / Bootstrap 5
- JavaScript / jQuery
- Chart.js
- Template admin existant (PolluxUI)

## Structure du projet

- `index.html` : tableau de bord principal de l'espace étudiant
- `assets/` : feuilles de style, images, scripts et ressources graphiques
- `pages/` : pages de démonstration et composants du template
- `docs/` : documentation de référence du projet

## Installation locale

1. Placez le dossier `unitech.com` dans le répertoire web de votre serveur local (XAMPP/WAMP/MAMP).
2. Démarrez Apache.
3. Ouvrez l'URL suivante dans votre navigateur :
   - `http://localhost/unitech.com/`

## Palette graphique

Les couleurs principales du projet sont inspirées du logo UNITECH :

- Bleu principal : `#5d8cf4`
- Bleu profond : `#1d3968`
- Jaune académique : `#f4dc61`
- Blanc / gris doux pour les cartes et le fond

## Évolution prévue

Le rendu actuel correspond à une base front-end fonctionnelle et prête à être enrichie par un backend avec :

- authentification des étudiants et administrateurs,
- gestion des données académiques,
- CRUD sur étudiants, résultats, cours et documents,
- imports Excel et validations de paiements,
- contrôle des permissions et journaux d'audit.

## Personnalisation

Pour adapter le design, il suffit de modifier les variables CSS du bloc `:root` dans `index.html` ou d'ajouter un fichier de style dédié dans `assets/css/`.
