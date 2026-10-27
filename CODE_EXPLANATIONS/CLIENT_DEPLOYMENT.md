# Client Deployment Guide
## Step-by-Step Setup for Your Client

---

## STEP 1: Get Domain & Hosting

### Recommended Hosting Providers:
| Provider | Price | PHP | MySQL | Link |
|----------|-------|-----|-------|------|
| Hostinger | $2.99/mo | Yes | Yes | hostinger.com |
| Bluehost | $2.95/mo | Yes | Yes | bluehost.com |
| InfinityFree | Free | Yes | Yes | infinityfree.net |
| 000webhost | Free | Yes | Yes | 000webhost.com |

### What to Buy:
- **Domain name**: e.g., `arizuarimato.com` (~$10-15/year)
- **Hosting plan**: Shared hosting with PHP + MySQL support

### After Purchase:
You'll receive:
- FTP hostname (e.g., `ftpupload.net`)
- FTP username
- FTP password
- cPanel login URL
- Database name
- Database username
- Database password

---

## STEP 2: Upload Files via FTP

### Install FileZilla:
Download from: https://filezilla-project.org/

### Connect to Hosting:
1. Open FileZilla
2. Enter Host, Username, Password from hosting provider
3. Click "Quickconnect"

### Upload Files:
1. Left side = your computer, Right side = hosting server
2. Navigate to `public_html` folder on the right
3. Upload entire `backend` folder
4. Upload entire `frontend` folder

Your structure should look like:
```
public_html/
├── backend/
│   ├── index.php
│   ├── config.php
│   ├── Controllers/
│   └── ...
└── frontend/
    ├── index.html
    ├── rooms.html
    └── ...
```

---

## STEP 3: Create Database

### In cPanel:
1. Login to cPanel (e.g., `yourdomain.com/cpanel`)
2. Find "MySQL Databases" or "Database Wizard"
3. Create new database: `hotel_management`
4. Create database user with strong password
5. Add user to database with "ALL PRIVILEGES"

### Note Down:
- Database name: `username_hotel_management`
- Database username: `username_dbuser`
- Database password: `your_strong_password`

---

## STEP 4: Configure the System

### Edit config.php:
Open `backend/config.php` and update:

```php
<?php
// Database configuration - UPDATE THESE
define('DB_HOST', 'localhost');           // Usually localhost
define('DB_USER', 'username_dbuser');     // Your database username
define('DB_PASS', 'your_strong_password'); // Your database password
define('DB_NAME', 'username_hotel_management'); // Your database name
```

### Edit api.js (for frontend):
Update the API URL in `frontend/js/api.js`:

```javascript
// Change this to your domain
const API_BASE = 'https://yourdomain.com/backend/index.php';
```

### Edit cors.php (for cross-origin):
Update `backend/cors.php` with your domain:

```php
$allowedOrigins = [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
];
```

---

## STEP 5: Run Installer

1. Open browser
2. Go to: `https://yourdomain.com/backend/`
3. You'll see the installer page
4. Click "Install Database"
5. System creates all tables automatically
6. You'll see "Installation Complete"

---

## STEP 6: Login & Configure

### Default Login:
- URL: `https://yourdomain.com/backend/`
- Username: `admin`
- Password: `admin123`

### IMPORTANT: Change Admin Password Immediately

1. Login to admin panel
2. Go to Users section
3. Edit admin account
4. Change password to something secure

### Configure Hotel Settings:
1. Go to Settings
2. Update:
   - Hotel name
   - Hotel address
   - Phone number
   - Email
   - Currency
   - Tax rate

---

## STEP 7: Test Everything

### Test Public Website:
- Visit `https://yourdomain.com/frontend/`
- Check all pages load
- Test room search
- Test booking form

### Test Admin Panel:
- Login with new password
- Check dashboard loads
- Add a test room
- Add a test reservation
- Test check-in/check-out

---

## STEP 8: Security Hardening

### Change Database Password:
Use a strong password with:
- 12+ characters
- Uppercase + lowercase
- Numbers
- Special characters

### Delete Installer:
After installation, you can delete the setup folder:
```
backend/Controllers/SetupController.php
backend/Views/setup/
```

### Backup Regularly:
1. Go to Settings in admin panel
2. Click "Backup Database"
3. Download the .sql file
4. Store it safely offline

---

## STEP 9: Handover to Client

### Provide to Client:

**1. Website URLs:**
- Public website: `https://yourdomain.com/frontend/`
- Admin panel: `https://yourdomain.com/backend/`

**2. Login Credentials:**
- Admin username: `admin`
- Admin password: [the new password you set]

**3. Training Guide:**
- How to add rooms
- How to manage reservations
- How to check-in/check-out guests
- How to process payments
- How to run reports
- How to backup database

**4. Support Information:**
- Your contact info for technical support
- Hosting provider support contact

---

## QUICK REFERENCE CARD (Print for Client)

```
╔══════════════════════════════════════════════════════════════╗
║              HOTEL MANAGEMENT SYSTEM - QUICK REFERENCE       ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  WEBSITE:                                                    ║
║  Public:  https://yourdomain.com/frontend/                   ║
║  Admin:   https://yourdomain.com/backend/                    ║
║                                                              ║
║  LOGIN:                                                      ║
║  Username: admin                                             ║
║  Password: [secure password]                                 ║
║                                                              ║
║  COMMON TASKS:                                               ║
║  1. Add Room: Rooms → Add New                                ║
║  2. Add Reservation: Reservations → Add New                  ║
║  3. Check-in Guest: Check-in → Check In                      ║
║  4. Check-out Guest: Check-out → Payment & Check-out         ║
║  5. View Reports: Reports → Generate                         ║
║  6. Backup: Settings → Backup Database                       ║
║                                                              ║
║  SUPPORT:                                                    ║
║  Phone: [Your phone]                                         ║
║  Email: [Your email]                                         ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

---

## TROUBLESHOOTING

| Problem | Solution |
|---------|----------|
| Page not found (404) | Check file paths, ensure .htaccess uploaded |
| Database connection failed | Verify config.php credentials |
| Blank white page | Check PHP version (7.4+), enable error reporting |
| Images not showing | Check folder permissions (755) |
| API not working | Update api.js with correct domain URL |

---

## COSTS SUMMARY

| Item | Cost |
|------|------|
| Domain name | ~$10-15/year |
| Hosting (basic) | ~$3-5/month |
| SSL certificate | Free with hosting (Let's Encrypt) |
| **Total** | **~$50-70/year** |
