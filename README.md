# SkillHub – Backend Foundation

A clean PHP/MySQL freelance marketplace backend.

## Stack
- PHP 8.1+ with PDO (prepared statements throughout)
- MySQL 8.0+
- Vanilla HTML / CSS / JS (no frameworks)

---

## Setup

### 1. Import the database
```bash
mysql -u root -p < skillhub.sql
```

### 2. Configure DB credentials
Edit `config/db.php` and set:
```php
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 3. Place files on your server
Drop the `skillhub/` folder into your web root (e.g. `htdocs/` or `/var/www/html/`).  
The project root should be served as the document root.

### 4. Verify PHP session settings
The `.htaccess` sets `cookie_httponly` and `use_strict_mode`.  
For HTTPS, uncomment `cookie_secure` in `includes/helpers.php`.

---

## Demo Accounts
| Role       | Email                     | Password    |
|------------|---------------------------|-------------|
| Client     | client@skillhub.test      | Password1!  |
| Freelancer | freelancer@skillhub.test  | Password1!  |

---

## Folder Structure
```
skillhub/
├── .htaccess                   # Apache security rules
├── index.php                   # Landing / redirect page
├── skillhub.sql                # Full DB schema + seeds
│
├── config/
│   └── db.php                  # PDO connection (singleton)
│
├── includes/
│   ├── helpers.php             # Session, auth guards, flash, e()
│   ├── header.php              # HTML <head> + site nav partial
│   └── footer.php             # Closing HTML partial
│
├── auth/
│   ├── register.php            # Registration (password_hash)
│   ├── login.php               # Login (password_verify)
│   └── logout.php              # Session destruction
│
├── dashboard/
│   ├── client.php              # Client dashboard
│   └── freelancer.php          # Freelancer dashboard
│
└── assets/
    └── css/
        └── main.css            # Minimal UI stylesheet
```

---

## Security Notes
- All DB queries use **PDO prepared statements** — no raw interpolation.
- Passwords hashed with `PASSWORD_BCRYPT` (cost 12); auto-rehash on login.
- Generic login error prevents email enumeration.
- Session regenerated on login (`session_regenerate_id(true)`).
- Session destroyed fully on logout (cookie + server data).
- `.htaccess` blocks direct access to `config/` and `includes/`.

---

## Next Steps (Phase 2)
- [ ] Job posting CRUD (clients)
- [ ] Proposal / bidding system (freelancers)
- [ ] Order management
- [ ] Messaging
- [ ] Profile editing
