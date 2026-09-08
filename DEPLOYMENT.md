# Deployment Guide

## Split Deployment: GitHub Pages + PHP Backend

This guide deploys the frontend to GitHub Pages and the backend to a PHP hosting provider.

---

## Step 1: Deploy Backend to PHP Hosting

### Option A: InfinityFree (Free)
1. Go to [InfinityFree](https://infinityfree.net)
2. Create an account and hosting
3. Note your:
   - FTP hostname (e.g., `ftpupload.net`)
   - FTP username
   - Database name (e.g., `if0_42865773_hotel_management_system`)
   - Database username
   - Database password

### Option B: 000webhost (Free)
1. Go to [000webhost.com](https://000webhost.com)
2. Create an account and hosting
3. Note your credentials

### Backend Setup

1. Upload the entire `backend/` folder to your hosting via FTP
2. Update `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
```

3. Visit `https://yourdomain.com/backend/index.php` to run the installer
4. Create an admin account when prompted

---

## Step 2: Deploy Frontend to GitHub Pages

### Create GitHub Repository
1. Create a new repository on GitHub: `Frontendsystemhotel`
2. Push your `frontend/` folder contents to this repository

### Enable GitHub Pages
1. Go to repository Settings > Pages
2. Select "main" branch
3. Your site will be at: `https://yourusername.github.io/Frontendsystemhotel/`

### Update API URL
Edit `frontend/js/api.js` and replace the API_BASE URL:

```javascript
const API_BASE = 'https://yourdomain.com/backend/index.php';
```

### Update CORS
Edit `backend/cors.php` and add your GitHub Pages URL:

```php
$allowedOrigins = [
    'https://yourusername.github.io',
];
```

---

## Step 3: Test Everything

1. Visit your GitHub Pages site
2. Test the contact form
3. Test room booking
4. Check browser console for CORS errors

---

## Troubleshooting

### CORS Errors
- Ensure your GitHub Pages URL is in `backend/cors.php`
- Ensure HTTPS is enabled on your PHP hosting

### Database Connection Failed
- Double-check credentials in `config.php`
- Ensure database user has full permissions

### Images Not Loading
- Check image paths in `frontend/images/`
- Ensure images are committed to the repository

---

## File Structure After Deployment

```
GitHub Pages (https://yourusername.github.io/Frontendsystemhotel/)
├── index.html
├── about.html
├── contact.html
├── gallery.html
├── rooms.html
├── css/
├── js/
│   └── api.js (points to your PHP backend)
└── images/

PHP Hosting (https://yourdomain.com/backend/)
├── index.php
├── config.php
├── cors.php
├── Controllers/
├── Models/
├── Views/
└── database/
```
