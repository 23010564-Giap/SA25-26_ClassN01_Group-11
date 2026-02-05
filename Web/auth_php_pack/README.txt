Login Integration Guide (PHP + MySQLi)

1) Import SQL
- Import backend/schema.sql (or backend/phenikaa_manager.sql if you want sample data).

2) Configure DB
- Update backend/db.php with your MySQL host/user/password/database.

3) Auth flow
- auth_guard.php protects pages that require login.
- login.php is the login form.
- login_handle.php processes login and creates a session.

4) Default demo accounts (if you imported sample data)
- admin / Admin@1234!
- sv001 / Student@2025

Notes
- Keep table/column names unchanged to avoid breaking queries.
