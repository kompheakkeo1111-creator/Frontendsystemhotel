# Contributing Guide — Demo Hotel

Team size: **4 members**. Follow this workflow so nobody overwrites anyone's work.

---

## 1. Branch Model

| Branch | Purpose | Who can push |
|--------|---------|--------------|
| `main` | Finished, working code (live) | Maintainer only (via merged PR) |
| `develop` | Where finished features are combined | Via merged PR |
| `feature/*` | Your own work in progress | You, freely |

```
main ────────────────────────────────● release
        \                         /
         develop ────●───────●───
           │         ▲       ▲
           ├─ feature/rooms
           ├─ feature/billing
           └─ feature/report
```

**Rule:** never work directly on `main` or `develop`. Always create your own `feature/` branch.

---

## 2. One-Time Setup (each member)

```bash
git config --global user.name  "Your Full Name"
git config --global user.email "yourgmail@gmail.com"
```

> Your email **must be the Gmail address attached to your GitHub account**, otherwise
> your commits will not be counted as yours.

Clone the repository:

```bash
cd C:\wamp64\www
git clone https://github.com/kompheakkeo1111-creator/Frontendsystemhotel.git
cd Frontendsystemhotel
```

---

## 3. Daily Workflow

### Start of day — always sync first

```bash
git checkout develop
git pull origin develop
```

### Start a new task — create your branch

```bash
git checkout develop
git checkout -b feature/your-task-name
```

Examples: `feature/room-filter`, `feature/payment-receipt`

### While working

```bash
git add .
git commit -m "rooms: add date filter"
git push -u origin feature/your-task-name
```

Repeat `add` / `commit` / `push` as often as you like — small commits are better.

### Finish — open a Pull Request

1. Push your branch (command above)
2. Open GitHub → your repo → **Pull requests** → **New pull request**
3. Base: `develop` ← Compare: `feature/your-task-name`
4. Click **Create pull request**
5. Reviewer merges it, then **delete the branch**

### After your PR is merged

```bash
git checkout develop
git pull origin develop
git branch -d feature/your-task-name
```

---

## 4. Commit Message Style

```
<type>: <short description>
```

| Type | Use for |
|------|---------|
| `feat:` | new feature |
| `fix:` | bug fix |
| `docs:` | documentation only |
| `style:` | formatting / CSS, no logic change |
| `refactor:` | code cleanup, no behaviour change |
| `test:` | tests |

Examples:

```
feat: add room availability search
fix: booking form not saving to database
docs: update README setup steps
```

---

## 5. Rules to Avoid Conflicts

1. **`git pull` before you start** — never write code on an old copy.
2. **One feature = one branch.** Don't mix unrelated changes.
3. **Commit small and often.** A 500-line commit is impossible to merge.
4. **Don't commit** `backups/`, `*.sql` dumps, `config_local.php`, passwords, or images over 1 MB.
5. **Talk to the team** if you must edit a file someone else is editing.
6. **Never force push** — `git push --force` can erase your teammates' work.

---

## 6. If You Get a Conflict

```bash
git pull origin develop
# Git says: CONFLICT in some/file.php
```

1. Open the conflicted file — you'll see `<<<<<<< HEAD` and `>>>>>>> feature/...`
2. Keep the correct code and delete those marker lines
3. Then:

```bash
git add some/file.php
git commit -m "resolve merge conflict in some/file.php"
git push
```

Still stuck? Ask the team before guessing.

---

## 7. Useful Commands

| Command | What it does |
|---------|--------------|
| `git status` | what changed |
| `git log --oneline -10` | last 10 commits |
| `git diff` | see your changes |
| `git stash` | temporarily hide your changes |
| `git stash pop` | bring them back |
| `git branch -a` | list all branches |
| `git clone <url>` | copy the repo to your PC |

---

## 8. Local Development

```bash
# Start WampServer (run as administrator)
# Then open:
http://localhost/Frontendsystemhotel/frontend/index.html     # public site
http://localhost/Frontendsystemhotel/backend/                # admin panel
```

Default admin login: `admin` / `admin123`
