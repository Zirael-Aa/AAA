<?php
session_start();
require __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Получаем только активные тарифы
$tariffs = db()->query("
    SELECT * FROM tariffs
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тарифы — ОАО «Исток»</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
    <div class="head head-tariffs"></div>

    <div class="body">
        <div class="tariffs-container">
            <h2 class="tariffs-title">Тарифы на услуги водоснабжения и водоотведения</h2>

            <?php if (!$tariffs): ?>
                <div class="empty-reviews" style="max-width:700px;margin:0 auto;">
                    Тарифы временно недоступны. Пожалуйста, зайдите позже.
                </div>
            <?php else: ?>
                <div class="tariffs-grid">
                    <?php foreach ($tariffs as $t): ?>
                        <div class="tariff-card">
                            <div class="tariff-header">
                                <h3 class="tariff-name"><?= e($t['name']) ?></h3>
                            </div>
                            <div class="tariff-details">
                                <div class="tariff-price">
                                    <span class="price"><?= number_format((float)$t['price'], 2, '.', '') ?> ₽</span>
                                    <span class="unit"><?= e($t['unit']) ?></span>
                                </div>
                                <div class="tariff-info">
                                    <?php if (!empty($t['description'])): ?>
                                        <p><?= e($t['description']) ?></p>
                                    <?php endif; ?>

                                    <?php
                                        $features = array_filter(
                                            array_map('trim', explode("\n", (string)$t['features'])),
                                            fn($v) => $v !== ''
                                        );
                                    ?>
                                    <?php if ($features): ?>
                                        <ul class="features">
                                            <?php foreach ($features as $f): ?>
                                                <li><?= e($f) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="tariffs-note">
                <p>* Тарифы утверждены с 1 января 2024 года. Возможны сезонные корректировки в соответствии с законодательством РФ.</p>
            </div>
        </div>
    </div>

    <div class="podval"></div>

<?php require 'footer.php'; ?>
    <script>
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });
    </script>
</body>
</html>