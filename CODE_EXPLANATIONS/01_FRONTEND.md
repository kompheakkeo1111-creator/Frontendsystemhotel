# FRONTEND FILES EXPLAINED
## What Each File Does

---

## 1. index.html - Homepage

**Purpose:** Main landing page for hotel website

**What It Shows:**
- Hotel logo and name
- Navigation menu (Home, About, Rooms, Gallery, Contact)
- Hero section with background image
- Features section (Pool, Dining, Service, Spa, Wi-Fi, Transfer)
- Statistics (50+ Rooms, 10K+ Guests, 15 Years, 20 Awards)
- Room previews (loaded from API)
- Footer with contact info

**How It Works:**
1. User opens index.html
2. CSS loads styling
3. JavaScript loads room data from API
4. Rooms are displayed as cards
5. User can click "Book Now" to go to rooms page

**Lines:** 200+

---

## 2. about.html - About Page

**Purpose:** Tell the hotel's story

**What It Shows:**
- Hotel history
- Mission and vision
- Team information
- Why choose us

**Lines:** 150+

---

## 3. rooms.html - Room Booking Page

**Purpose:** Display rooms and allow booking

**What It Shows:**
- Search form (check-in date, check-out date, guests)
- Room grid with prices
- Booking modal popup
- Amenities section

**Key Functions:**
```javascript
loadRooms()          // Fetch rooms from API
renderRooms(data)    // Display room cards
openBooking(...)     // Open booking form
closeBookingModal()  // Close booking form
updateBookingTotal() // Calculate total price
```

**How Booking Works:**
1. User selects dates
2. User clicks "Search"
3. System shows available rooms
4. User clicks "Book Now"
5. User fills in details
6. User submits form
7. System creates reservation
8. User sees confirmation

**Lines:** 370+

---

## 4. gallery.html - Photo Gallery

**Purpose:** Show hotel photos

**What It Shows:**
- Grid of hotel photos
- Click to enlarge (lightbox)
- Categories (Rooms, Restaurant, Pool, etc.)

**Lines:** 150+

---

## 5. contact.html - Contact Form

**Purpose:** Allow guests to send messages

**What It Shows:**
- Contact form (name, email, phone, subject, message)
- Hotel address and phone
- Google Maps embed

**How It Works:**
1. User fills in form
2. User clicks "Send Message"
3. JavaScript sends data to API
4. System saves message in database
5. User sees "Thank You" message

**Lines:** 200+

---

## 6. css/style.css - Styling

**Purpose:** Make the website look nice

**What It Controls:**
- Colors (Gold, Dark, Cream)
- Fonts (Playfair Display, Poppins)
- Layout (Flexbox, Grid)
- Animations (Fade-in, Hover effects)
- Responsive design (Mobile, Tablet, Desktop)

**Key Styles:**
```css
:root {
    --gold: #c8a84e;        /* Gold color */
    --dark: #1a1a2e;        /* Dark color */
    --cream: #faf8f5;       /* Cream background */
}

.navbar       /* Navigation bar */
.hero         /* Hero section */
.features     /* Feature cards */
.rooms-grid   /* Room cards grid */
.gallery-grid /* Gallery grid */
.footer       /* Footer section */
```

**Responsive Breakpoints:**
- Desktop: > 768px
- Mobile: ≤ 768px

**Lines:** 830+

---

## 7. js/api.js - API Communication

**Purpose:** Talk to backend server

**What It Does:**
- Sends requests to backend
- Receives data from backend
- Formats data for display

**Key Functions:**
```javascript
apiGet(endpoint)           // GET request
apiPost(endpoint, data)    // POST request
formatPrice(amount)        // Format currency
calcNights(checkIn, out)   // Calculate nights
```

**API Base URL:**
```javascript
const API_BASE = 'https://arizu-arimato.free.nf/backend/index.php';
```

**Lines:** 100+

---

## 8. js/main.js - Animations & UI

**Purpose:** Add interactivity

**What It Does:**
- Scroll effects (navbar changes color)
- Mobile menu toggle
- Fade-in animations
- Gallery lightbox
- Stats counter animation

**Key Features:**
```javascript
// Navbar changes on scroll
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', scrollY > 50);
});

// Mobile menu
hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Fade-in animation
const observer = new IntersectionObserver(...);
document.querySelectorAll('.fade-in').forEach(...);

// Stats counter
counterObserver.observe(counter);
```

**Lines:** 200+

---

## 9. images/ - Hotel Photos

**Purpose:** Store hotel images

**Files:**
- hotel-exterior.jpg (Hero image)
- room-deluxe.jpg (Deluxe room)
- room-vip.jpg (VIP room)
- room-suite.jpg (Suite room)
- hotel-pool.jpg (Pool photo)
- hotel-restaurant.jpg (Restaurant photo)

---

## SUMMARY

| File | Purpose | Lines |
|------|---------|-------|
| index.html | Homepage | 200+ |
| about.html | About page | 150+ |
| rooms.html | Room booking | 370+ |
| gallery.html | Photo gallery | 150+ |
| contact.html | Contact form | 200+ |
| css/style.css | Styling | 830+ |
| js/api.js | API calls | 100+ |
| js/main.js | Animations | 200+ |
