# Architecture Documentation

This document provides a technical overview of Taskio's architecture, project structure, and design patterns.

## Table of Contents

- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Design Patterns](#design-patterns)
- [Database Schema](#database-schema)
- [Frontend Architecture](#frontend-architecture)
- [Security Architecture](#security-architecture)
- [API Design](#api-design)

## Technology Stack

### Backend

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Framework | Symfony | 7.3 | PHP web application framework |
| PHP | PHP | 8.2+ | Server-side language |
| Database | MariaDB | 11.4 | Relational database |
| ORM | Doctrine ORM | 3.5 | Database abstraction layer |
| Authentication | Symfony Security | - | User authentication & authorization |
| Email | Symfony Mailer | - | Email delivery system |
| Testing | PHPUnit | 11.5 | Unit and functional testing |
| Fixtures | Foundry | - | Test data generation |

### Frontend

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Build Tool | Vite | 6.3 | Fast development and bundling |
| CSS Framework | Tailwind CSS | 3.4 | Utility-first CSS framework |
| UI Components | DaisyUI | 4.12 | Component library for Tailwind |
| JavaScript | Stimulus | - | Modest JavaScript framework |
| Navigation | Turbo | - | SPA-like page navigation |
| Drag & Drop | SortableJS | - | Drag and drop functionality |

### DevOps

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Containerization | Docker + Compose | Development environment |
| Web Server | Apache | HTTP server (in Docker) |
| Mail Testing | Mailpit | Development email testing |

## Project Structure

```
taskio/
├── assets/                     # Frontend assets
│   ├── controllers/            # Stimulus controllers
│   │   ├── hello_controller.js
│   │   └── sortable_controller.js
│   ├── styles/                 # CSS files
│   │   └── app.css            # Tailwind imports
│   ├── app.js                 # Main JavaScript entry
│   └── bootstrap.js           # Stimulus bootstrap
│
├── config/                     # Symfony configuration
│   ├── packages/               # Bundle configurations
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── twig.yaml
│   │   └── ...
│   ├── routes/                 # Routing definitions
│   │   └── routes.yaml
│   └── services.yaml           # Service container config
│
├── docker/                     # Docker configuration
│   ├── apache.conf             # Apache virtual host
│   └── vite/                   # Vite container setup
│
├── migrations/                 # Database migrations
│   └── VersionYYYYMMDDHHMMSS.php
│
├── public/                     # Web root
│   ├── build/                  # Compiled assets
│   └── index.php               # Application entry point
│
├── src/                        # Application source code
│   ├── Command/                # CLI commands
│   │   └── AccountResetPasswordCommand.php
│   │
│   ├── Controller/             # HTTP controllers (thin — no EntityManager)
│   │   ├── Admin/              # Admin-specific controllers
│   │   │   ├── AccountController.php
│   │   │   └── BoardController.php
│   │   ├── AccountController.php
│   │   ├── BoardController.php
│   │   ├── CardController.php
│   │   ├── LaneController.php
│   │   ├── RegistrationController.php
│   │   ├── SecurityController.php
│   │   └── ...
│   │
│   ├── Dto/                    # Input DTOs (form mapping + validation)
│   │   ├── Account/            # ProfileInput, RegistrationInput, ...
│   │   ├── Board/              # BoardInput, InvitationInput
│   │   ├── Card/               # CardInput, CardMoveInput
│   │   └── Lane/               # LaneInput
│   │
│   ├── Entity/                 # Doctrine entities
│   │   ├── Account.php         # The user entity (implements UserInterface)
│   │   ├── Board.php
│   │   ├── BoardInvitation.php
│   │   ├── Card.php
│   │   ├── Lane.php
│   │   └── ResetPasswordRequest.php
│   │
│   ├── EntityListener/
│   │   └── BoardOwnerListener.php
│   │
│   ├── Enum/
│   │   └── CardStatus.php
│   │
│   ├── Form/                   # Form types (data_class = DTO, never an entity)
│   │   ├── BoardType.php
│   │   ├── CardType.php
│   │   ├── LaneType.php
│   │   ├── RegistrationFormType.php
│   │   └── ...
│   │
│   ├── Repository/             # Data access + save()/remove() write entry points
│   │   ├── AccountRepository.php
│   │   ├── BoardInvitationRepository.php
│   │   ├── BoardRepository.php
│   │   ├── CardRepository.php
│   │   └── LaneRepository.php
│   │
│   ├── Security/               # Security components
│   │   ├── EmailVerifier.php
│   │   ├── UserChecker.php
│   │   └── Voter/
│   │       └── BoardVoter.php
│   │
│   ├── Service/                # Business logic (one folder per domain)
│   │   ├── Account/            # AccountService, RegistrationService
│   │   ├── Board/              # BoardService, BoardInvitationService, CardMover
│   │   ├── Card/               # CardService
│   │   ├── Lane/               # LaneService
│   │   └── ContactMailer.php
│   │
│   ├── Factory/                # Foundry factories
│   │   ├── AccountFactory.php
│   │   ├── BoardFactory.php
│   │   ├── CardFactory.php
│   │   └── LaneFactory.php
│   │
│   ├── Story/                  # Foundry stories (fixture scenarios)
│   ├── Twig/Components/        # Twig components (Card, Lane, Hero)
│   └── Kernel.php              # Application kernel
│
├── templates/                  # Twig templates
│   ├── admin/                  # Admin templates
│   ├── board/                  # Board templates
│   ├── card/                   # Card templates
│   ├── security/               # Auth templates
│   └── base.html.twig          # Base layout
│
├── tests/                      # Test suite
│   ├── Unit/                   # Unit tests (Dto, Entity, Form, Security, Service, ...)
│   ├── Integration/            # Integration tests (reserved)
│   ├── Functional/             # Functional HTTP tests
│   │   └── Controller/
│   └── fixtures/vite/          # Stub Vite manifest used during tests
│
├── var/                        # Generated files
│   ├── cache/                  # Application cache
│   └── log/                    # Application logs
│
├── vendor/                     # Composer dependencies
│
├── .env                        # Environment variables template
├── .env.local                  # Local environment overrides
├── compose.yaml                # Docker Compose configuration
├── composer.json               # PHP dependencies
├── Dockerfile                  # Production Docker image
├── package.json                # Node.js dependencies
├── phpunit.dist.xml            # PHPUnit configuration
├── symfony.lock                # Symfony Flex lock file
├── tailwind.config.js          # Tailwind configuration
└── vite.config.js              # Vite configuration
```

## Design Patterns

### Layered write flow: DTO → Service → Repository (mandatory)

Every write in the application flows through the same layered chain. Controllers
are thin: they handle routing, security, forms, flash messages and redirects, and
**never** touch `EntityManager` directly.

```
HTTP Request
   │  Form maps to a dedicated Input DTO (data_class = XxxInput), not the entity
   ▼
Controller ── $dto ──► Service ── entity ──► Repository::save()/remove() ──► Doctrine
 (thin)              (business logic,        (single persistence
                      owns the transaction)   entry point)
```

Rules enforced across `src/`:

1. Each form is mapped to a DTO (`App\Dto\...`), never to a Doctrine entity.
2. No `EntityManagerInterface` / `persist` / `flush` / `remove` in `src/Controller/`.
3. Persistence goes exclusively through a Repository `save()` / `remove()` method.
4. Every mutation is orchestrated by a Service (`App\Service\...`).
5. Input validation (`Assert\*`) lives on the DTO; entities keep only integrity
   guards (`UniqueEntity`, column constraints) as defense in depth.

> **Note on naming:** the user entity is `App\Entity\Account` (it implements
> `UserInterface`).

Example for the Board domain:

```php
// Controller (thin)
public function new(Request $request, BoardService $boardService): Response
{
    $input = new BoardInput();
    $form = $this->createForm(BoardType::class, $input);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $boardService->create($input, $this->getUser());
        return $this->redirectToRoute('app_board_index');
    }
    return $this->render('board/new.html.twig', ['form' => $form]);
}

// Service (business logic + transaction)
public function create(BoardInput $input, Account $owner): Board
{
    $board = (new Board())->setTitle($input->title)->setOwner($owner);
    $this->boards->save($board);          // Repository owns persistence
    return $board;
}
```

The AJAX `card_move` endpoint receives its DTO straight from the JSON body via
`#[MapRequestPayload] CardMoveInput`, then delegates to `CardService`.

### Repository Pattern

**Purpose**: Separate data access logic from business logic.

**Implementation**:
```php
// src/Repository/BoardRepository.php
class BoardRepository extends ServiceEntityRepository
{
    /**
     * Persist a board. Single entry point for board writes.
     */
    public function save(Board $board, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($board);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Find boards visible to the given user (either owner or member).
     * @return Board[]
     */
    public function findVisibleForUser(Account $user): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.accounts', 'a')
            ->andWhere('b.owner = :user OR a = :user')
            ->setParameter('user', $user)
            ->distinct()
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

Repositories expose read queries freely (controllers may call them for display),
but `save()` / `remove()` are only ever called from the Service layer.

**Benefits**:
- Clean separation of concerns
- Reusable query logic
- Easy to test and mock

### Voter Pattern

**Purpose**: Fine-grained authorization control.

**Implementation**:
```php
// src/Security/Voter/BoardVoter.php
final class BoardVoter extends Voter
{
    public const VIEW   = 'BOARD_VIEW';
    public const EDIT   = 'BOARD_EDIT';
    public const DELETE = 'BOARD_DELETE';
    public const MANAGE_COLLABORATORS = 'BOARD_MANAGE_COLLABORATORS';

    public function __construct(private readonly BoardRepository $boardRepository) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE_COLLABORATORS], true)
            && $subject instanceof Board;
    }

    protected function voteOnAttribute(string $attribute, mixed $board, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Account) {
            return false;
        }

        // Admin can do anything
        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return true;
        }

        // Owner can do anything on his board
        if ($board->getOwner()?->getId() === $user->getId()) {
            return true;
        }

        // Check if the user is a member of the board
        $isMember = $this->boardRepository->isBoardMember($board, $user);

        return match ($attribute) {
            self::VIEW   => $isMember,
            self::EDIT   => $isMember,
            self::DELETE => false,
            self::MANAGE_COLLABORATORS => false, // Only owner and admins
        };
    }
}
```

**Benefits**:
- Centralized authorization logic
- Easy to extend and maintain
- Reusable across controllers

### Service Layer Pattern

**Purpose**: Encapsulate complex business logic.

**Implementation**:
```php
// src/Service/Board/BoardInvitationService.php
final class BoardInvitationService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly BoardInvitationRepository $invitationRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {}

    public function acceptInvitation(BoardInvitation $invitation, Account $user): void
    {
        $board = $invitation->getBoard();

        if (!$this->isUserAlreadyMember($user, $board)) {
            $board->addAccount($user);
        }

        $invitation->setIsAccepted(true);
        $this->invitationRepository->save($invitation);
    }
}
```

Note how the mutation is persisted through the repository's `save()` method —
the service never touches the `EntityManager` directly.

**Benefits**:
- Business logic separate from controllers
- Easy to test
- Reusable across the application

### Form Type Pattern

**Purpose**: Encapsulate form logic and validation.

**Implementation**:
```php
// src/Form/BoardType.php
class BoardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                // Map an empty submission to '' (not null) so the non-nullable
                // DTO property is satisfied and NotBlank reports it cleanly.
                'empty_data' => '',
                'label' => 'Board title',
                'attr' => ['class' => 'input input-bordered'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        // Validation lives on BoardInput's constraints.
        $resolver->setDefaults([
            'data_class' => BoardInput::class,
        ]);
    }
}
```

**Benefits**:
- Reusable form definitions
- Type-safe form handling
- Validation rules centralized on the DTO (`App\Dto\Board\BoardInput`)

## Database Schema

### Entity Relationships

```
Account
  ├── owns many Boards (one-to-many, Board.owner)
  └── is a member of many Boards (many-to-many, Account.boards / Board.accounts)

Board
  ├── owned by one Account (many-to-one, non-null)
  ├── has many member Accounts (many-to-many)
  └── has many Lanes (one-to-many, ordered by position, orphan removal)

BoardInvitation
  ├── belongs to one Board (many-to-one, cascade delete)
  └── invited by one Account (many-to-one)

Lane
  ├── belongs to one Board (many-to-one, cascade delete)
  └── has many Cards (one-to-many, ordered by position)

Card
  ├── belongs to one Lane (many-to-one, cascade delete)
  └── has status (enum CardStatus, nullable)
```

### Key Entities

**Account Entity** (the user, implements `UserInterface`):
- `id` (primary key)
- `email` (unique)
- `password` (hashed)
- `name`, `lastname`
- `role` (string, e.g. `ROLE_USER` / `ROLE_ADMIN`)
- `isVerified` (bool)
- Relationships: ownedBoards, boards (memberships)

**Board Entity:**
- `id` (primary key)
- `title`
- `owner` (Account foreign key, non-null)
- Relationships: owner, accounts (members), lanes

**BoardInvitation Entity:**
- `id` (primary key)
- `email`, `token` (unique)
- `createdAt`, `expiresAt`, `isAccepted`, `acceptedAt`
- Relationships: board, invitedBy (Account)

**Lane Entity:**
- `id` (primary key)
- `title`
- `position` (integer for ordering)
- `board` (Board foreign key)
- Relationships: board, cards

**Card Entity:**
- `id` (primary key)
- `title`
- `description`
- `status` (enum `CardStatus`, nullable)
- `position` (integer for ordering)
- `lane` (Lane foreign key)
- Relationships: lane

## Frontend Architecture

### Stimulus Controllers

Taskio uses **Stimulus** for JavaScript interactions.

**Sortable Controller** (Drag & Drop):
```javascript
// assets/controllers/sortable_controller.js
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    connect() {
        this.sortable = Sortable.create(this.element, {
            animation: 150,
            onEnd: this.onEnd.bind(this),
        });
    }

    async onEnd(event) {
        // Update positions via AJAX
        const url = event.item.dataset.updateUrl;
        await fetch(url, {
            method: 'PATCH',
            body: JSON.stringify({ position: event.newIndex }),
        });
    }
}
```

### Tailwind + DaisyUI

**Styling Approach**:
- **Tailwind CSS**: Utility-first CSS for custom designs
- **DaisyUI**: Pre-built components (buttons, cards, modals)
- **Responsive**: Mobile-first design

**Example**:
```html
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Board Title</h2>
        <p>Description here</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Open</button>
        </div>
    </div>
</div>
```

### Turbo (Hotwire)

**Purpose**: SPA-like navigation without full page reloads.

**Features**:
- Automatic page caching
- Form submissions via AJAX
- Progress bar during navigation

## Security Architecture

### Authentication

- **Symfony Security Bundle**: Handles user authentication
- **Password Hashing**: Uses Argon2i algorithm
- **Remember Me**: Persistent login tokens
- **Email Verification**: Confirms user email addresses

### Authorization

- **Role-Based Access Control (RBAC)**:
  - `ROLE_USER`: Standard user
  - `ROLE_ADMIN`: Administrator
- **Voter-Based Permissions**: Fine-grained access control per resource

### Security Measures

1. **CSRF Protection**: Built into Symfony forms
2. **XSS Prevention**: Twig auto-escapes output
3. **SQL Injection Protection**: Doctrine parameterized queries
4. **Rate Limiting**: Prevents spam and abuse
5. **Secure Password Reset**: Time-limited tokens

## API Design

### RESTful Principles

Taskio follows resource-oriented conventions (HTML forms only support GET/POST,
so mutations are POST routes):

| HTTP Method | Route | Name | Description |
|------------|-------|------|-------------|
| GET | `/board` | app_board_index | List the user's boards |
| GET/POST | `/board/new` | app_board_new | Create a new board |
| GET/POST | `/board/{id}/edit` | app_board_edit | Edit a board |
| POST | `/board/{id}` | app_board_delete | Delete a board (CSRF-protected) |
| POST | `/board/{id}/collaborator/invite` | app_board_invite_collaborator | Invite a collaborator |
| POST | `/board/{id}/collaborator/{userId}/remove` | app_board_remove_collaborator | Remove a collaborator |
| GET | `/board/invitation/{token}/accept` | app_board_accept_invitation | Accept an invitation |
| POST | `/card/cards/move` | card_move | Move a card (AJAX, JSON payload) |

### AJAX Endpoints

For dynamic interactions (drag & drop, etc.) the JSON payload is mapped to a
DTO with `#[MapRequestPayload]`, then the controller delegates to the service —
same layered flow as regular forms:

```php
// src/Controller/CardController.php
#[Route('/cards/move', name: 'card_move', methods: ['POST'])]
public function move(
    #[MapRequestPayload] CardMoveInput $input,
    CardRepository $cardRepo,
    LaneRepository $laneRepo,
    CardService $cardService
): JsonResponse {
    $card = $cardRepo->find($input->cardId);
    $lane = $laneRepo->find($input->toLaneId);

    if (!$card || !$lane) {
        return $this->json(['error' => 'not found'], 404);
    }

    $this->denyAccessUnlessGranted('BOARD_EDIT', $lane->getBoard());
    $cardService->move($card, $lane, $input->newIndex);

    return $this->json(['ok' => true]);
}
```

## Performance Considerations

1. **Database Indexing**: Foreign keys and frequently queried fields
2. **Lazy Loading**: Doctrine loads related entities on demand
3. **Asset Optimization**: Vite bundles and minifies assets
4. **Caching**: Symfony HTTP cache and OPcache

## Extensibility

### Adding New Features

1. **Create Entity**: Define the data model
2. **Create Repository**: Add `save()`/`remove()` and custom queries
3. **Create DTO**: Define the input shape and its validation constraints
4. **Create Service**: Orchestrate the mutation through the repository
5. **Create Form Type**: Map the form to the DTO (`data_class`)
6. **Create Controller**: Thin — form handling + service call, no EntityManager
7. **Create Templates**: Build the UI
8. **Add Routes**: Register URL patterns
9. **Write Tests**: Unit (DTO, Form, Service) + functional (HTTP)

### Configuration

- **Environment Variables**: Configure via `.env` files
- **Bundle Configuration**: Modify `config/packages/` files
- **Services**: Register services in `config/services.yaml`

## Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Stimulus Handbook](https://stimulus.hotwired.dev/)
- [Tailwind CSS](https://tailwindcss.com/docs)

---

[← Back to README](../README.md) | [Testing Guide](TESTING.md) | [Contributing →](../CONTRIBUTING.md)
