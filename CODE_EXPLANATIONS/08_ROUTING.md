# ROUTING EXPLAINED
## How URLs Work

---

## Basic URL Structure

```
https://arizu-arimato.free.nf/backend/index.php?r=CONTROLLER/ACTION
                                               └────────────────────┘
                                                        │
                                                What page to show
```

---

## How Routing Works

### Step 1: User Enters URL
```
https://arizu-arimato.free.nf/backend/index.php?r=dashboard/index
```

### Step 2: Router Reads Route
```
Route: "dashboard/index"
```

### Step 3: Split Into Parts
```
Controller: "dashboard"
Action: "index"
```

### Step 4: Convert to Class Name
```
"dashboard" → "Dashboard" → "DashboardController"
```

### Step 5: Convert Action to Method
```
"index" → "indexAction()"
```

### Step 6: Call Method
```
DashboardController->indexAction()
```

---

## URL Examples

### Admin Panel Pages

| URL | Controller | Action | What It Shows |
|-----|------------|--------|---------------|
| ?r=dashboard/index | DashboardController | indexAction | Dashboard |
| ?r=rooms/index | RoomController | indexAction | Room Management |
| ?r=room_types/index | RoomTypeController | indexAction | Room Types |
| ?r=guests/index | GuestController | indexAction | Guest Management |
| ?r=reservations/index | ReservationController | indexAction | Reservations |
| ?r=checkin/index | CheckInController | indexAction | Check-in Page |
| ?r=checkout/index | CheckOutController | indexAction | Check-out Page |
| ?r=billing/index | BillingController | indexAction | Billing |
| ?r=reports/index | ReportController | indexAction | Reports |
| ?r=housekeeping/index | HousekeepingController | indexAction | Housekeeping |
| ?r=maintenance/index | MaintenanceController | indexAction | Maintenance |
| ?r=users/index | UserController | indexAction | Staff Users |
| ?r=settings/index | SettingController | indexAction | Settings |
| ?r=notifications/index | NotificationController | indexAction | Notifications |
| ?r=login/index | LoginController | indexAction | Login Page |
| ?r=logout/index | LogoutController | indexAction | Logout |
| ?r=setup/index | SetupController | indexAction | Database Setup |

---

### API Endpoints

| URL | Method | What It Does |
|-----|--------|--------------|
| ?r=api/rooms | GET | Get room types |
| ?r=api/rooms/view&id=1 | GET | Get single room |
| ?r=api/availableRooms&check_in=...&check_out=... | GET | Search rooms |
| ?r=api/settings | GET | Get settings |
| ?r=api/reservation | POST | Create booking |
| ?r=api/contact | POST | Submit contact form |
| ?r=api/addRoom | POST | Add room type |
| ?r=api/editRoom | POST | Edit room type |
| ?r=api/deleteRoom | POST | Delete room type |

---

## Special Cases

### Default Route
```
If no route specified: ?r=dashboard/index
```

### Case Sensitivity
```
?r=checkin/index → CheckInController
?r=Checkin/index → CheckInController
?r=CHECKIN/index → CheckInController
```

### Trailing Slash
```
?r=dashboard/index/ → DashboardController
?r=dashboard/index → DashboardController
```

---

## SUMMARY

| Concept | Example |
|---------|---------|
| Base URL | index.php?r= |
| Controller | dashboard |
| Action | index |
| Full Route | dashboard/index |
| Class Name | DashboardController |
| Method Name | indexAction() |
