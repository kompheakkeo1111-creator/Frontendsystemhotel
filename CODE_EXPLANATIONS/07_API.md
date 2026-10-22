# API EXPLAINED
## What Each Endpoint Does

---

## Base URL
```
https://arizu-arimato.free.nf/backend/index.php
```

---

## Public API (No Login Required)

### 1. GET /api/rooms

**Purpose:** Get all room types

**Request:**
```
GET https://arizu-arimato.free.nf/backend/index.php?r=api/rooms
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type_name": "Sweet Room",
            "description": "Comfortable standard room",
            "price_per_night": 50.00,
            "capacity": 2,
            "available_rooms": 3,
            "image": "images/room-deluxe.jpg"
        }
    ]
}
```

---

### 2. GET /api/rooms/view&id=X

**Purpose:** Get single room type

**Request:**
```
GET https://arizu-arimato.free.nf/backend/index.php?r=api/rooms/view&id=1
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "type_name": "Sweet Room",
        "rooms": [...]
    }
}
```

---

### 3. GET /api/availableRooms

**Purpose:** Search available rooms for dates

**Request:**
```
GET https://arizu-arimato.free.nf/backend/index.php?r=api/availableRooms&check_in=2026-09-15&check_out=2026-09-18&guests=2
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "room_number": "101",
            "type_name": "Sweet Room",
            "price_per_night": 50.00,
            "capacity": 2,
            "image": "images/room-deluxe.jpg"
        }
    ]
}
```

---

### 4. POST /api/reservation

**Purpose:** Create a booking

**Request:**
```
POST https://arizu-arimato.free.nf/backend/index.php?r=api/reservation
Content-Type: application/json

{
    "room_type_id": 2,
    "full_name": "John Smith",
    "email": "john@email.com",
    "phone": "1234567890",
    "guests": 2,
    "check_in_date": "2026-09-15",
    "check_out_date": "2026-09-18",
    "special_requests": "Extra pillows"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reservation created successfully",
    "data": {
        "reservation_number": "RES-20260909-1234",
        "total_amount": 150.00
    }
}
```

---

### 5. POST /api/contact

**Purpose:** Submit contact form

**Request:**
```
POST https://arizu-arimato.free.nf/backend/index.php?r=api/contact
Content-Type: application/json

{
    "full_name": "John Smith",
    "email": "john@email.com",
    "phone": "1234567890",
    "subject": "Inquiry",
    "message": "I would like to know about your services."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Message sent successfully"
}
```

---

### 6. GET /api/settings

**Purpose:** Get hotel settings

**Request:**
```
GET https://arizu-arimato.free.nf/backend/index.php?r=api/settings
```

**Response:**
```json
{
    "success": true,
    "data": {
        "hotel_name": "Arizu Arimato",
        "hotel_address": "Siem Reap",
        "hotel_phone": "085373940",
        "hotel_email": "kompheakkeo1111@gmail.com",
        "currency": "USD",
        "tax_rate": "10.00"
    }
}
```

---

## Admin API (Login Required)

### 7. POST /api/addRoom

**Purpose:** Add room type (admin)

**Request:**
```
POST https://arizu-arimato.free.nf/backend/index.php?r=api/addRoom
Content-Type: application/json

{
    "type_name": "New Room Type",
    "description": "Description here",
    "price_per_night": 75.00,
    "capacity": 3
}
```

---

### 8. POST /api/editRoom

**Purpose:** Edit room type (admin)

**Request:**
```
POST https://arizu-arimato.free.nf/backend/index.php?r=api/editRoom
Content-Type: application/json

{
    "id": 1,
    "type_name": "Updated Name",
    "price_per_night": 80.00
}
```

---

### 9. POST /api/deleteRoom

**Purpose:** Delete room type (admin)

**Request:**
```
POST https://arizu-arimato.free.nf/backend/index.php?r=api/deleteRoom
Content-Type: application/json

{
    "id": 1
}
```

---

## Error Response

```json
{
    "success": false,
    "message": "Error description"
}
```

---

## SUMMARY

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /api/rooms | GET | Get room types |
| /api/rooms/view | GET | Get single room |
| /api/availableRooms | GET | Search available rooms |
| /api/reservation | POST | Create booking |
| /api/contact | POST | Submit contact form |
| /api/settings | GET | Get hotel settings |
| /api/addRoom | POST | Add room type |
| /api/editRoom | POST | Edit room type |
| /api/deleteRoom | POST | Delete room type |
