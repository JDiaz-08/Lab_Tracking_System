<?php
define('DB_PATH', __DIR__ . '/../data/lab.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL;');
            $pdo->exec('PRAGMA foreign_keys=ON;');
            _initDB($pdo);
        } catch (PDOException $e) {
            die('<b>Database error:</b> ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

function _initDB(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name         TEXT    NOT NULL,
            last_name          TEXT    NOT NULL,
            middle_name        TEXT,
            student_id         TEXT    UNIQUE NOT NULL,
            course             TEXT    NOT NULL,
            course_level       INTEGER NOT NULL,
            email              TEXT    UNIQUE NOT NULL,
            address            TEXT    NOT NULL,
            password           TEXT    NOT NULL,
            remaining_sessions INTEGER DEFAULT 30,
            created_at         DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS admins (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS announcements (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            content    TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS reservations (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            lab_room   TEXT    NOT NULL,
            date       TEXT    NOT NULL,
            time_slot  TEXT    NOT NULL,
            purpose    TEXT,
            status     TEXT    DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS notifications (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            message    TEXT    NOT NULL,
            is_read    INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS sit_in_logs (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER NOT NULL,
            lab_room    TEXT    NOT NULL,
            purpose     TEXT,
            status      TEXT    DEFAULT 'active',
            login_time  DATETIME DEFAULT CURRENT_TIMESTAMP,
            logout_time DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS feedback (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER NOT NULL,
            sit_in_id  INTEGER,
            message    TEXT    NOT NULL,
            rating     INTEGER DEFAULT 5,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Migrate existing DBs — add new columns if missing
    foreach ([
        "ALTER TABLE users ADD COLUMN remaining_sessions INTEGER DEFAULT 30",
        "ALTER TABLE sit_in_logs ADD COLUMN status TEXT DEFAULT 'active'",
        "ALTER TABLE users ADD COLUMN profile_picture TEXT",
    ] as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* already exists */ }
    }

    // Seed admin
    if (!$pdo->query("SELECT id FROM admins WHERE username='admin'")->fetch()) {
        $pdo->prepare("INSERT INTO admins (username,password) VALUES ('admin',?)")
            ->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    // Seed announcements
    if ($pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn() == 0) {
        $ins = $pdo->prepare("INSERT INTO announcements (title,content) VALUES (?,?)");
        $ins->execute(['Lab Schedule Update', 'The computer laboratories will be open Monday to Saturday, 7:00 AM – 9:00 PM. Sunday access requires prior reservation approval.']);
        $ins->execute(['Maintenance Notice',  'Lab Room 3 will undergo routine maintenance this Friday. Please use Lab Rooms 1, 2, 4, 5, or 6 as alternatives.']);
        $ins->execute(['Reminder: Lab Conduct','Students are reminded to log out properly after each session. Leaving sessions open affects availability for other students.']);
    }
}