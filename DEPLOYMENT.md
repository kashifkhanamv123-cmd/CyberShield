# CyberShield Deployment Guide

Follow these steps to deploy CyberShield to a live server (CPanel, VPS, or shared hosting).

## 1. Database Setup
1.  Create a new MySQL database (e.g., `cybershield_db`).
2.  Import the consolidated schema located at `database/schema.sql`.
3.  Create a database user with full privileges on this database.

## 2. Environment Configuration
1.  Rename `.env.example` to `.env` in the root directory.
2.  Update the following values in `.env`:
    - `DB_HOST`: Usually `localhost`.
    - `DB_USER`: Your database username.
    - `DB_PASS`: Your database password.
    - `DB_NAME`: Your database name.
    - `GEMINI_API_KEY`: Your Google Gemini API key.
    - `BASE_URL`: The full URL to your project (e.g., `https://yourdomain.com`).
    - `RECAPTCHA_SITE_KEY`: Your Google reCAPTCHA v2 Site Key.
    - `RECAPTCHA_SECRET_KEY`: Your Google reCAPTCHA v2 Secret Key.

## 3. Server Configuration (Apache)
- The project includes `.htaccess` files to secure sensitive directories.
- Ensure `mod_rewrite` is enabled on your Apache server.
- The root directory should be the project root.

## 4. File Permissions
- Ensure the `uploads/` directory is writable by the web server:
  ```bash
  chmod -R 755 uploads/
  ```
- Ensure the `logs/` directory exists and is writable:
  ```bash
  mkdir logs
  chmod -R 755 logs/
  ```

## 5. Security Checklist
- [ ] `.env` is NOT accessible via browser (verified by root `.htaccess`).
- [ ] `config/`, `includes/`, and `database/` directories are protected.
- [ ] `APP_ENV` is set to `production` in `.env` to disable error display.
- [ ] Use HTTPS for secure session handling.

## Troubleshooting
- **Database Connection Error**: Check your `.env` credentials and ensure the MySQL service is running.
- **403 Forbidden**: This is expected for sensitive directories. If you get this on the homepage, check your root `.htaccess` or file permissions.
- **Gemini API Error**: Verify your API key in `.env` and check if your server has `cURL` enabled.
