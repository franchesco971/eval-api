# eval-api — Documentation de test

API REST construite avec **Symfony 7.4** et **API Platform 4.1**. Elle expose une ressource `Book` avec upload de couverture.

---

## Prérequis

- Docker & Docker Compose
- (Optionnel) PHP 8.1+ et Composer pour exécuter les commandes en local

---

## Démarrage

```bash
docker compose up -d
```

Les migrations et la création du répertoire `public/images/covers/` sont exécutées automatiquement par le script d'entrée du conteneur PHP.

Si les migrations ne sont pas jouées automatiquement :

```bash
docker exec eval-api-php php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Accès aux services

| Service        | URL locale                          |
|----------------|-------------------------------------|
| API (HTTP)     | http://localhost:15080              |
| Swagger UI     | http://localhost:15080/api          |
| OpenAPI (JSON) | http://localhost:15080/api/docs.json |
| PostgreSQL     | localhost:15432                     |

**Identifiants base de données :**

| Paramètre  | Valeur       |
|------------|--------------|
| Host       | localhost    |
| Port       | 15432        |
| Database   | app          |
| User       | app          |
| Password   | !ChangeMe!   |

---

## Endpoints disponibles

Base URL : `http://localhost:15080/api`

| Méthode  | Endpoint                | Description                        |
|----------|-------------------------|------------------------------------|
| `GET`    | `/books`                | Liste tous les livres              |
| `POST`   | `/books`                | Crée un livre                      |
| `GET`    | `/books/{id}`           | Récupère un livre                  |
| `PUT`    | `/books/{id}`           | Met à jour un livre                |
| `DELETE` | `/books/{id}`           | Supprime un livre                  |
| `POST`   | `/books/{id}/cover`     | Upload la couverture d'un livre    |

---

## Sécurité

Le firewall est configuré en mode `stateless` sans authentification requise. Tous les endpoints sont publiquement accessibles.

---

## Schéma des données

### Livre (`Book`)

| Champ            | Type       | Obligatoire | Description                          |
|------------------|------------|-------------|--------------------------------------|
| `title`          | string(150)| oui         | Titre du livre                       |
| `price`          | int        | oui         | Prix (en centimes ou unité entière)  |
| `resume`         | string(255)| non         | Résumé                               |
| `coverImagePath` | string(255)| non         | Chemin relatif de la couverture      |
| `createdAt`      | datetime   | —           | Généré automatiquement à la création |
| `updatedAt`      | datetime   | —           | Mis à jour automatiquement           |

---

## Exemples de requêtes

### Créer un livre

```http
POST /api/books
Content-Type: application/ld+json

{
  "title": "Les Misérables",
  "price": 1200,
  "resume": "Roman de Victor Hugo."
}
```

Réponse `201` :

```json
{
  "@id": "/api/books/1",
  "id": 1,
  "title": "Les Misérables",
  "price": 1200,
  "resume": "Roman de Victor Hugo.",
  "coverImagePath": null,
  "createdAt": "2026-04-27T10:00:00+00:00",
  "updatedAt": null
}
```

---

### Récupérer la liste des livres

```http
GET /api/books
Accept: application/ld+json
```

---

### Mettre à jour un livre

```http
PUT /api/books/1
Content-Type: application/ld+json

{
  "title": "Les Misérables — Édition complète",
  "price": 1500
}
```

---

### Uploader une couverture

```http
POST /api/books/1/cover
Content-Type: multipart/form-data

coverImage: <fichier image>
```

Contraintes du fichier :
- Formats acceptés : `image/jpeg`, `image/png`
- Taille maximale : **5 Mo**

Le champ peut s'appeler `coverImage` ou `file`.

Réponse `200` :

```json
{
  "id": 1,
  "coverImagePath": "a3f8e1c2d4b7f6a0e9d2c5b8a1f4e7d0.jpg"
}
```

L'image est servie statiquement via :

```
http://localhost:15080/images/covers/{coverImagePath}
```

---

### Supprimer un livre

```http
DELETE /api/books/1
```

Réponse `204 No Content`.

---

## Tester avec curl

```bash
# Créer un livre
curl -X POST http://localhost:15080/api/books \
  -H "Content-Type: application/ld+json" \
  -d '{"title":"Dune","price":900}'

# Uploader une couverture (id=1)
curl -X POST http://localhost:15080/api/books/1/cover \
  -F "coverImage=@/chemin/vers/image.jpg"

# Lister les livres
curl http://localhost:15080/api/books
```

---

## Exécuter les tests PHPUnit

```bash
docker exec eval-api-php php bin/phpunit
```

---

## Architecture — points clés

| Classe | Rôle |
|--------|------|
| `src/Entity/Book.php` | Entité Doctrine + configuration API Platform |
| `src/Repository/BookRepository.php` | Accès données (Doctrine) |
| `src/Service/BookCoverUploadService.php` | Logique métier d'upload (validation, gestion de fichiers, persistance) |
| `src/Controller/BookCoverUploadController.php` | Point d'entrée HTTP de l'upload (délègue au service) |

Les images uploadées sont stockées dans `public/images/covers/` sous un nom aléatoire (`bin2hex(random_bytes(16)).extension`). L'ancienne image est supprimée automatiquement lors d'un nouvel upload.

---

## Axes d'amélioration

### Authentification JWT

Actuellement l'API est publique (aucune authentification). L'ajout de JWT permettrait de sécuriser les opérations d'écriture (`POST`, `PUT`, `DELETE`, upload de couverture).

**Dépendances à ajouter :**

```bash
composer require lexik/jwt-authentication-bundle
```

**Étapes principales :**

1. Générer la paire de clés RSA :
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```
2. Créer une entité `User` implémentant `UserInterface` et `PasswordAuthenticatedUserInterface`
3. Configurer le firewall dans `config/packages/security.yaml` :
   ```yaml
   firewalls:
       login:
           pattern: ^/api/login
           stateless: true
           json_login:
               check_path: /api/login
               success_handler: lexik_jwt_authentication.handler.authentication_success
               failure_handler: lexik_jwt_authentication.handler.authentication_failure
       api:
           pattern: ^/api
           stateless: true
           jwt: ~
   access_control:
       - { path: ^/api/login, roles: PUBLIC_ACCESS }
       - { path: ^/api,       roles: IS_AUTHENTICATED_FULLY }
   ```
4. Ajouter les en-têtes `Authorization: Bearer <token>` dans toutes les requêtes protégées

---

### Stockage externe des images

Actuellement les couvertures sont stockées sur le système de fichiers local (`public/images/covers/`), ce qui pose des problèmes en environnement multi-instance ou conteneurisé (pas de persistance entre déploiements).

**Solution recommandée : Flysystem + stockage objet (S3, MinIO, OVH Object Storage…)**

**Dépendances à ajouter :**

```bash
composer require league/flysystem-bundle league/flysystem-aws-s3-v3
```

**Étapes principales :**

1. Configurer un adaptateur Flysystem dans `config/packages/flysystem.yaml` :
   ```yaml
   flysystem:
       storages:
           cover_images.storage:
               adapter: 'aws'
               options:
                   client: Aws\S3\S3Client
                   bucket: '%env(S3_BUCKET)%'
                   prefix: covers/
   ```
2. Injecter `FilesystemOperator $coverImagesStorage` dans `BookCoverUploadService` à la place du chemin local
3. Remplacer les appels `file_exists` / `unlink` / `move` par les méthodes Flysystem :
   ```php
   $this->coverImagesStorage->delete($oldPath);
   $this->coverImagesStorage->write($fileName, $file->getContent());
   ```
4. Exposer une URL publique via la configuration de bucket (ACL public ou URL signée)

**Avantages :**
- Persistance indépendante des conteneurs
- Scalabilité horizontale
- Le `BookCoverUploadService` ne change pas d'interface : seul l'adaptateur est swappé via injection de dépendances (conforme OCP)
