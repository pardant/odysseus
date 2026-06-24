# Admin Todo Dashboard

A secure, modern admin-style to-do management dashboard built with PHP, MariaDB, and vanilla JavaScript.

## Features

- **Secure Authentication** - Login with username, password, CAPTCHA, and optional TOTP two-factor authentication
- **Security Setup** - Security question and 2FA configuration on first login
- **Task Board** - Kanban-style board with High / Medium / Low priority columns
- **Drag & Drop** - Reorder tasks within and across priority columns (SortableJS)
- **Task Management** - Create, edit, delete tasks with modal popups
- **Categories** - Dynamic user-created categories with color coding
- **User Assignment** - Assign tasks to registered users
- **Master Admin Panel** - Create users with generated passwords, manage accounts
- **PHPMyAdmin** - Database management interface included

## Quick Start

```bash
cd todo-admin
docker compose up -d --build
```

Open `http://localhost:8080` to access the app. On first launch you'll be guided through creating the master admin account.

- **App**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081

## Default Credentials

No default credentials are set. On first visit, the setup wizard creates the master admin account with your chosen username and password.

## Architecture

```
todo-admin/
  config/          # Database connection
  includes/        # Auth, CAPTCHA, TOTP, utility functions
  public/          # Web root (Apache document root)
    admin/         # Admin panel pages
    api/           # JSON API endpoints
    assets/        # CSS and JavaScript
    partials/      # Reusable PHP templates
  sql/             # Database schema
  docker/          # Apache configuration
```

## Tech Stack

- **Backend**: PHP 8.2 with PDO (MariaDB driver)
- **Database**: MariaDB 10.11
- **Frontend**: Vanilla JavaScript, CSS custom properties, SortableJS
- **Auth**: bcrypt password hashing, TOTP (RFC 6238), server-side math CAPTCHA
- **Deployment**: Docker Compose (PHP-Apache + MariaDB + PHPMyAdmin)

## Admin User Management

The master admin can create new users from the Admin Panel. New users receive a generated password and must:

1. Log in with the generated password
2. Set a new password
3. Configure a security question
4. Optionally enable two-factor authentication

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `mariadb` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_NAME` | `todo_admin` | Database name |
| `DB_USER` | `todo_user` | Database user |
| `DB_PASS` | `todo_pass` | Database password |
