<?php
session_start();
require __DIR__ . '/db.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$news = db()->query("
    SELECT * FROM news
    WHERE is_active = 1
    ORDER BY news_date DESC, sort_order ASC, id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости — ОАО "Исток"</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>

    <div class="head head-news"></div>

    <div class="body">
        <div class="news-page-container">
            <h1 class="news-page-title">Новости компании</h1>

            <div class="news-page-list">

                <?php if (!$news): ?>
                    <div class="empty-reviews">
                        Новостей пока нет. Заходите позже.
                    </div>
                <?php else: ?>
                    <?php foreach ($news as $i => $n): ?>
                        <?php $nid = 'news-' . (int)$n['id']; ?>
                        <article class="news-page-item" id="<?= $nid ?>">
                            <div class="news-page-header">
                                <?php if (!empty($n['image'])): ?>
                                    <img src="<?= e($n['image']) ?>"
                                         alt="<?= e($n['title']) ?>"
                                         class="news-page-img"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>

                                <div class="news-page-meta">
                                    <span class="news-page-date">
                                        <?= e(date('d.m.Y', strtotime($n['news_date']))) ?>г
                                    </span>
                                    <h2 class="news-page-heading"><?= e($n['title']) ?></h2>

                                    <?php if (!empty($n['preview'])): ?>
                                        <p class="news-page-preview"><?= e($n['preview']) ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($n['full_text'])): ?>
                                        <button class="news-page-toggle"
                                                onclick="toggleNews('<?= $nid ?>-full', this)">
                                            Читать полностью <span class="arrow">▼</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($n['full_text'])): ?>
                                <div class="news-page-full" id="<?= $nid ?>-full">
                                    <p><?= nl2br(e($n['full_text'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="podval"></div>

<?php require 'footer.php'; ?>

<script>
    function toggleNews(id, btn) {
        const block = document.getElementById(id);
        const isOpen = block.classList.contains('open');

        if (isOpen) {
            block.classList.remove('open');
            btn.innerHTML = 'Читать полностью <span class="arrow">▼</span>';
        } else {
            block.classList.add('open');
            btn.innerHTML = 'Свернуть <span class="arrow">▲</span>';
        }
    }
</script>
</body>
</html>