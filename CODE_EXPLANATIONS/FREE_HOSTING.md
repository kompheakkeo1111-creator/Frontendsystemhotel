# Free Hosting Setup Guide
## Deploy Your Hotel System for Free

---

## OPTION 1: InfinityFree (Recommended)

### Step 1: Create Account
1. Go to: https://infinityfree.net
2. Click "Sign Up"
3. Fill in email and password
4. Verify your email

### Step 2: Create Hosting
1. Login to InfinityFree
2. Click "Create Account"
3. Choose subdomain: `arizuarimato.epizy.com` (or your choice)
4. Select PHP 7.4+ and MySQL 5.7
5. Click "Create Account"

### Step 3: Upload Files
1. Go to Control Panel → File Manager
2. Navigate to `htdocs` folder
3. Upload `backend` folder
4. Upload `frontend` folder

### Step 4: Create Database
1. Go to Control Panel → MySQL Databases
2. Create new database
3. Note down:
   - Database name: `epiz_42865773_hotel_management`
   - Database username: `epiz_42865773`
   - Database password: (your password)

### Step 5: Configure
Edit `backend/config.php`:
```php
define('DB_HOST', 'sql.infinityfree.com');
define('DB_USER', 'epiz_42865773');
define('DB_PASS', 'your_password');
define('DB_NAME', 'epiz_42865773_hotel_management');
```

Edit `frontend/js/api.js`:
```javascript
const API_BASE = 'https://arizuarimato.epizy.com/backend/index.php';
```

### Step 6: Install
Visit: `https://arizuarimato.epizy.com/backend/`

---

## OPTION 2: 000webhost

### Step 1: Create Account
1. Go to: https://www.000webhost.com
2. Click "Get Free Hosting"
3. Fill in details and verify email

### Step 2: Create Website
1. Login to 000webhost
2. Click "Create Website"
3. Choose subdomain: `arizuarimato.000webhostapp.com`
4. Select PHP 7.4

### Step 3: Upload Files
1. Go to File Manager or use FTP
2. Upload `backend` and `frontend` folders to `public_html`

### Step 4: Create Database
1. Go to Databases → MySQL
2. Create new database
3. Note credentials

### Step 5: Configure & Install
Same as InfinityFree steps above.

---

## OPTION 3: GitHub Pages + Free Backend

### Frontend (GitHub Pages):
Already deployed at:
`https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/`

### Backend (InfinityFree/000webhost):
1. Deploy backend to free hosting
2. Update `frontend/js/api.js` with backend URL

---

## FREE HOSTING COMPARISON

| Feature | InfinityFree | 000webhost |
|---------|--------------|------------|
| Storage | 5 GB | 300 MB |
| Bandwidth | Unlimited | 3 GB/month |
| PHP | 7.4 | 7.4 |
| MySQL | Yes | Yes |
| Subdomain | Yes | Yes |
| Custom Domain | Yes | Yes |
| Uptime | 99.9% | 99.9% |

---

## IMPORTANT NOTES

### Limitations of Free Hosting:
- Slow loading times
- Ads may be displayed
- Limited resources
- May sleep after inactivity
- Not for production use

### For Production:
Upgrade to paid hosting ($3-5/month) for:
- Better performance
- No ads
- Custom domain
- SSL certificate
- Better support

---

## TROUBLESHOOTING

### "Database Connection Failed"
- Check database credentials in config.php
- Ensure database exists

### "500 Internal Server Error"
- Check PHP version (7.4+)
- Ensure .htaccess uploaded

### "File Not Found"
- Check file paths
- Ensure files uploaded to correct folder

### Images Not Loading
- Check image paths in code
- Ensure images uploaded to `frontend/images/`
