# Plan de refactorisation architecturale — Taskio

> Objectif : faire passer **100 % des flux de données par la chaîne `Controller → DTO → Service → Repository`**,
> conformément aux bonnes pratiques Symfony. Aucune écriture ne doit plus se faire « en direct »
> depuis un contrôleur via `EntityManager`, ni via un formulaire mappé sur une entité Doctrine.

- **Stack** : Symfony 7.3, PHP 8.2+, Doctrine ORM 3.5, MariaDB.
- **Branche de travail** : `refactor/dto-service-repository`.

---

## 1. Diagnostic de l'existant

| Domaine | DTO | Service | Repository (écriture) | État actuel |
|--------|-----|---------|------------------------|-------------|
| Contact | ✅ `ContactData` | ✅ `ContactMailer` | n/a (mail only) | **Conforme** (référence) |
| Board (CRUD) | ❌ | ❌ | partiel (lecture) | Contrôleur fait `persist/flush/remove` |
| Lane (CRUD) | ❌ | ❌ | partiel | Contrôleur calcule la position + `persist/flush` |
| Card (CRUD) | ❌ | ❌ | partiel | Contrôleur calcule la position + `persist/flush` |
| Card (move) | ❌ | ✅ `CardMover` | ✅ `CardRepository` | Service OK, manque DTO d'entrée |
| Account (profil) | ❌ | ❌ | ❌ | Contrôleur hash le mot de passe + `flush` |
| Account (admin) | ❌ | ❌ | partiel | Contrôleur `flush/remove` |
| Registration | ❌ | partiel (`EmailVerifier`) | ❌ | Contrôleur hash + `persist/flush` |
| Reset password | ❌ | bundle | bundle | Logique dans le contrôleur |
| Invitation | ❌ (string email) | ✅ `BoardInvitationService` | ✅ | Service OK, manque DTO typé |

**Problèmes transverses constatés :**

1. **Formulaires couplés aux entités** : `data_class = Card::class` (etc.) → la requête HTTP écrit directement dans l'entité persistée.
2. **Logique métier dans les contrôleurs** : positionnement (`findMaxPositionInLane`), hachage de mot de passe, vérifications IDOR, CSRF, rate-limiting.
3. **Accès données dispersés** : `$em->getRepository(Card::class)->find(...)` dans `CardController::move` au lieu du repo injecté.
4. **`flush()` éparpillé** dans tous les contrôleurs → pas de point unique de transaction.
5. **`docs/ARCHITECTURE.md` désynchronisé** : parle d'une entité `User` alors que l'entité réelle est `Account`.

---

## 2. Architecture cible

```
HTTP Request
    │
    ▼
┌─────────────┐   Form mappé sur DTO (data_class = XxxDto)
│ Controller  │   - fin : routing, form, sécurité (IsGranted), flash, redirect
└─────┬───────┘   - NE TOUCHE PLUS à EntityManager
      │  $dto (validé)
      ▼
┌─────────────┐   - logique métier + orchestration
│   Service   │   - mappe DTO → Entity (création/MAJ)
└─────┬───────┘   - appelle Repository, gère la transaction (flush)
      │  Entity
      ▼
┌─────────────┐   - toutes les requêtes (lecture ET écriture)
│ Repository  │   - save() / remove() / findXxx()
└─────┬───────┘
      ▼
   Doctrine / DB
```

### Règles non négociables

1. **Tout formulaire est mappé sur un DTO** (`data_class = …Dto`), jamais sur une entité.
2. **Aucun `EntityManager`, `persist`, `flush`, `remove` dans un contrôleur.**
3. **Toute persistance passe par une méthode de Repository** (`save()`, `remove()`).
4. **Toute mutation de données passe par un Service** qui possède la transaction.
5. **Le contrôleur ne reçoit/retourne que des DTO et des entités déjà chargées** (ParamConverter pour la lecture).
6. **La validation métier vit sur le DTO** (contraintes `Assert\*`), pas sur l'entité.

---

## 3. Conventions

| Type | Namespace | Suffixe | Exemple |
|------|-----------|---------|---------|
| DTO d'entrée | `App\Dto\<Domaine>` | `Input` / `Data` | `App\Dto\Board\BoardInput` |
| DTO de sortie (si besoin API) | `App\Dto\<Domaine>` | `View` | `App\Dto\Board\BoardView` |
| Service applicatif | `App\Service\<Domaine>` | `Service` / `Manager` | `App\Service\Board\BoardService` |
| Mapper (DTO↔Entity) | `App\Mapper` | `Mapper` | `App\Mapper\BoardMapper` |
| Exception métier | `App\Exception` | `Exception` | `App\Exception\InvitationException` |

- **Mapping DTO ↔ Entity** : méthode `toEntity()` / `fromEntity()` dans un **Mapper** dédié (ou méthode statique sur le DTO pour les cas simples). Cela garde les Services lisibles.
- **`save()` dans les Repository** :
  ```php
  public function save(Board $board, bool $flush = true): void
  {
      $this->getEntityManager()->persist($board);
      if ($flush) { $this->getEntityManager()->flush(); }
  }
  ```

---

## 4. Plan par domaine (livrables)

### 4.1 Board
- **DTO** : `App\Dto\Board\BoardInput { #[Assert\NotBlank] string $title; ?string $description; }`
- **Form** : `BoardType` → `data_class = BoardInput`.
- **Service** : `App\Service\Board\BoardService`
  - `create(BoardInput $in, Account $owner): Board`
  - `update(Board $board, BoardInput $in): void`
  - `delete(Board $board): void`
- **Repository** : ajouter `save()` / `remove()` à `BoardRepository`.
- **Controller** : `new/edit/delete` n'appellent plus `$entityManager`.

### 4.2 Lane
- **DTO** : `LaneInput { #[Assert\NotBlank] string $title; }`
- **Service** : `LaneService`
  - `createForBoard(LaneInput $in, Board $board): Lane` (déplace ici le calcul `getNextPositionForBoard`)
  - `update(Lane $lane, LaneInput $in): void`
  - `delete(Lane $lane): void`
- **Repository** : `save()` / `remove()` sur `LaneRepository` (la requête de position existe déjà).

### 4.3 Card
- **DTO entrée** : `CardInput { string $title; ?string $description; ?CardStatus $status; }`
- **DTO move** : `CardMoveInput { #[Assert\Positive] int $cardId; int $toLaneId; #[Assert\PositiveOrZero] int $newIndex; }`
- **Service** : `CardService`
  - `createForLane(CardInput $in, Lane $lane): Card` (déplace le calcul `findMaxPositionInLane`)
  - `update(Card $card, CardInput $in): void`
  - `delete(Card $card): void`
  - `move(CardMoveInput $in): Card` (s'appuie sur `CardMover` existant ; charge Card/Lane via repos).
- **Controller `move`** : remplace `json_decode` + `$em->getRepository()` par
  `#[MapRequestPayload] CardMoveInput $input` puis `$cardService->move($input)`.

### 4.4 Account / Profil
- **DTO** : `ProfileInput { string $fullName; ?string $plainPassword; … }`
- **Service** : `AccountService`
  - `updateProfile(Account $account, ProfileInput $in): void` (gère le hachage du mot de passe → sort du contrôleur)
- **Repository** : `save()` sur `AccountRepository`.

### 4.5 Registration
- **DTO** : `RegistrationInput { #[Assert\Email] string $email; #[Assert\Length(min:…)] string $plainPassword; bool $agreeTerms; }`
- **Service** : `RegistrationService::register(RegistrationInput $in): Account`
  (hachage + persist + déclenche `EmailVerifier`). Le contrôleur ne fait que le `security->login`.

### 4.6 Admin / Account
- **DTO** : `AdminAccountInput { string $email; array $roles; … }`
- **Service** : `AccountService::adminUpdate()` / `delete()`.
- **Controller** : `edit/delete` délèguent au service.

### 4.7 Invitation (consolidation)
- **DTO** : `InvitationInput { #[Assert\Email] string $email; }` (remplace le `string $email` nu).
- `BoardInvitationService` est déjà conforme : ajuster les signatures pour accepter le DTO.

### 4.8 Reset password
- **DTO** : `ResetPasswordRequestInput { #[Assert\Email] string $email; }`, `NewPasswordInput { string $plainPassword; }`.
- **Service** : `PasswordResetService` enveloppant le bundle `symfonycasts/reset-password`.

---

## 5. Préoccupations transversales

- **Transactions** : un seul `flush()` par requête, détenu par le Service (via `Repository::save()` ou `wrapInTransaction` pour les opérations multi-entités comme `move`).
- **Validation** : déplacer toutes les contraintes `Assert\*` des entités/formulaires vers les **DTO**. Les entités gardent uniquement les contraintes d'intégrité DB (unique, nullable).
- **Sécurité** : les `#[IsGranted]` / Voters restent dans le contrôleur (point d'entrée HTTP). Les vérifications IDOR/CSRF restent côté contrôleur ; la logique métier (ex. « ne pas retirer l'owner ») descend dans le Service.
- **Mapping** : entrée via `#[MapRequestPayload]` (JSON/API) ou Form+DTO (HTML). Sortie HTML : on continue de passer l'entité au template (lecture seule), pas besoin de DTO de sortie pour le rendu Twig.
- **Exceptions** : créer `App\Exception\*` métier, converties en flash/HTTP par le contrôleur (ou un `ExceptionListener`).

---

## 6. Phasage (ordre d'exécution recommandé)

1. **Socle** : ajouter `save()/remove()` à tous les Repository + créer `App\Exception\DomainException` de base. *(non cassant)*
2. **Pilote Board** : DTO + `BoardService` + `BoardType` sur DTO + refonte `BoardController`. Valider le pattern de bout en bout (+ tests).
3. **Lane** puis **Card** (CRUD + move avec `CardMoveInput`).
4. **Account / Profil** et **Admin/Account**.
5. **Registration** + **Reset password**.
6. **Invitation** : passage au DTO.
7. **Nettoyage** : retirer les contraintes des entités/formulaires migrées, supprimer les usages directs d'`EntityManager` dans les contrôleurs (grep de contrôle).
8. **Documentation** : mettre à jour `docs/ARCHITECTURE.md` (corriger `User`→`Account`, documenter la chaîne DTO→Service→Repo).

> Chaque phase est **autonome, testable et mergeable** indépendamment — pas de big-bang.

---

## 7. Stratégie de tests

- **Unitaires** : un test par Service (mock du Repository) ; un test par Mapper (DTO↔Entity).
- **DTO** : tests de validation (contraintes `Assert`).
- **Fonctionnels** : conserver/compléter les tests contrôleurs existants (`tests/Functional`) pour garantir la non-régression des routes.
- **Garde-fou CI** : script/grep interdisant `EntityManagerInterface` dans `src/Controller/` (hors injection legacy tolérée temporairement).

---

## 8. Risques & points d'attention

| Risque | Mitigation |
|--------|------------|
| Régression sur les formulaires (DTO ≠ entité) | Migration domaine par domaine + tests fonctionnels avant/après. |
| Double validation (entité + DTO) | Retirer les `Assert` des entités au fil de la migration. |
| `move` concurrentiel | `CardMover` conserve `wrapInTransaction` + locks pessimistes existants. |
| Mots de passe | Centraliser le hachage dans `AccountService`/`RegistrationService`, jamais dans le contrôleur. |
| Volume de PR | Découpage en 8 phases mergeables. |

---

## 9. Definition of Done

- [x] Aucun `persist/flush/remove` ni `EntityManagerInterface` dans `src/Controller/`.
- [x] Aucun `data_class` pointant vers une entité dans `src/Form/` (les 10 formulaires mappent un DTO).
- [x] Chaque mutation passe par `Service → Repository::save()/remove()`.
- [x] DTO validés pour chaque flux d'entrée.
- [x] Tests unitaires (services + DTO + formulaires) **et fonctionnels** (chaîne HTTP complète) au vert — 131 tests.
- [x] `docs/ARCHITECTURE.md` mis à jour.

## 10. État de la mise en œuvre (implémenté)

Tous les domaines sont migrés vers la chaîne `Controller → DTO → Service → Repository` :

| Domaine | DTO(s) | Service | Repository (save/remove) |
|--------|--------|---------|--------------------------|
| Board | `Board\BoardInput` | `Service\Board\BoardService` | `BoardRepository` |
| Lane | `Lane\LaneInput` | `Service\Lane\LaneService` | `LaneRepository` |
| Card | `Card\CardInput`, `Card\CardMoveInput` | `Service\Card\CardService` (+ `CardMover`) | `CardRepository` |
| Account (profil) | `Account\ProfileInput` | `Service\Account\AccountService` | `AccountRepository` |
| Account (admin) | `Account\AdminAccountInput` | `Service\Account\AccountService` | `AccountRepository` |
| Registration | `Account\RegistrationInput` | `Service\Account\RegistrationService` | `AccountRepository` |
| Reset password | `Account\PasswordResetRequestInput`, `Account\NewPasswordInput` | `Service\Account\AccountService` | `AccountRepository` |
| Invitation / collab. | `Board\InvitationInput` | `Service\Board\BoardInvitationService` | `BoardInvitationRepository`, `AccountRepository` |
| Contact | `ContactData` | `Service\ContactMailer` | n/a (mail) |

Le endpoint AJAX `card_move` reçoit son DTO via `#[MapRequestPayload] CardMoveInput`.
La validation a été déplacée des entités/formulaires vers les DTO (les contraintes
d'intégrité et `UniqueEntity` restent côté entité comme défense en profondeur).
