<?php
session_start();
require __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$isLogged = !empty($_SESSION['user']['id']);

$flash     = '';
$flashType = '';

/* ====== СОХРАНЕНИЕ ОТЗЫВА ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {

    if (!$isLogged) {
        $_SESSION['flash']      = 'Чтобы оставить отзыв, войдите в личный кабинет.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index9.php');
        exit;
    }

    $rating  = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $_SESSION['flash']      = 'Поставьте оценку от 1 до 5 звёзд.';
        $_SESSION['flash_type'] = 'error';
    } elseif ($message === '') {
        $_SESSION['flash']      = 'Напишите текст отзыва.';
        $_SESSION['flash_type'] = 'error';
    } else {
        $stmt = db()->prepare("
            INSERT INTO reviews (user_id, rating, message, status)
            VALUES (?, ?, ?, 'published')
        ");
        $stmt->execute([$_SESSION['user']['id'], $rating, $message]);

        $_SESSION['flash']      = 'Спасибо! Ваш отзыв опубликован.';
        $_SESSION['flash_type'] = 'success';
    }

    header('Location: index9.php');
    exit;
}

/* ====== FLASH ====== */
$flash     = $_SESSION['flash']      ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_type']);

/* ====== СПИСОК ОТЗЫВОВ ====== */
$stmt = db()->query("
    SELECT r.id, r.rating, r.message, r.created_at, u.name
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.status = 'published'
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll();

/* ====== СТАТИСТИКА ====== */
$total = count($reviews);
$sum   = 0;
$dist  = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) {
    $sum += (int)$r['rating'];
    $dist[(int)$r['rating']]++;
}
$avg = $total ? round($sum / $total, 1) : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
    <div class="head head-reviews"></div>

    <div class="body">
        <div class="content-container">
            <div class="history">
                <h2>Обратная связь</h2>
                <div class="history-item">
                    <a href="index6.php">История</a>
                </div>
                <div class="history-item">
                    <a href="index7.php">Ответы на вопросы</a>
                </div>
                <div class="history-item">
                    <a href="index9.php">Отзывы</a>
                </div>
            </div>

            <div class="right">
                <br><br>

                <?php if ($flash): ?>
                    <div class="flash <?= $flashType === 'error' ? 'error' : '' ?>">
                        <?= e($flash) ?>
                    </div>
                <?php endif; ?>

                <?php if ($isLogged): ?>

                    <!-- ===== ФОРМА ОТЗЫВА (только для авторизованных) ===== -->
                    <div class="question-form-container">
                        <div class="zayavka-form">
                            <h3>Оставить отзыв</h3>
                            <form id="reviewForm" method="post">
                                <input type="hidden" name="action" value="add_review">
                                <input type="hidden" name="rating" id="rating-value" value="5">

                                <div class="form-group">
                                    <label>Ваша оценка:</label>
                                    <div class="rating-input" id="rating-input">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                        <span class="rating-value-label" id="rating-label">5</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="review-message">Ваш отзыв:</label>
                                    <textarea id="review-message" name="message"
                                              placeholder="Расскажите о качестве услуг..."
                                              required></textarea>
                                </div>

                                <button type="submit" class="submit-btn">Отправить отзыв</button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- ===== ГОСТЬ ===== -->
                    <div class="review-login-note">
                        <p>Чтобы оставить отзыв, войдите в личный кабинет или зарегистрируйтесь.</p>
                        <a href="login.php" class="btn-red">Войти</a>
                        <a href="registration.php" class="btn-outline">Регистрация</a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
<div class="otziv1">
                <h2>Отзывы наших клиентов</h2>

                <?php if ($total): ?>
                <div class="rating-summary">
                    <div class="rating-summary-left">
                        <div class="rating-big"><?= number_format($avg, 1, ',', '') ?></div>
                        <div class="rating-stars-big"><?= str_repeat('★', (int)round($avg)) . str_repeat('☆', 5 - (int)round($avg)) ?></div>
                        <div class="rating-total">на основе <?= $total ?> отзывов</div>
                    </div>
                    <div class="rating-summary-right">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar-row">
                                <span class="rating-bar-label"><?= $i ?> ★</span>
                                <div class="rating-bar">
                                    <div class="rating-bar-fill"
                                         style="width: <?= $total ? ($dist[$i] / $total * 100) : 0 ?>%"></div>
                                </div>
                                <span class="rating-bar-count"><?= $dist[$i] ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="reviews-container">
                    <?php if (!$reviews): ?>
                        <div class="empty-reviews">Пока нет отзывов. Будьте первым!</div>
                    <?php else: ?>
                        <?php
                        // Разбиваем отзывы на группы по 3 (для карусели)
                        $chunks = array_chunk($reviews, 3);
                        foreach ($chunks as $index => $chunk):
                        ?>
                            <div class="reviews-grid <?= $index === 0 ? '' : 'hidden' ?>">
                                <?php foreach ($chunk as $r): ?>
                                    <div class="review">
                                        <div class="review-header">
                                            <div class="review-avatar">
                                                <?= e(mb_substr($r['name'], 0, 2)) ?>
                                            </div>
                                            <div class="review-info">
                                                <h3><?= e($r['name']) ?></h3>
                                                <div class="rating-stars">
                                                    <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
                                                    <span class="rating-num"><?= (int)$r['rating'] ?>/5</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="review-text"><?= nl2br(e($r['message'])) ?></div>
                                        <div class="review-date">
                                            <?= e(date('d.m.Y', strtotime($r['created_at']))) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (count($chunks ?? []) > 1): ?>
                <div class="carousel-nav">
                    <button class="carousel-btn prev-btn">
                        <img src="фото/стрелка%20в%20лево.png" alt="Предыдущий">
                    </button>

                    <div class="carousel-indicators">
                        <?php foreach ($chunks as $i => $c): ?>
                            <div class="indicator <?= $i === 0 ? 'active' : '' ?>"
                                 data-slide="<?= $i ?>"></div>
                        <?php endforeach; ?>
                    </div>

                    <button class="carousel-btn next-btn">
                        <img src="фото/стрелка%20в%20право.png" alt="Следующий">
                    </button>
                </div>
                <?php endif; ?>
            </div>    
    </div>
   <div class="podval"></div>
<?php require 'footer.php'; ?>

<script>
/* ===== Карусель отзывов ===== */
document.addEventListener('DOMContentLoaded', function() {
    const slides     = document.querySelectorAll('.reviews-grid');
    const indicators = document.querySelectorAll('.carousel-nav .indicator');
    const prevBtn    = document.querySelector('.prev-btn');
    const nextBtn    = document.querySelector('.next-btn');

    if (!slides.length) return;

    let currentSlide = 0;
    let autoTimer    = null;

    function showSlide(index) {
        slides.forEach(s => s.classList.add('hidden'));
        indicators.forEach(i => i.classList.remove('active'));

        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.remove('hidden');
        if (indicators[currentSlide]) indicators[currentSlide].classList.add('active');
    }

    function startAuto() {
        if (slides.length < 2) return;
        autoTimer = setInterval(() => showSlide(currentSlide + 1), 5000);
    }
    function stopAuto() { clearInterval(autoTimer); }

    if (prevBtn) prevBtn.addEventListener('click', () => { showSlide(currentSlide - 1); stopAuto(); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { showSlide(currentSlide + 1); stopAuto(); startAuto(); });

    indicators.forEach((ind, i) => {
        ind.addEventListener('click', () => { showSlide(i); stopAuto(); startAuto(); });
    });

    startAuto();
});

/* ===== Звёздный рейтинг ===== */
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('rating-input');
    if (!container) return;

    const stars = container.querySelectorAll('.star');
    const input = document.getElementById('rating-value');
    const label = document.getElementById('rating-label');

    function paint(value) {
        stars.forEach(s => {
            s.classList.toggle('active', Number(s.dataset.value) <= value);
        });
    }
    paint(Number(input.value) || 5);

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            stars.forEach(s => s.classList.remove('hover'));
            const v = Number(star.dataset.value);
            stars.forEach(s => { if (Number(s.dataset.value) <= v) s.classList.add('hover'); });
        });
        star.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });
        star.addEventListener('click', () => {
            const v = Number(star.dataset.value);
            input.value = v;
            label.textContent = v;
            paint(v);
        });
    });
});
</script>
</body>
</html>