# Système de Gestion des Stages

Bienvenue dans le projet de Système de Gestion des Stages ! Cette application web est conçue pour aider les étudiants à gérer leurs informations de stage et à générer les documents nécessaires. De plus, les professeurs peuvent suivre l'avancement des stages de leurs étudiants.

## Table des matières

- [Aperçu du projet](#aperçu-du-projet)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Contribution](#contribution)
- [Licence](#licence)

## Aperçu du projet

Ce projet a été développé pendant un stage de six semaines, du 22 mai au 3 juillet 2024, à l'Institut Limayrac. L'objectif principal était de créer un outil complet pour la gestion des stages des étudiants, incluant la génération de documents et le suivi par les professeurs.

## Fonctionnalités

- **Portail Étudiant**
  - Saisie des informations de stage
  - Génération des documents de stage (fiche de renseignement, convention de stage, attestation de stage)
- **Portail Professeur**
  - Suivi de l'avancement des stages des étudiants
  - Accès aux dossiers de stage des étudiants
- **Portail Administrateur**
  - Suivi de l'avancement des stages de tout les étudiants
  - Accès aux dossiers de stage de tout les étudiants

## Technologies utilisées

### Outils de développement

- **VS Code**
  - Choisi pour sa flexibilité, ses extensions utiles pour le développement web, et sa popularité dans la communauté des développeurs.
- **Trello**
  - Utilisé pour la gestion de projet et la répartition des tâches.
- **GitHub**
  - Pour le contrôle de version et la collaboration.

### Base de données

- **XAMPP & phpMyAdmin**
  - Utilisés pour gérer et visualiser la base de données.

### Outils supplémentaires

- **Balsamiq**
  - Utilisé pour créer des maquettes et planifier la mise en page du site.
- **Looping**
  - Pour visualiser et effectuer des modifications sur la base de données.

## Installation

Pour installer et exécuter ce projet en local, suivez ces étapes :

1. Clonez le dépôt :
    ```sh
    git clone https://github.com/votre-utilisateur/systeme-gestion-stages.git
    ```
2. Accédez au répertoire du projet :
    ```sh
    cd systeme-gestion-stages
    ```
3. Installez les dépendances :
    ```sh
    npm install
    ```
4. Configurez la base de données :
    - Importez le fichier SQL fourni dans phpMyAdmin.
    - Configurez la connexion à la base de données dans le fichier `config.php`.

5. Démarrez le serveur de développement :
    ```sh
    npm start
    ```

## Utilisation

### Pour les étudiants

1. Connectez-vous à votre compte étudiant.
2. Saisissez les informations relatives à votre stage.
3. Générez les documents nécessaires (fiche de renseignement, convention de stage, attestation de stage).

### Pour les professeurs

1. Connectez-vous à votre compte professeur.
2. Consultez les dossiers de stage de vos étudiants.
3. Suivez l'avancement des démarches de stage.

### Pour l'admin

1. Connectez-vous à votre compte admin.
2. Consultez les dossiers de stage de tout les étudiants.
3. Suivez l'avancement des démarches de stage.

## Contribution

Les contributions sont les bienvenues ! Pour contribuer au projet, veuillez suivre les étapes ci-dessous :

1. Forkez le dépôt.
2. Créez une branche pour votre fonctionnalité ou correction de bug (`git checkout -b feature/ma-fonctionnalite`).
3. Commitez vos modifications (`git commit -m 'Ajouter ma fonctionnalité'`).
4. Pushez votre branche (`git push origin feature/ma-fonctionnalite`).
5. Ouvrez une Pull Request.

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
