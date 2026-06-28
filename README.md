# Guild Glory Simulator

A cyberpunk hacker-themed simulation website that visually simulates deploying virtual guild bots. The application is purely for entertainment and UI simulation. It does not connect to or modify any real game, account, server, or third-party service.

## Features

*   **Cyberpunk Aesthetic:** Matrix-style animated background, falling green code rain, glowing effects, and a dark theme.
*   **5-Step Simulation Flow:**
    1.  **Access Terminal:** Enter a valid access key.
    2.  **Guild Configuration:** Input Guild ID and select a server region.
    3.  **Plan Selection:** Choose the number of bots to simulate.
    4.  **Deployment Sequence:** Animated terminal with progress bar and typing effects.
    5.  **Success Screen:** Final status with a confetti animation.
*   **Admin Panel:** Manage access keys, view simulation logs, and configure settings.
*   **Single-Click Setup:** The SQLite database is automatically initialized on the first run.
*   **Security:** Includes CSRF protection, password hashing, prepared statements to prevent SQL injection, output escaping for XSS prevention, and basic rate limiting.

## Technologies Used

*   **Backend:** PHP 8+
*   **Database:** SQLite
*   **Frontend:** HTML5, CSS3, Vanilla JavaScript

## Setup & Installation

1.  **Requirements:** A web server running PHP 8+ with the PDO SQLite extension enabled.
2.  **Installation:** Place the files in your web server's document root. Ensure the `db` directory is writable by the web server user so the SQLite database can be created.
3.  **Run:** Access `index.php` in your web browser. The database (`db/guild_glory.sqlite`) will be created automatically on the first load.

## Admin Access

*   **URL:** Navigate to `admin.php`
*   **Default Credentials:**
    *   **Username:** `admin`
    *   **Password:** `admin123`

*It is highly recommended to change these credentials in a production environment.*

## Disclaimer

This application is a **SIMULATION ONLY**. It does not interact with any external systems, APIs, or games. It is designed for entertainment and UI demonstration purposes.
