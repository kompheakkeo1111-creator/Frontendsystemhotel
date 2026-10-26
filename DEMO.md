# Demo Branch — Placeholder Version

You are on the **`demo`** branch. This is a safe, fake copy of the project meant
for team members to pull, run and develop against **without touching real
credentials or real records**.

> **This branch contains no real data.** Everything you see is a placeholder.

---

## What is faked on this branch

| Item | Real value | Demo value |
|------|-----------|------------|
| Database host | `sql102.infinityfree.com` (live server) | `127.0.0.1` (your own machine) |
| Database user | `if0_43261235` | `root` |
| Database password | *(real password)* | *(empty — local only)* |
| Database name | `if0_43261235_hotel_management` | `hotel_management_demo` |
| Hotel name | *(real hotel name)* | `Demo Hotel` |
| Hotel email | *(real inbox)* | `info@demohotel.example` |
| Hotel phone | *(real number)* | `+855 12 345 678` |
| Seed guests | — | `John Smith`, `Maria Garcia`, `Chen Wei`, `Sarah Johnson` |
| `backups/*.sql` | real production dumps | **removed entirely** |

The real branding, credentials and backups live on the **`develop`** and
**`main`** branches only.

---

## Run the demo locally

### 1. Clone and switch to this branch

```bash
git clone https://github.com/kompheakkeo1111-creator/Frontendsystemhotel.git
cd Frontendsystemhotel
git checkout demo
```

Already have it cloned?

```bash
git fetch origin
git checkout demo
git pull origin demo
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

Default demo login:

| Username | Password |
|----------|----------|
| `admin` | `admin123` |

---

## Daily workflow from this branch

**Never commit your work directly to `demo`.** Create your own branch:

```bash
git checkout demo
git pull origin demo

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
| `develop` | Integration branch, merged features land here | Yes |
| `main` | Finished, deployed code | Yes |
| `feature/*` | Your own task | Whatever you add |

---

## Important warnings

1. **Do not use the demo database credentials on a live server** — they are
   deliberately non-working placeholders.
2. **Do not copy real guest records into this branch.** Use the sample rows
   already provided.
3. **Do not force push** (`git push --force`) — it can erase teammates' work.
4. If you need the real configuration for local development, create a
   `backend/config_local.php` file. It is listed in `.gitignore` and is never
   pushed to GitHub.

---

## If something looks wrong

```bash
# Get back to a clean demo state
git checkout demo
git fetch origin
git reset --hard origin/demo
```

`git reset --hard` **discards your uncommitted local changes** — commit or
`git stash` first if you want to keep them.
