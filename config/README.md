# Database configuration

Create `db.php` in this directory on each environment. The file must define
the `$conn` MySQLi connection used by the PHP pages in `public/`.

`db.php` is intentionally ignored by Git so database credentials are never
committed. On production, move the existing `public/db.php` file here before
deploying the updated public pages.
