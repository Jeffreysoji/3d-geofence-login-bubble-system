# KKBank 🏦

A web-based banking application with geofence-based login authentication, built as a mini-project using PHP, MySQL, and the Google Maps API.

---

## Features

- **User Registration & Login** — Secure account creation with hashed passwords and security questions.
- **Geofence Authentication** — Users define a geographic boundary (circle or polygon); login is only allowed from within that area.
- **Admin Panel** — Manage users, view auth logs, and approve/reject registrations.
- **Email Notifications** — Alerts sent on suspicious or out-of-zone login attempts.
- **Responsive UI** — Clean, dark-themed interface for both user and admin sides.

---

## Tech Stack

| Layer      | Technology                  |
|------------|-----------------------------|
| Backend    | PHP 8.x (PDO)               |
| Database   | MySQL / MariaDB (XAMPP)     |
| Frontend   | HTML5, CSS3, Vanilla JS     |
| Maps       | Google Maps JavaScript API  |
| Server     | Apache (XAMPP)              |

---

## Project Structure

```
kkbank/
├── index.html          # Landing page
├── style.css           # Global styles
├── main.js             # Shared JS
├── db.php              # DB connection (⚠️ not committed — see db.example.php)
├── db.example.php      # Template — copy to db.php and configure
├── setup_db.php        # One-time DB table setup
├── admin/              # Admin panel (login, dashboard, alerts)
└── user/               # User portal (register, login, geofence, dashboard)
```

---

## Setup (XAMPP / Local)

1. **Clone the repo** into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/YOUR_USERNAME/kkbank.git
   cd kkbank
   ```

2. **Configure the database:**
   ```bash
   cp db.example.php db.php
   # Edit db.php and set your DB credentials
   ```

3. **Create the database:**
   - Open phpMyAdmin → create a database named `kkbank_db`
   - Navigate to `http://localhost/kkbank/setup_db.php` to auto-create tables

4. **Google Maps API:**
   - Get an API key from [Google Cloud Console](https://console.cloud.google.com/)
   - Replace `YOUR_GOOGLE_MAPS_API_KEY` in `user/geofence.php`

5. **Start XAMPP** (Apache + MySQL) and open `http://localhost/kkbank/`

---

## Environment Variables (Production)

| Variable         | Description          |
|------------------|----------------------|
| `KKBANK_DB_HOST` | Database host        |
| `KKBANK_DB_PORT` | Database port        |
| `KKBANK_DB_NAME` | Database name        |
| `KKBANK_DB_USER` | Database username    |
| `KKBANK_DB_PASS` | Database password    |

---

## SDG Alignment

This project supports:
- **SDG 9** — Industry, Innovation and Infrastructure
- **SDG 16** — Peace, Justice and Strong Institutions (secure digital banking)
- **SDG 17** — Partnerships for the Goals

---

## License

MIT License — see [LICENSE](LICENSE) for details.
