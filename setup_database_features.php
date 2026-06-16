<?php
/**
 * Feature Update Script — Top 5 features
 * - Gamification (XP, streak, badges)
 * - Course modules + completions + ratings
 * - Notifications + activity feed
 *
 * Veilig om meerdere keren te draaien (idempotent).
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Feature Update</title>";
echo "<style>body{font-family:Outfit,Arial,sans-serif;background:#0a0a10;color:#e8eaef;padding:2rem;line-height:1.6;}a{color:#5a8aff;} .ok{color:#7dff9d;} .warn{color:#ffd47a;} .err{color:#ff7a8a;} h3{margin-top:1.8rem;}</style></head><body>";
echo "<h1>Feature Update — Gamification, modules, ratings, notifications</h1>";

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = getDBConnection();

    // ---------- 1. user_stats ----------
    echo "<h3>1. Tabel user_stats</h3>";
    if (!tableExists($pdo, 'user_stats')) {
        $pdo->exec("CREATE TABLE user_stats (
            user_id INT PRIMARY KEY,
            xp INT NOT NULL DEFAULT 0,
            streak_days INT NOT NULL DEFAULT 0,
            last_streak_date DATE NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; user_stats aangemaakt</p>";
    } else {
        echo "<p class='warn'>user_stats bestaat al</p>";
    }

    // Zorg dat alle bestaande gebruikers een record hebben
    $pdo->exec("INSERT IGNORE INTO user_stats (user_id, xp) SELECT id, 0 FROM users");
    echo "<p class='ok'>&#10003; bestaande gebruikers gesynchroniseerd</p>";

    // ---------- 2. badges + user_badges ----------
    echo "<h3>2. Tabellen badges & user_badges</h3>";
    if (!tableExists($pdo, 'badges')) {
        $pdo->exec("CREATE TABLE badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL,
            icon VARCHAR(50) NOT NULL,
            color VARCHAR(20) NOT NULL DEFAULT 'primary'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; badges aangemaakt</p>";
    } else {
        echo "<p class='warn'>badges bestaat al</p>";
    }
    if (!tableExists($pdo, 'user_badges')) {
        $pdo->exec("CREATE TABLE user_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_badge (user_id, badge_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; user_badges aangemaakt</p>";
    } else {
        echo "<p class='warn'>user_badges bestaat al</p>";
    }

    // Seed badges
    $seedBadges = [
        ['first_enroll',  'Eerste stap',      'Schreef je in voor je eerste cursus',         'bi-rocket-takeoff', 'primary'],
        ['five_courses',  'Leergierig',       'Volgt 5 of meer cursussen',                  'bi-collection',     'info'],
        ['ten_courses',   'Kennishonger',     'Volgt 10 of meer cursussen',                 'bi-stars',          'warning'],
        ['first_module',  'Eerste les',       'Voltooide je eerste module',                 'bi-check-circle',   'success'],
        ['course_done',   'Diploma!',         'Voltooide een complete cursus',              'bi-mortarboard',    'warning'],
        ['streak_3',      'Op dreef',         '3 dagen op rij ingelogd',                    'bi-fire',           'danger'],
        ['streak_7',      'Week-strijder',    '7 dagen op rij ingelogd',                    'bi-fire',           'warning'],
        ['streak_30',     'Maand-master',     '30 dagen op rij ingelogd',                   'bi-trophy',         'warning'],
        ['xp_100',        'Centurion',        '100 XP verzameld',                           'bi-lightning',      'info'],
        ['xp_500',        'XP Power',         '500 XP verzameld',                           'bi-lightning-charge','warning'],
        ['xp_1000',       'XP Legend',        '1000 XP verzameld',                          'bi-gem',            'danger'],
        ['reviewer',      'Recensent',        'Schreef een review voor een cursus',         'bi-chat-quote',     'info'],
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO badges (code, name, description, icon, color) VALUES (?,?,?,?,?)");
    foreach ($seedBadges as $b) { $stmt->execute($b); }
    echo "<p class='ok'>&#10003; " . count($seedBadges) . " badges gesynchroniseerd</p>";

    // ---------- 3. course_modules + module_completions ----------
    echo "<h3>3. Tabellen course_modules & module_completions</h3>";
    if (!tableExists($pdo, 'course_modules')) {
        $pdo->exec("CREATE TABLE course_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NULL,
            position INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; course_modules aangemaakt</p>";
    } else {
        echo "<p class='warn'>course_modules bestaat al</p>";
    }
    if (!tableExists($pdo, 'module_completions')) {
        $pdo->exec("CREATE TABLE module_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            module_id INT NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_module (user_id, module_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; module_completions aangemaakt</p>";
    } else {
        echo "<p class='warn'>module_completions bestaat al</p>";
    }

    // Demo modules voor cursussen die er nog geen hebben
    $courses = $pdo->query("SELECT id, title FROM courses")->fetchAll();
    $modulesInserted = 0;
    foreach ($courses as $c) {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
        $cnt->execute([$c['id']]);
        if ((int)$cnt->fetchColumn() === 0) {
            $demoMods = [
                ['Introductie', 'Maak kennis met de cursus en de leerdoelen.', 1],
                ['Kern concepten', 'De belangrijkste theorie en voorbeelden.', 2],
                ['Praktijk opdracht', 'Pas het geleerde toe in een opdracht.', 3],
                ['Eindopdracht', 'Lever het eindresultaat in.', 4],
            ];
            $ins = $pdo->prepare("INSERT INTO course_modules (course_id, title, description, position) VALUES (?, ?, ?, ?)");
            foreach ($demoMods as $m) {
                $ins->execute([$c['id'], $m[0], $m[1], $m[2]]);
                $modulesInserted++;
            }
        }
    }
    echo "<p class='ok'>&#10003; $modulesInserted demo-modules toegevoegd</p>";

    // ---------- 4. course_ratings ----------
    echo "<h3>4. Tabel course_ratings</h3>";
    if (!tableExists($pdo, 'course_ratings')) {
        $pdo->exec("CREATE TABLE course_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            rating TINYINT NOT NULL,
            review TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_course_rating (user_id, course_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; course_ratings aangemaakt</p>";
    } else {
        echo "<p class='warn'>course_ratings bestaat al</p>";
    }

    // ---------- 5. notifications ----------
    echo "<h3>5. Tabel notifications</h3>";
    if (!tableExists($pdo, 'notifications')) {
        $pdo->exec("CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            icon VARCHAR(50) NOT NULL DEFAULT 'bi-bell',
            title VARCHAR(150) NOT NULL,
            message VARCHAR(500) NULL,
            link VARCHAR(200) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; notifications aangemaakt</p>";
    } else {
        echo "<p class='warn'>notifications bestaat al</p>";
    }

    // ---------- 6. activity_feed ----------
    echo "<h3>6. Tabel activity_feed</h3>";
    if (!tableExists($pdo, 'activity_feed')) {
        $pdo->exec("CREATE TABLE activity_feed (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            icon VARCHAR(50) NOT NULL DEFAULT 'bi-activity',
            link VARCHAR(200) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_activity_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p class='ok'>&#10003; activity_feed aangemaakt</p>";
    } else {
        echo "<p class='warn'>activity_feed bestaat al</p>";
    }

    echo "<hr><p class='ok'><strong>Update voltooid!</strong></p>";
    echo "<p><a href='login.php'>&rarr; Naar inloggen</a></p>";

} catch (PDOException $e) {
    echo "<p class='err'>&#10007; Fout: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
