# CineXpress 🎬

A comprehensive web-based cinema ticket booking and management system built with PHP and MySQL. CineXpress allows users to browse movies, select showtimes, pick seats, and complete bookings, while giving admins full control over movies, bookings and transactions.

## Live Demo

http://cinexpress.great-site.net/index.php

---

## Features

### For Users
- **Movie Browsing**: Explore currently showing and coming soon movies with ratings, descriptions, and trailers
- **Seat Selection**: Interactive seat picker with real-time availability
- **Ticket Booking**: Book tickets by selecting showtime, date, and preferred seats
- **Cash on Delivery (COD)**: Simple and accessible payment at the counter
- **Booking History**: View and track all past and current bookings

### For Admins
- **Movie Management**: Add, edit, and remove movies with posters, trailers, descriptions, and pricing
- **Category Management**: Organize movies by category (Hollywood, Bollywood, Kollywood, etc.)
- **Booking Overview**: Monitor all user bookings and their statuses
- **Transaction Records**: View detailed payment and transaction logs
- **User Management**: Manage registered users and their roles

---

## User Roles

| Role | Access |
|------|--------|
| **Super Admin** | Full system access, manages movies, theaters, categories, users, bookings, and transactions |
| **Registered User** | Browse movies, book tickets, make payments, view booking history, manage profile |
| **Guest / Walk-in** | Browse now-showing and coming-soon movies without logging in |

---

## Technologies Used

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript, jQuery, AJAX |
| **Backend** | PHP |
| **Database** | MySQL |
| **Local Server** | XAMPP / WAMP |

---

## Installation

### Prerequisites

- XAMPP or WAMP server installed
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/AshwinMaharjan/cineXpress-management-system.git
   ```

2. **Move to your server root**
   - For XAMPP: place the folder inside `htdocs/`
   - For WAMP: place the folder inside `www/`

3. **Import the database**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database named `dbmovies`
   - Import the provided `dbmovies.sql` file

4. **Configure database credentials**

   Open `connect.php` and update the following:
   ```php
   define('DB_SERVER',   'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME',     'dbmovies');
   ```

5. **Start the application**
   - Launch Apache and MySQL from the XAMPP/WAMP control panel
   - Visit: `http://localhost/cineXpress/`

---

## Usage

### Guest
- Visit the homepage to browse all now-showing and coming-soon movies
- View movie details, trailers, ratings, and showtimes without logging in

### Registered User
- Register an account or log in
- Select a movie → choose a theater and showtime → pick seats → confirm booking
- Pay via **Cash on Delivery**
- View booking history and download or review ticket details
- Update your profile picture and personal information

### Super Admin
- Log in at `/admin` with super admin credentials
- Add and manage movies and categories showtimes
- Monitor all bookings, payments and transaction records
- Manage registered user accounts

---

## Project Structure

```
cineXpress/
├── admin/              # Admin panel pages and logic
├── css/                # Stylesheets
├── images/             # Static image assets
├── uploads/            # Movie posters and user profile images
├── users/              # User-facing pages (booking, profile, history)
├── connect.php         # Database connection configuration
├── dbmovies.sql          # Database schema (no seed data)
└── index.php           # Application entry point
```

---

## Screenshots

- Landing Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/c9f3a1a3-cd0a-4627-a16d-9c2de4fba45b" />

- Now Showing: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/c35d40f7-5fe7-466b-8be8-6be65328400f" />

- All Movies: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/e580ab43-9d57-46e0-be78-4b7a3256843b" />

- Coming Soon: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/50617e72-213b-4bee-b40c-2c7dc63fb223" />

- Login Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/b3f99ac7-d894-4661-b26f-96e107bad292" />

- Register Page: <img width="1358" height="1049" alt="localhost_updated_cinema_hall_register php (1)" src="https://github.com/user-attachments/assets/196e5832-ad55-4020-bdf3-e4433f067190" />

- Movie Details Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/351d52a6-347c-4e84-8713-db707b2fae51" />

- Trailer Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/53b89e94-58aa-4cf8-8e89-2d50c66a3d3f" />

- Book Tickets: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/d6485b34-da33-4cfd-84eb-6c54682d1704" /> <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/2ed716f4-c786-4d9b-a675-4cd07bc64ef5" />

- Admin Dashboard: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/66583392-0b8a-429e-96c4-c164217b59d0" /> <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/2d5ec613-4084-4a60-8e82-e38164fca513" />

- Admin Category Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/896a2f30-914d-4daa-b4c8-4eb8e4145107" />

- Add Movies Page: <img width="1358" height="1046" alt="localhost_updated_cinema_hall_admin_movies php" src="https://github.com/user-attachments/assets/ffebed76-a2d7-4783-bbfe-b66134f04a92" />

- View Movie Added Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/c4077833-c212-4692-bdac-1878d510567a" />

- Revenue Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/8c72f5a2-0a59-4380-9d84-baf1fe014672" /> <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/565397e6-61ae-4e3e-b65d-b3d6f75209d8" /> <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/3745f34c-4dff-42eb-a600-07b7fb08a7c7" />

- View All Users Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/d985cba4-c331-4235-867b-ad5f608aa237" />

- View All Booking Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/9d0c49fa-4f15-47f6-9109-cbd3bb77e828" />

- Users Seat Booking Page:<img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/64da76b7-837a-4113-a085-ec04e82b1a66" />

- Users Ticket Confirmed Page: <img width="1358" height="826" alt="localhost_updated_cinema_hall_booking_success php_bookingid=23" src="https://github.com/user-attachments/assets/9c1aef0f-0507-44ee-97d1-3a6e61879bae" />
 
- Users Dashboard Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/d0370533-b8ac-46e2-a8c0-92edb972fdaa" />

- Users Booking Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/45aa2e81-a5b0-4c81-8258-bc9b46d0f830" />

- Users Booking Details: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/cdaeba6c-cac1-40fe-9f32-6b81f9b75585" />

- Users Ticket Print Page: <img width="1366" height="768" alt="image" src="https://github.com/user-attachments/assets/65c65695-d361-41bc-a58d-9872088c6184" />

---

## Roadmap

- [ ] Khalti or eSewa payment gateway integration
- [ ] QR code e-ticket generation
- [ ] Email/SMS booking confirmation notifications
- [ ] Online seat map with live availability visualization
- [ ] Movie ratings and reviews by users
- [ ] Multi-language support (Nepali / English)
- [ ] Mobile responsive design

---

## Contact

Have questions or want to contribute? Reach out:

- **GitHub**: [AshwinMaharjan](https://github.com/AshwinMaharjan)
- **Email**: [maharjan.ashwin098@gmail.com](mailto:maharjan.ashwin098@gmail.com)

---

> Built with 🎬 and PHP. CineXpress — your cinema, your seat, your experience.
