# CONFIGURATION EXPLAINED
## Settings and Configuration

---

## 1. Database Configuration (config.php)

### Local Development (WAMP)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotelsystem');
```

### Production (InfinityFree)
```php
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_42865773');
define('DB_PASS', 'YIfvzeJH4d');
define('DB_NAME', 'if0_42865773_hotel_management_system');
```

---

## 2. Application Configuration

```php
define('APP_NAME', 'Hotel Management System');
```

---

## 3. Session Configuration

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

---

## 4. API Configuration (js/api.js)

```javascript
const API_BASE = 'https://arizu-arimato.free.nf/backend/index.php';
```

---

## 5. Deployment Configuration

### GitHub Actions (.github/workflows/deploy.yml)
```yaml
name: Deploy to GitHub Pages
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm run build
      - uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./frontend
```

---

## 6. InfinityFree Configuration

### FTP Settings
```
Host: ftpupload.net
User: if0_42865773
Password: YIfvzeJH4d
```

### MySQL Settings
```
Host: sql105.infinityfree.com
User: if0_42865773
Password: YIfvzeJH4d
Database: if0_42865773_hotel_management_system
```

### URLs
```
Frontend: https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/
Backend: https://arizu-arimato.free.nf/backend/
```

---

## 7. How to Switch Between Local and Production

### Step 1: Open config.php
```php
// For Local (WAMP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotelsystem');

// For Production (InfinityFree)
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_42865773');
define('DB_PASS', 'YIfvzeJH4d');
define('DB_NAME', 'if0_42865773_hotel_management_system');
```

### Step 2: Save File

### Step 3: Test
- Local: http://localhost/arizu-arimato/backend/index.php
- Production: https://arizu-arimato.free.nf/backend/index.php

---

## SUMMARY

| Setting | Local | Production |
|---------|-------|------------|
| DB Host | localhost | sql105.infinityfree.com |
| DB User | root | if0_42865773 |
| DB Pass | (empty) | YIfvzeJH4d |
| DB Name | hotelsystem | if0_42865773_hotel_management_system |
| API URL | http://localhost/... | https://arizu-arimato.free.nf/... |
