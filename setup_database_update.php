<?php
/**
 * Database Update Script
 * Voegt de docent-rol en teacher_id kolom toe.
 * Veilig om meerdere keren te draaien (idempotent).
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Database Update — Docent rol toevoegen</h1>";
echo "<style>body{font-family:Outfit,Arial,sans-serif;background:#0a0a10;color:#e8eaef;padding:2rem;}a{color:#5a8aff;} .ok{color:#7dff9d;} .warn{color:#ffd47a;} .err{color:#ff7a8a;}</style>";

try {
    $pdo = getDBConnection();

    // 1. Uitbreiden van role enum
    echo "<h3>1. Rollen uitbreiden</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $col = $stmt->fetch();
    if ($col && stripos($col['Type'], 'docent') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','docent','student') NOT NULL DEFAULT 'student'");
        echo "<p class='ok'>&#10003; role-enum uitgebreid met 'docent'</p>";
    } else {
        echo "<p class='warn'>role-enum bevat al 'docent' (overgeslagen)</p>";
    }

    // 2. teacher_id kolom toevoegen
    echo "<h3>2. Kolom teacher_id toevoegen aan courses</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'teacher_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN teacher_id INT NULL AFTER instructor");
        echo "<p class='ok'>&#10003; kolom teacher_id toegevoegd</p>";

        try {
            $pdo->exec("ALTER TABLE courses ADD CONSTRAINT fk_courses_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL");
            echo "<p class='ok'>&#10003; foreign key fk_courses_teacher toegevoegd</p>";
        } catch (PDOException $e) {
            echo "<p class='warn'>FK kon niet worden toegevoegd: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='warn'>kolom teacher_id bestaat al (overgeslagen)</p>";
    }

    // 3. Demo-docent gebruiker aanmaken
    echo "<h3>3. Demo-docent gebruiker</h3>";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['docent1']);
    if (!$stmt->fetch()) {
        $hash = password_hash('docent123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'docent')");
        $stmt->execute(['docent1', 'docent1@leerplatform.nl', $hash, 'Jan', 'Jansen']);
        echo "<p class='ok'>&#10003; demo-docent aangemaakt — login: <strong>docent1</strong> / <strong>docent123</strong></p>";
    } else {
        echo "<p class='warn'>demo-docent 'docent1' bestaat al (overgeslagen)</p>";
    }

    // 4. Bestaande cursussen koppelen aan een docent op basis van instructor-naam
    echo "<h3>4. Bestaande cursussen aan docent koppelen</h3>";
    $stmt = $pdo->query("SELECT id, instructor, teacher_id FROM courses WHERE teacher_id IS NULL");
    $orphans = $stmt->fetchAll();
    $matched = 0;
    foreach ($orphans as $c) {
        $name = trim($c['instructor']);
        if ($name === '') continue;
        $parts = preg_split('/\s+/', $name, 2);
        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';
        $find = $pdo->prepare("SELECT id FROM users WHERE role = 'docent' AND first_name = ? AND last_name = ? LIMIT 1");
        $find->execute([$first, $last]);
        $teacher = $find->fetch();
        if ($teacher) {
            $upd = $pdo->prepare("UPDATE courses SET teacher_id = ? WHERE id = ?");
            $upd->execute([$teacher['id'], $c['id']]);
            $matched++;
        }
    }
    echo "<p class='ok'>&#10003; $matched cursussen gekoppeld aan bestaande docenten</p>";

    echo "<hr><p class='ok'><strong>Update voltooid!</strong></p>";
    echo "<p><a href='login.php'>&rarr; Naar inloggen</a></p>";

} catch (PDOException $e) {
    echo "<p class='err'>&#10007; Fout: " . htmlspecialchars($e->getMessage()) . "</p>";
}
