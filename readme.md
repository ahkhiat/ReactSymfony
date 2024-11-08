# MonApplication

Cette application est une plateforme développée avec Symfony pour le back-end et React pour le front-end. Elle utilise une base de données MySQL pour stocker les données utilisateur et expose une API RESTful pour la communication entre le back-end et le front-end.

## 🛠️ Technologies

- **Back-end** : Symfony 7 (PHP)
- **Front-end** : React (JavaScript)
- **Base de données** : MySQL
- **Authentification** : Utilisation de JSON Web Token (JWT) pour l'accès sécurisé (optionnel)

## 📋 Prérequis

- **PHP** : Version 8.2 ou supérieure
- **MySQL** : Version 10.5 (ou supérieure) pour MariaDB / 8.0 (ou supérieure) pour MySQL standard
- **Composer** : Dernière version pour la gestion des dépendances Symfony
- **Node.js** et **npm** : Pour la gestion des dépendances du front-end React

## ⚙️ Installation

### 1. Cloner le projet

```bash
git clone https://github.com/username/monapplication.git
cd monapplication
```

### 2. Installation des dépendances

Back-end (Symfony)

```bash
composer install
```

Front-end (React)

```bash
cd frontend
npm install
```

### 3. Configuration de l'environnement

Créer un fichier .env à la racine du projet Symfony et configurer les variables d'environnement nécessaires

### 4. Création de la base de données 

```bash
php bin/console doctrine:database:create
```

### 5. Migration de la base de données 

```bash
php bin/console make:migration
php bin/console doctrine:migration:migrate
```

## 🚀 Lancer l'application

### Back-end
```bash
cd backend
php bin/console symfony serve
```

### Front-end
```bash
cd frontend
npm run dev
```