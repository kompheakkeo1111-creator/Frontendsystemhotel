# Arizu Arimato - Hotel Management System

## Live Demo
**Frontend**: https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/

## Quick Start (Local Setup)

### Requirements
- [WAMP Server](http://www.wampserver.com/) or [XAMPP](https://www.apachefriends.org/)
- Git

### Installation

1. Clone the repository
```bash
git clone https://github.com/kompheakkeo1111-creator/Frontendsystemhotel.git
```

2. Open WAMP/XAMPP and start Apache + MySQL

3. Open browser and go to:
```
http://localhost/Frontendsystemhotel/backend/
```

4. The installer will create the database automatically

5. Login with:
   - Username: `admin`
   - Password: `admin123`

## Pages

### Public Website
| Page | URL |
|------|-----|
| Home | http://localhost/Frontendsystemhotel/frontend/index.html |
| Rooms | http://localhost/Frontendsystemhotel/frontend/rooms.html |
| About | http://localhost/Frontendsystemhotel/frontend/about.html |
| Contact | http://localhost/Frontendsystemhotel/frontend/contact.html |
| Gallery | http://localhost/Frontendsystemhotel/frontend/gallery.html |

### Admin Panel
| Page | URL |
|------|-----|
| Login | http://localhost/Frontendsystemhotel/backend/ |
| Dashboard | http://localhost/Frontendsystemhotel/backend/index.php?r=dashboard/index |
| Rooms | http://localhost/Frontendsystemhotel/backend/index.php?r=rooms/index |
| Reservations | http://localhost/Frontendsystemhotel/backend/index.php?r=reservations/index |
| Check-in | http://localhost/Frontendsystemhotel/backend/index.php?r=checkin/index |
| Check-out | http://localhost/Frontendsystemhotel/backend/index.php?r=checkout/index |
| Billing | http://localhost/Frontendsystemhotel/backend/index.php?r=billing/index |
| Reports | http://localhost/Frontendsystemhotel/backend/index.php?r=reports/index |

## Features

### Public Website
- View room types and prices
- Search available rooms by date
- Make reservations online
- Contact form

### Admin Panel
- Dashboard with real-time stats
- Room management (CRUD)
- Guest management
- Reservation management
- Check-in / Check-out
- Payment processing
- Billing and receipts
- Revenue reports
- User management (role-based)
- Database backup/restore

## Default Staff Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |

## User Roles

| Role | Access |
|------|--------|
| Administrator | Full access |
| Manager | Most features except user management |
| Receptionist | Rooms, guests, reservations, check-in/out |
| Housekeeping | Rooms, housekeeping, maintenance |
| Accountant | Billing, reports |

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Font Awesome
- **Backend**: PHP 7.4+, MySQL, PDO
- **Admin Panel**: Bootstrap 5, Chart.js
- **Architecture**: Custom MVC Framework
