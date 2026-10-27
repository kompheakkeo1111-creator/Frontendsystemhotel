# Safe Placeholders & Demo Branch Guide

This repository is written so that **nothing sensitive is ever pushed to
GitHub**: database credentials are local-only placeholders, the SQL dumps
contain sample records, and production backups are not stored in the repo.

> **No real guest, employee or customer data exists in this repository.**

---

## What is a placeholder in this repo

| Item | Real value (never committed) | Value in this repo |
|------|------------------------------|--------------------|
| Database host | your live server (e.g. InfinityFree) | `127.0.0.1` (your own machine) |
| Database user | live DB user | `root` |
| Database password | *(real password)* | *(empty — local only)* |
| Database name | live DB name | `hotel_management_demo` |
| Hotel phone | *(real number)* | `+855 12 345 678` |
| Seed guests | — | `John Smith`, `Maria Garcia`, `Chen Wei`, `Sarah Johnson` |
| `backups/*.sql` | real production dumps | **not in the repository** |

The hotel **name** (`Arizu Arimato`) and public email are the project's real
branding — they are safe to publish. All *credentials* and *records* are
placeholders.

Real server credentials, if ever needed, go in `backend/config_local.php`
which is listed in `.gitignore` and is never pushed to GitHub.

### The `demo` branch

A separate **`demo`** branch exists as a fully-faked starter copy (placeholder
branding *and* credentials) for team members to pull and develop against:

```bash
git fetch origin
git checkout demo
git pull origin demo
```

---

## Run the project locally

### 1. Clone

```bash
git clone https://github.com/kompheakkeo1111-creator/Frontendsystemhotel.git
cd Frontendsystemhotel
```

### 2. Start your database

Start Apache + MySQL in WAMP/XAMPP, then import the sample schema:

```bash
mysql -u root hotel_management_demo < hotel_management_1_2.sql
```

Or open `http://localhost/phpmyadmin` → **Import** → choose
`hotel_management_1_2.sql` → **Go**.

### 3. Open the app

```
http://localhost/Frontendsystemhotel/frontend/index.html   (public site)
http://localhost/Frontendsystemhotel/backend/              (admin panel)
```

Default login:

| Username | Password |
|----------|----------|
| `admin` | `admin123` |

---

## Daily workflow

**Never commit your work directly to `main` or `develop`.** Create your own
branch:

```bash
git checkout develop
git pull origin develop

git checkout -b feature/my-task     # your own branch

git add .
git commit -m "feat: describe your change"
git push -u origin feature/my-task
```

Then open a **Pull Request** targeting `develop` so the owner can review it.

See **[CONTRIBUTING.md](CONTRIBUTING.md)** for the full 4-member workflow.

---

## Branch map

| Branch | Purpose | Contains real data? |
|--------|---------|--------------------|
| `demo` | Safe starter copy for team members | No — placeholders only |
| `develop` | Integration branch, merged features land here | No — placeholders only |
| `main` | Finished, deployed code | No — placeholders only |
| `feature/*` | Your own task | Whatever you add |

---

## Important warnings

1. **Do not use these database credentials on a live server** — they are
   deliberately non-working placeholders.
2. **Do not commit real guest records.** Use the sample rows already provided.
3. **Do not force push** (`git push --force`) — it can erase teammates' work.
4. If you need the real configuration for local development, create a
   `backend/config_local.php` file. It is listed in `.gitignore` and is never
   pushed to GitHub.

---

## If something looks wrong

```bash
# Get back to the last pushed state of your branch
git fetch origin
git reset --hard origin/<your-branch>
```

`git reset --hard` **discards your uncommitted local changes** — commit or
`git stash` first if you want to keep them.
