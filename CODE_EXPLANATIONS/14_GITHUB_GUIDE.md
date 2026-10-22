# 14. GITHUB GUIDE - .github Folder Explained

## Overview

The `.github` folder is a special folder that GitHub recognizes automatically. It's used for **repository configuration** and **automation**.

---

## Your .github Folder Structure

```
.github/
└── workflows/
    └── deploy.yml
```

---

## What is GitHub Actions?

GitHub Actions is a **CI/CD (Continuous Integration/Continuous Deployment)** tool built into GitHub. It lets you automate tasks when you push code.

---

## Your deploy.yml Explained

### File Location
`.github/workflows/deploy.yml`

### What It Does
Automatically deploys your **frontend** to **GitHub Pages** when you push to the `main` branch.

### Line-by-Line Explanation

```yaml
name: Deploy to GitHub Pages
```
- Name of the workflow (shows in GitHub Actions tab)

---

```yaml
on:
  push:
    branches: [ main ]
  workflow_dispatch:
```
- **Trigger**: When you push to `main` branch
- **workflow_dispatch**: Also allows manual trigger from GitHub UI

---

```yaml
permissions:
  contents: read
  pages: write
  id-token: write
```
- **contents: read**: Can read repository files
- **pages: write**: Can deploy to GitHub Pages
- **id-token: write**: Can authenticate for deployment

---

```yaml
concurrency:
  group: "pages"
  cancel-in-progress: false
```
- Only one deployment runs at a time
- Doesn't cancel ongoing deployments

---

```yaml
jobs:
  deploy:
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    runs-on: ubuntu-latest
```
- **Job name**: deploy
- **Environment**: github-pages
- **Runs on**: Ubuntu Linux (GitHub's server)

---

```yaml
    steps:
      - name: Checkout
        uses: actions/checkout@v4
```
- **Step 1**: Downloads your repository code

---

```yaml
      - name: Setup Pages
        uses: actions/configure-pages@v4
```
- **Step 2**: Configures GitHub Pages settings

---

```yaml
      - name: Upload artifact
        uses: actions/upload-pages-artifact@v3
        with:
          path: './frontend'
```
- **Step 3**: Uploads only the `frontend` folder
- This means only your frontend files are deployed
- Backend (PHP) is NOT deployed here

---

```yaml
      - name: Deploy to GitHub Pages
        id: deployment
        uses: actions/deploy-pages@v4
```
- **Step 4**: Deploys the uploaded files to GitHub Pages

---

## How It Works (Visual Flow)

```
┌─────────────────────────────────────────────────────────────┐
│                    YOUR COMPUTER                            │
│                                                             │
│  1. You write/edit code                                     │
│  2. You commit changes                                      │
│  3. You push to GitHub                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    GITHUB SERVER                            │
│                                                             │
│  4. GitHub receives your push                               │
│  5. GitHub Actions triggers deploy.yml                      │
│  6. Workflow starts running                                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                 GITHUB ACTIONS SERVER                       │
│                                                             │
│  7. Downloads your code                                     │
│  8. Uploads ./frontend folder                               │
│  9. Deploys to GitHub Pages                                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  GITHUB PAGES                               │
│                                                             │
│  10. Your frontend is now live!                             │
│  11. URL: https://kompheakkeo1111-creator.github.io/...    │
└─────────────────────────────────────────────────────────────┘
```

---

## Common .github Files

| File/Folder | Purpose | You Have It? |
|-------------|---------|--------------|
| `README.md` | Project description | ✅ Yes |
| `LICENSE` | Usage rights | ❌ No |
| `.gitignore` | Files to ignore | ❌ No |
| `CONTRIBUTING.md` | Contribution guidelines | ❌ No |
| `CODE_OF_CONDUCT.md` | Community rules | ❌ No |
| `SECURITY.md` | Security reporting | ❌ No |
| `ISSUE_TEMPLATE/` | Bug/feature templates | ❌ No |
| `PULL_REQUEST_TEMPLATE.md` | PR template | ❌ No |
| `workflows/` | GitHub Actions | ✅ Yes |

---

## Why .github Folder Matters

### For Your Project

| Situation | Need .github? |
|-----------|---------------|
| Auto-deploy frontend | ✅ Yes (you have this) |
| School project | ⚪ Optional |
| Open source project | ✅ Yes |
| Team collaboration | ✅ Yes |
| CI/CD automation | ✅ Yes |

### Benefits

1. **Automation**: Code auto-deploys when you push
2. **Consistency**: Same deployment process every time
3. **No Manual Work**: Just push code, GitHub handles the rest
4. **Version Control**: Every deployment is tracked

---

## How to Use GitHub Actions

### View Your Workflow

1. Go to your repository on GitHub
2. Click **Actions** tab
3. You'll see "Deploy to GitHub Pages" workflow
4. Click on a run to see details

### Manual Trigger

1. Go to **Actions** tab
2. Click **Deploy to GitHub Pages**
3. Click **Run workflow**
4. Select `main` branch
5. Click **Run workflow**

### View Deployment URL

1. Go to **Settings** tab
2. Click **Pages** in left sidebar
3. You'll see your live URL

---

## Your Deployment URLs

| Component | URL | Hosting |
|-----------|-----|---------|
| **Frontend** | https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/ | GitHub Pages |
| **Backend** | https://arizu-arimato.free.nf/backend/index.php | InfinityFree |

---

## Adding More Workflows

You can create additional workflows in `.github/workflows/`:

### Example: Run Tests

```yaml
name: Run Tests

on:
  push:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run PHP Syntax Check
        run: php -l backend/**/*.php
```

### Example: Auto-Label Issues

```yaml
name: Label Issues

on:
  issues:
    types: [opened]

jobs:
  label:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/labeler@v4
```

---

## Summary

| Item | Description |
|------|-------------|
| `.github` folder | Special folder GitHub recognizes |
| `workflows/` | Contains automation scripts |
| `deploy.yml` | Auto-deploys frontend to GitHub Pages |
| **Trigger** | Push to `main` branch |
| **Result** | Frontend live at GitHub Pages URL |
| **Backend** | Separate hosting (InfinityFree) |

---

## Quick Reference

| Command | What It Does |
|---------|--------------|
| `git push origin main` | Triggers auto-deployment |
| View Actions | GitHub repo → Actions tab |
| Manual deploy | Actions → Run workflow |
| View live site | Settings → Pages |

---

**Your .github folder is working correctly and auto-deploys your frontend!**
