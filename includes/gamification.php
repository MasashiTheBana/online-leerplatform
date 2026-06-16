<?php
/**
 * Gamification & social helpers
 *
 * Functies voor XP, levels, streaks, badges, notificaties en activity feed.
 * Veilig om meerdere keren aan te roepen - alle awards zijn idempotent.
 */

if (!function_exists('getDBConnection')) {
    require_once __DIR__ . '/../config/database.php';
}

/* -------------------- XP / LEVELS -------------------- */

const XP_PER_ENROLL    = 25;
const XP_PER_MODULE    = 10;
const XP_PER_COURSE    = 100;
const XP_PER_RATING    = 15;
const XP_PER_STREAK_DAY= 5;

/**
 * Bereken het level uit XP. 100 XP per level, schaalt licht.
 */
function levelFromXp(int $xp): int {
    if ($xp <= 0) return 1;
    return (int) floor((-1 + sqrt(1 + 8 * ($xp / 50))) / 2) + 1;
}

/** XP nodig om in een level te komen */
function xpForLevel(int $level): int {
    if ($level <= 1) return 0;
    return (int) (50 * ($level - 1) * $level);
}

function getUserStats(int $userId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT xp, streak_days, last_streak_date FROM user_stats WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->prepare("INSERT IGNORE INTO user_stats (user_id, xp) VALUES (?, 0)")->execute([$userId]);
        $row = ['xp' => 0, 'streak_days' => 0, 'last_streak_date' => null];
    }
    $xp = (int)$row['xp'];
    $level = levelFromXp($xp);
    $currentLevelXp = xpForLevel($level);
    $nextLevelXp = xpForLevel($level + 1);
    $progressInLevel = $xp - $currentLevelXp;
    $progressNeeded  = max(1, $nextLevelXp - $currentLevelXp);
    return [
        'xp' => $xp,
        'level' => $level,
        'streak_days' => (int)$row['streak_days'],
        'last_streak_date' => $row['last_streak_date'],
        'progress_pct' => min(100, (int) round(($progressInLevel / $progressNeeded) * 100)),
        'xp_in_level' => $progressInLevel,
        'xp_to_next' => $nextLevelXp - $xp,
    ];
}

/**
 * Geef XP aan een gebruiker en stuur notificatie bij level-up.
 * Retourneert ['gained' => int, 'leveled_up' => bool, 'new_level' => int].
 */
function awardXp(int $userId, int $amount, string $reason = ''): array {
    if ($amount <= 0) return ['gained' => 0, 'leveled_up' => false, 'new_level' => 0];
    $pdo = getDBConnection();
    $before = getUserStats($userId);
    $pdo->prepare("INSERT INTO user_stats (user_id, xp) VALUES (?, ?) ON DUPLICATE KEY UPDATE xp = xp + VALUES(xp)")
        ->execute([$userId, $amount]);
    $after = getUserStats($userId);
    $leveledUp = $after['level'] > $before['level'];

    if ($leveledUp) {
        addNotification($userId, "Level omhoog!", "Je hebt level {$after['level']} bereikt!", 'profile.php', 'bi-arrow-up-circle');
        addActivity($userId, 'level_up', "Bereikte level {$after['level']}", 'bi-arrow-up-circle', 'profile.php');
    }

    // XP-drempel badges
    checkXpBadges($userId, $after['xp']);

    return ['gained' => $amount, 'leveled_up' => $leveledUp, 'new_level' => $after['level']];
}

/* -------------------- STREAKS -------------------- */

/**
 * Werk streak bij op login. Maakt streak +1 bij opvolgende dagen,
 * houdt 'm bij vandaag, en reset bij gap > 1.
 * Retourneert nieuwe streak_days, en of er een nieuwe streak-dag is.
 */
function touchStreakOnLogin(int $userId): array {
    $pdo = getDBConnection();
    $stats = getUserStats($userId);
    $today = date('Y-m-d');
    $last = $stats['last_streak_date'];

    $newStreak = $stats['streak_days'];
    $awarded = false;
    if ($last === $today) {
        // al geteld vandaag
    } else {
        if ($last !== null && date('Y-m-d', strtotime($last . ' +1 day')) === $today) {
            $newStreak = $stats['streak_days'] + 1;
        } else {
            $newStreak = 1;
        }
        $stmt = $pdo->prepare("UPDATE user_stats SET streak_days = ?, last_streak_date = ? WHERE user_id = ?");
        $stmt->execute([$newStreak, $today, $userId]);
        awardXp($userId, XP_PER_STREAK_DAY, 'streak');
        $awarded = true;
        // Streak badges
        if ($newStreak >= 3)  unlockBadge($userId, 'streak_3');
        if ($newStreak >= 7)  unlockBadge($userId, 'streak_7');
        if ($newStreak >= 30) unlockBadge($userId, 'streak_30');
    }

    return ['streak_days' => $newStreak, 'awarded' => $awarded];
}

/* -------------------- BADGES -------------------- */

function getBadgeByCode(string $code): ?array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE code = ?");
    $stmt->execute([$code]);
    $b = $stmt->fetch();
    return $b ?: null;
}

/**
 * Geef een gebruiker een badge (idempotent). Stuurt notificatie+activity bij eerste keer.
 * Retourneert true als nieuw uitgereikt.
 */
function unlockBadge(int $userId, string $code): bool {
    $badge = getBadgeByCode($code);
    if (!$badge) return false;

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
    $stmt->execute([$userId, $badge['id']]);
    if ($stmt->fetch()) return false;

    $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)")
        ->execute([$userId, $badge['id']]);

    addNotification(
        $userId,
        "Nieuwe badge: {$badge['name']}",
        $badge['description'],
        'profile.php#badges',
        $badge['icon']
    );
    addActivity($userId, 'earned_badge', "Verdiende badge \"{$badge['name']}\"", $badge['icon'], 'profile.php#badges');
    return true;
}

function checkXpBadges(int $userId, int $xp): void {
    if ($xp >= 100)  unlockBadge($userId, 'xp_100');
    if ($xp >= 500)  unlockBadge($userId, 'xp_500');
    if ($xp >= 1000) unlockBadge($userId, 'xp_1000');
}

/* -------------------- NOTIFICATIONS -------------------- */

function addNotification(int $userId, string $title, ?string $message = null, ?string $link = null, string $icon = 'bi-bell'): void {
    $pdo = getDBConnection();
    $pdo->prepare("INSERT INTO notifications (user_id, icon, title, message, link) VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $icon, $title, $message, $link]);
}

function getUnreadNotificationCount(int $userId): int {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getRecentNotifications(int $userId, int $limit = 10): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markNotificationsRead(int $userId, ?int $notificationId = null): void {
    $pdo = getDBConnection();
    if ($notificationId === null) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    } else {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND id = ?")
            ->execute([$userId, $notificationId]);
    }
}

/* -------------------- ACTIVITY FEED -------------------- */

function addActivity(int $userId, string $actionType, string $title, string $icon = 'bi-activity', ?string $link = null): void {
    $pdo = getDBConnection();
    $pdo->prepare("INSERT INTO activity_feed (user_id, action_type, title, icon, link) VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $actionType, $title, $icon, $link]);
}

function getRecentActivity(int $limit = 15, ?int $userId = null): array {
    $pdo = getDBConnection();
    if ($userId !== null) {
        $stmt = $pdo->prepare("
            SELECT af.*, u.first_name, u.last_name, u.username
            FROM activity_feed af
            INNER JOIN users u ON u.id = af.user_id
            WHERE af.user_id = ?
            ORDER BY af.created_at DESC LIMIT $limit
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT af.*, u.first_name, u.last_name, u.username
            FROM activity_feed af
            INNER JOIN users u ON u.id = af.user_id
            ORDER BY af.created_at DESC LIMIT $limit
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/* -------------------- MODULES / VOORTGANG -------------------- */

function getCourseModules(int $courseId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY position ASC, id ASC");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

function getCompletedModuleIds(int $userId, int $courseId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT mc.module_id
        FROM module_completions mc
        INNER JOIN course_modules cm ON cm.id = mc.module_id
        WHERE mc.user_id = ? AND cm.course_id = ?
    ");
    $stmt->execute([$userId, $courseId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'module_id'));
}

function getCourseProgress(int $userId, int $courseId): array {
    $modules = getCourseModules($courseId);
    $total = count($modules);
    $done = count(getCompletedModuleIds($userId, $courseId));
    $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
    return ['total' => $total, 'done' => $done, 'pct' => $pct, 'is_complete' => $total > 0 && $done === $total];
}

/**
 * Markeer een module als voltooid (of haal weg) en geef XP + check badges.
 */
function completeModule(int $userId, int $moduleId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT cm.id, cm.course_id, cm.title, c.title AS course_title
        FROM course_modules cm INNER JOIN courses c ON c.id = cm.course_id WHERE cm.id = ?");
    $stmt->execute([$moduleId]);
    $mod = $stmt->fetch();
    if (!$mod) return ['ok' => false];

    $courseId = (int)$mod['course_id'];

    // Eerste module ooit?
    $cntBefore = (int)$pdo->query("SELECT COUNT(*) FROM module_completions WHERE user_id = $userId")->fetchColumn();

    $stmt = $pdo->prepare("INSERT IGNORE INTO module_completions (user_id, module_id) VALUES (?, ?)");
    $stmt->execute([$userId, $moduleId]);
    $newlyDone = $stmt->rowCount() > 0;

    if ($newlyDone) {
        awardXp($userId, XP_PER_MODULE, 'module');
        addActivity($userId, 'module_done', "Module \"{$mod['title']}\" voltooid", 'bi-check-circle', 'course_detail.php?id=' . $courseId);
        if ($cntBefore === 0) unlockBadge($userId, 'first_module');

        // Cursus voltooid?
        $progress = getCourseProgress($userId, $courseId);
        if ($progress['is_complete']) {
            awardXp($userId, XP_PER_COURSE, 'course_complete');
            unlockBadge($userId, 'course_done');
            addNotification($userId, "Cursus voltooid!", "Je hebt \"{$mod['course_title']}\" afgerond.", 'certificate.php?course_id=' . $courseId, 'bi-mortarboard');
            addActivity($userId, 'course_done', "Voltooide cursus \"{$mod['course_title']}\"", 'bi-mortarboard', 'certificate.php?course_id=' . $courseId);
        }
    }
    return ['ok' => true, 'newly_done' => $newlyDone, 'progress' => getCourseProgress($userId, $courseId)];
}

function uncompleteModule(int $userId, int $moduleId): void {
    $pdo = getDBConnection();
    $pdo->prepare("DELETE FROM module_completions WHERE user_id = ? AND module_id = ?")
        ->execute([$userId, $moduleId]);
}

/* -------------------- RATINGS -------------------- */

function getCourseRatingSummary(int $courseId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c, AVG(rating) AS avg_r FROM course_ratings WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $r = $stmt->fetch();
    return ['count' => (int)$r['c'], 'average' => $r['avg_r'] !== null ? round((float)$r['avg_r'], 1) : 0];
}

function getUserCourseRating(int $userId, int $courseId): ?array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM course_ratings WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    $r = $stmt->fetch();
    return $r ?: null;
}

function saveCourseRating(int $userId, int $courseId, int $rating, string $review = ''): void {
    $rating = max(1, min(5, $rating));
    $pdo = getDBConnection();
    $existing = getUserCourseRating($userId, $courseId);
    if ($existing) {
        $pdo->prepare("UPDATE course_ratings SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$rating, $review, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO course_ratings (user_id, course_id, rating, review) VALUES (?, ?, ?, ?)")
            ->execute([$userId, $courseId, $rating, $review]);
        awardXp($userId, XP_PER_RATING, 'rating');
        unlockBadge($userId, 'reviewer');
        $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        $title = $stmt->fetchColumn() ?: 'cursus';
        addActivity($userId, 'rated_course', "Gaf een beoordeling aan \"$title\"", 'bi-star-fill', 'course_detail.php?id=' . $courseId);
    }
}

function getCourseReviews(int $courseId, int $limit = 20): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT cr.*, u.first_name, u.last_name, u.username
        FROM course_ratings cr
        INNER JOIN users u ON u.id = cr.user_id
        WHERE cr.course_id = ?
        ORDER BY cr.created_at DESC
        LIMIT $limit
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}

/* -------------------- AVATAR / VISUAL HELPERS -------------------- */

/**
 * Geef een stabiele kleurgradient terug op basis van een string (naam, titel).
 */
function gradientFor(string $key): string {
    $palettes = [
        ['#002ef4', '#7c00ff'],
        ['#00b7ff', '#002ef4'],
        ['#ff5cd9', '#7c00ff'],
        ['#00ffae', '#00b7ff'],
        ['#ffae00', '#ff3d3d'],
        ['#7c00ff', '#ff3d8d'],
        ['#00ffae', '#002ef4'],
        ['#ff9100', '#ff003d'],
    ];
    $hash = 0;
    for ($i = 0; $i < strlen($key); $i++) { $hash = ($hash * 31 + ord($key[$i])) & 0xffffffff; }
    $p = $palettes[$hash % count($palettes)];
    return "linear-gradient(135deg, {$p[0]} 0%, {$p[1]} 100%)";
}

function initialsFor(string $name): string {
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/**
 * Render een ronde avatar met initialen.
 */
function avatarHtml(string $name, int $size = 36, ?string $title = null): string {
    $bg = gradientFor($name);
    $initials = htmlspecialchars(initialsFor($name));
    $font = max(12, (int) round($size * 0.42));
    $titleAttr = $title !== null ? 'title="' . htmlspecialchars($title) . '"' : '';
    return "<span class='lp-avatar' $titleAttr style=\"display:inline-flex;align-items:center;justify-content:center;width:{$size}px;height:{$size}px;border-radius:50%;background:$bg;color:#fff;font-weight:700;font-size:{$font}px;line-height:1;letter-spacing:-0.02em;text-shadow:0 1px 2px rgba(0,0,0,.25);box-shadow:0 4px 14px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.18);\">$initials</span>";
}
