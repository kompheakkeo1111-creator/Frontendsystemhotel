# CODING_STANDARDS.md — Development Setup, Coding Standards, Testing & Deployment

> This file records the team's agreement on **how we set up, write, test and deploy**
> this project. It covers teacher's Phases 5–8 (pages 5–8 of the guideline) and
> doubles as the *Developer Documentation* required by Assignment Criterion 7.

---

# PART A — Phase 5: Development Setup

Every team member must have the same toolchain before writing any code.

## A.1 Required software

| Tool | Version | Purpose | Install link |
|------|---------|---------|--------------|
| Git | latest | Version control | https://git-scm.com |
| IDE | VS Code (recommended) or PhpStorm | Editing code | https://code.visualstudio.com |
| Web Server | WAMP or XAMPP (Apache + PHP 8.x) | Run the backend locally | https://www.wampserver.com |
| Database | MySQL 5.7+ (bundled with WAMP) | Store hotel data | (comes with WAMP) |
| Browser | Chrome / Edge + DevTools | Test UI & debug API | — |
| Node.js | any LTS (only for JS unit tests) | Run `frontend_calc.test.js` | https://nodejs.org |

## A.2 One-time setup (each member)

```bash
# 1. Configure Git identity (Gmail = the one on your GitHub account)
git config --global user.name  "Your Full Name"
git config --global user.email "yourgmail@gmail.com"

# 2. Clone the repository into the web root
cd C:\wamp64\www
git clone https://github.com/kompheakkeo1111-creator/Frontendsystemhotel.git
cd Frontendsystemhotel

# 3. Start WAMP → Apache + MySQL must be green
```

## A.3 Project dependencies

This project is **intentionally dependency-free** — no Composer, no npm packages:

| Layer | Dependencies | How it is installed |
|-------|--------------|---------------------|
| Backend | PHP 7.4+ extensions: `pdo_mysql`, `json`, `session` | Bundled with WAMP/XAMPP |
| Frontend | Bootstrap 5, Chart.js, Font Awesome | Loaded from CDN in the HTML |
| Database | `hotel_management_1_2.sql` schema | Auto-installed on first visit to `/backend/` |
| Tests | Plain `php` (+ `node` for one JS suite) | Nothing to install |

> **Why no Composer/PHPUnit?** The hosting target (InfinityFree shared hosting)
> and the WAMP classroom machines run simplest with zero dependencies, so the
> team wrote its own tiny test runner in `tests/lib/TestRunner.php`.

## A.4 Verify your setup

```bash
php -v                 # PHP 8.x shows
mysql --version        # MySQL shows
git --version          # Git shows
php tests/run_all.php --unit   # should print passing tests
```

If `php tests/run_all.php --unit` passes, your setup is correct.

---

# PART B — Phase 6: Coding Standards

These rules are **mandatory** for every Pull Request. The reviewer rejects a PR
that breaks them.

## B.1 Naming conventions

| Element | Convention | Example |
|---------|-----------|---------|
| PHP class (PascalCase) | Noun, singular | `ReservationController`, `RoomType`, `Guest` |
| Controller file | `XxxController.php` + class `XxxController` | `BillingController.php` |
| Action method | camelCase + `Action` suffix | `indexAction()`, `viewAction()` |
| Model class | Singular noun | `Reservation` (table `reservations`) |
| Private/protected property | camelCase | `$active`, `$validStatus`, `$taxRate` |
| Local variable | camelCase, descriptive | `$nights`, `$totalWithTax` |
| Method/function | verb + object, camelCase | `computeNights()`, `computeTotal()`, `isLoggedIn()` |
| DB table | snake_case, plural | `room_types`, `check_in_out` |
| DB column | snake_case | `check_in_date`, `tax_rate` |
| PHP constant / config define | SCREAMING_SNAKE | `DB_HOST`, `BASE_URL` |
| CSS class | kebab-case, block__element | `room-card`, `admin-sidebar` |
| JS function | camelCase | `loadRooms()`, `formatPrice()` |
| JS constant | SCREAMING_SNAKE | `API_BASE` |
| Test marker data | prefix `qa-` | `qa-tester-1@example.test` (auto-cleaned) |
| Git branch | `feature/kebab-case` | `feature/room-filter` |
| Commit | Conventional Commits | `feat: add room availability search` |

**Rules:**
- Never use abbreviations that are not obvious (`$tmp` is OK, `$x1` is not).
- One class = one file = one responsibility name (`ReservationController`, not `Utils`).
- Boolean variables start with `is`/`has`/`can` (`isLoggedIn`, `hasActiveStay`).

## B.2 Folder structure

```
Frontendsystemhotel/
├── .github/workflows/deploy.yml   # CI/CD — GitHub Pages deploy
├── backend/                        # PHP MVC application
│   ├── index.php                   # Front controller (entry point)
│   ├── Core/                       # Framework core (Router, Controller, Model)
│   ├── Controllers/                # One controller per domain area
│   ├── Models/                     # One model per table
│   ├── Views/                      # PHP templates, grouped by controller
│   │   ├── layout/                 # header.php, footer.php (shared)
│   │   └── billing/                # mirrors Controllers/BillingController
│   ├── Helpers/                    # Stateless helper classes
│   ├── database/                   # .sql schema files
│   ├── assets/                     # backend-only css/img
│   └── config.php                  # environment config (never commit secrets!)
├── frontend/                       # Public website (static HTML/CSS/JS)
│   ├── *.html                      # one file per page
│   ├── css/style.css               # single stylesheet
│   ├── js/                         # api.js (HTTP layer), main.js (UI logic)
│   └── images/
├── tests/                          # all automated tests
│   ├── run_all.php                 # entry point
│   ├── lib/                        # TestRunner, Http, Db helpers
│   ├── unit/                       # isolated function tests (PHP + JS)
│   ├── integration/                # API tests over HTTP
│   └── system/                     # end-to-end flows
├── CODE_EXPLANATIONS/              # developer documentation (this file)
├── backups/                        # DB backups (SQL)
├── README.md                       # project overview + quick start
└── ASSIGNMENT_CRITERIA_P19.md      # grading checklist
```

**Rules:**
- A file lives only in the folder that matches its layer — no PHP inside `frontend/`,
  no HTML page inside `backend/Controllers/`.
- Views mirror Controllers: `Controllers/BillingController.php` → `Views/billing/`.
- Never create a "misc", "temp", "stuff" or "new" folder.

## B.3 Comments

| Where | What | Example |
|-------|------|---------|
| Every PHP file header | DocBlock: what the file/class does | see `ReservationController.php` |
| Every public method | DocBlock: purpose + `@param` + `@return` | `/** @param string $route e.g. "room_types/index" */` |
| Complex logic | `//` line comment saying **why**, not what | `// singularize trailing "s" so "room_types" → RoomTypeController` |
| Property | `/** @var type description */` | `/** @var array Allowed reservation status values */` |
| HTML/CSS/JS section | `<!-- ===== Section ===== -->` banner | `<!-- ===== ROOM GRID ===== -->` |

**Rules:**
- Comments explain **why**, not **what** — the code already says what.
- Delete commented-out code before opening the PR (Git remembers it).
- Update the comment in the same commit that changes the behaviour.
- Never leave `TODO: someone else do this` without an issue link.

## B.4 Clean Code principles

1. **Small functions** — an action method should fit on one screen (~30 lines).
   Extract repeated blocks into private helpers (`computeNights()`, `computeTotal()`).
2. **Meaningful names** — `computeTotal($nights, $rate)` beats `calc($a, $b)`.
3. **No magic numbers** — `define('MIN_NIGHTS', 1)` or `$this->taxRate`, never bare `1` scattered around.
4. **Early return / guard clauses** — fail fast:
   ```php
   if (!$this->isLoggedIn()) { $this->redirect('index.php?r=login/index'); return; }
   ```
5. **No dead code** — remove unused methods and the redundant
   `$this->loginRequired();` double-call in `ReservationController::__construct()`.
6. **One level of abstraction per method** — don't mix SQL, HTML and business
   rules in the same function.
7. **DRY output** — shared markup lives in `Views/layout/header.php` + `footer.php`.
8. **Handle errors explicitly** — never `@`-suppress errors; show/log a friendly message.

## B.5 SOLID principles

| Principle | Meaning in this project | Example |
|-----------|------------------------|---------|
| **S**ingle Responsibility | One class does one job | `Router` only dispatches; `Model` only talks to MySQL; `ReservationController` only handles reservations |
| **O**pen/Closed | Extend by adding files, not editing the core | New feature = new `XxxController.php`; `Core/Router.php` and `Core/Controller.php` stay untouched (the Router auto-resolves `?r=xxx/yyy`) |
| **L**iskov Substitution | Every controller behaves like `Core\Controller` | All controllers `extend Controller` and inherit `loginRequired()`, `roleRequired()`, `redirect()` — any can be dispatched interchangeably |
| **I**nterface Segregation | Small, focused helpers | `tests/lib/` splits `Http.php` (HTTP), `Db.php` (DB cleanup), `TestRunner.php` (assertions) instead of one god-class |
| **D**ependency Inversion | Depend on abstractions/config, not hard-coded values | DB credentials come from `config.php` constants (`DB_HOST`, `DB_USER`…), so swapping environments never touches business code |

**Practical checklist before a PR:**
- [ ] Does this class have only one reason to change? If not → split it.
- [ ] Did I modify `Core/` to add a feature? If yes → find another way.
- [ ] Am I hard-coding a value that belongs in `config.php`?

## B.6 DRY (Don't Repeat Yourself)

| Repeated thing | Where it is centralized |
|----------------|------------------------|
| Page header/footer | `backend/Views/layout/header.php`, `footer.php` |
| DB connection | `backend/Core/Model.php` (PDO singleton logic) |
| Tax/price math | `ReservationController::computeTotal()` — one implementation, reused by billing & reports |
| Night-count math | `ReservationController::computeNights()` — one implementation, unit-tested once |
| HTTP client calls | `frontend/js/api.js` (`API_BASE` defined once) |
| Test assertions | `tests/lib/TestRunner.php` |
| DB test cleanup | `tests/lib/Db.php` |
| Auth checks | `loginRequired()` / `roleRequired()` in `Core/Controller.php` |

**Rule of three:** the same logic appearing **3 times** must be extracted into a
shared function/class in the same PR.

---

# PART C — Phase 7: Testing

Full details and result tables live in **[TESTING.md](TESTING.md)**. Summary:

## C.1 Test levels (run in this order)

| Level | Scope | Runner | Files |
|-------|-------|--------|-------|
| 1. Unit | Single function, no HTTP/DB | `php tests/run_all.php --unit` | `tests/unit/unit_tests.php`, `tests/unit/frontend_calc.test.js` |
| 2. Integration | API endpoints together (HTTP + DB) | `php tests/run_all.php --integration` | `tests/integration/api_tests.php` |
| 3. System | Whole flow end-to-end (login → book → check-in → bill) | `php tests/run_all.php --system` | `tests/system/system_tests.php` |
| 4. UAT | Real user tries the system | Manual | Record evidence → `USER_MANUAL.md` |

## C.2 How to run

```bash
php tests/run_all.php               # everything (71 tests)
php tests/run_all.php --unit        # fast, no server needed
php tests/run_all.php --integration # needs Apache + MySQL running
php tests/run_all.php --system      # needs Apache + MySQL running
```

## C.3 Bug-fix loop (repeat until smooth)

```
Write code → run php tests/run_all.php → FAIL?
   → reproduce bug → fix → re-run ALL tests → still FAIL?
   → fix again → ALL green → commit "fix: ..." → open PR
```

- Every bug found gets a commit with prefix `fix:` and a line in `TESTING.md`
  (*Bug → Cause → Fix → Test that proves it*).
- Never mark a test "expected to fail" — a red test blocks the merge.

## C.4 UAT (Criterion 5, optional but recommended)

1. Give the login + `USER_MANUAL.md` to 1–2 people who did not write the code.
2. Ask them to complete: *register guest → reserve room → check in → check out → print receipt*.
3. Screenshot each step, note every confusion or error → file as `fix:` issues.
4. Repeat with new users until no blocking bug appears.

---

# PART D — Phase 8: Deployment

## D.1 Environment flow

```
Development Server          Testing Server             Production Server
(WAMP on your laptop)   →   (staging copy /        →   (GitHub Pages + 
localhost/...               teammate's machine)         InfinityFree hosting)
git: feature/* branch        git: develop branch        git: main branch
manual test                  automated tests (71)       live demo URL
```

| Server | Where | Git branch | Purpose |
|--------|-------|-----------|---------|
| Development | WAMP `C:\wamp64\www\...` | `feature/*` | Build & unit-test locally |
| Testing | Same WAMP + `php tests/run_all.php` full run, or a teammate's machine | `develop` | Verify merged features before release |
| Production | GitHub Pages (`frontend/`) + InfinityFree (`backend/`) | `main` (only via merged PR) | Public demo for users & teacher |

**Rule:** `main` is only updated by merging a reviewed Pull Request — never push
straight to `main`.

## D.2 CI/CD — GitHub Actions

File: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)

```
push to main ──▶ GitHub Actions runs automatically ──▶ uploads ./frontend
                                                   ──▶ deploys to GitHub Pages
                                                   ──▶ live at
       https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/
```

- The workflow publishes **only `frontend/`** — PHP backend is excluded
  (`_config.yml` + artifact path enforce this) because Pages cannot run PHP.
- Manual trigger is available via **Actions → Deploy to GitHub Pages → Run workflow**.

## D.3 Backend (PHP) deployment

Pages cannot host PHP → deploy `backend/` to free PHP+MySQL hosting
(InfinityFree recommended). Step-by-step: **[FREE_HOSTING.md](FREE_HOSTING.md)**
and client hand-over guide: **[CLIENT_DEPLOYMENT.md](CLIENT_DEPLOYMENT.md)**.

Checklist before going live:

- [ ] Change DB credentials in `backend/config.php` to the production database
- [ ] Import `backend/database/hotel_management_1_2.sql` into the hosting DB
- [ ] Point `frontend/js/api.js` `API_BASE` to the live backend URL
- [ ] Change default `admin / admin123` password
- [ ] Run the full test suite one last time: `php tests/run_all.php`
- [ ] Verify the live demo URL opens and login works

## D.4 Tool comparison (why these choices)

| Option | Used here? | Reason |
|--------|-----------|--------|
| **GitHub Pages** | ✅ | Free, automatic deploy from `main` — static frontend only |
| **GitHub Actions** | ✅ | Real CI/CD: deploy happens on every merge, no manual upload |
| **Free PHP hosting (InfinityFree)** | ✅ (planned) | Runs the PHP backend + MySQL at zero cost |
| Docker | ❌ not used | Classroom WAMP + free shared hosting don't support it; would add complexity with no benefit |
| GitLab CI | ❌ not used | Repository is on GitHub — GitHub Actions covers CI/CD natively |

---

## Quick reference — the 4 phases in one glance

| Phase | Status | Evidence |
|-------|--------|----------|
| 5. Development Setup | ✅ | This file §Part A, `README.md`, `CONTRIBUTING.md` |
| 6. Coding Standards | ✅ | This file §Part B (enforced in PR review) |
| 7. Testing | ✅ (+UAT ⬜) | `TESTING.md`, `tests/` — 71 automated tests passing |
| 8. Deployment | ✅ | `.github/workflows/deploy.yml`, live demo, `FREE_HOSTING.md` |
