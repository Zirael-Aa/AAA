
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<div class="pole1">
    <div class="dropdown-menu">
        <a href="index7.php">Ответы на вопросы</a>
        <a href="index9.php">Отзывы</a>
        <a href="index6.php">История</a>
    </div>
</div>

<div class="pole2">
    <div class="search-section">
        <div class="accessibility-info">
            <a href="#" class="accessibility-link" id="slepToggle">Версия для слабовидящих</a>
            <div class="phone-number">+7 (863)657-61-01</div>
        </div>

        <!-- ===== Поле поиска ===== -->
        <div class="site-search">
            <input type="text"
                   id="siteSearchInput"
                   placeholder="Поиск по сайту…"
                   autocomplete="off">
            <button type="button" id="siteSearchBtn" aria-label="Найти">
                <img src="фото/Лупа.png" alt="Поиск">
            </button>
        </div>
    </div>

    <div class="title-section">
        <h1 class="f">
            <a href="index.php"><img src="фото/Логотип.png" alt="Логотип"></a>
            Чистая вода
        </h1>
    </div>

      <div class="button3">
        <?php if (!empty($_SESSION['user']['id'])): ?>
            <?php
                $parts = preg_split('/\s+/u', trim($_SESSION['user']['name'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                $initials = '';
                if (count($parts) >= 2) {
                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8')
                              . mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'), 'UTF-8');
                } elseif (count($parts) === 1) {
                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'), 'UTF-8');
                }
                if ($initials === '') $initials = '?';
            ?>
            <a href="profile.php" class="user-avatar"
               title="<?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="user-avatar-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-avatar-label">Личный кабинет</span>
            </a>
        <?php else: ?>
            <a href="login.php"><img src="фото/Админка1.png" alt="">Личный кабинет</a>
        <?php endif; ?>

        <a href="index8.php"><img src="фото/тариф.png" alt="">Тарифы</a>
        <a href="index4.php"><img src="фото/водакран.png" alt="">Подключение</a>
        <a href="index5.php"><img src="фото/garantiya.png" alt="">Ремонт</a>
    </div>
</div>

<!-- ===== Версия для слабовидящих: загрузчик ===== -->
<script>
(function () {
    var STORAGE_KEY = 'slep_enabled';
    var btn = document.getElementById('slepToggle');
    if (!btn) return;

    var enabled = localStorage.getItem(STORAGE_KEY) === '1';

    function updateBtnLabel() {
        btn.textContent = enabled
            ? 'Обычная версия сайта'
            : 'Версия для слабовидящих';
    }
    updateBtnLabel();

    function loadScript(src, cb) {
        var s = document.createElement('script');
        s.src = src;
        s.onload = cb || function () {};
        document.head.appendChild(s);
    }

    function loadSlepAssets(callback) {
        loadScript('https://code.jquery.com/jquery-3.6.0.min.js', function () {
            window.$jq = window.jQuery.noConflict(true);

            loadScript('https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', function () {
                loadScript('https://lidrekon.ru/slep/js/jquery.js', function () {
                    loadScript('https://lidrekon.ru/slep/js/uhpv-full.min.js', function () {
                        if (callback) callback();
                    });
                });
            });
        });
    }

    function enableSlep() {
        localStorage.setItem(STORAGE_KEY, '1');
        enabled = true;
        updateBtnLabel();

        loadSlepAssets(function () {
            setTimeout(function () {
                var uhpvBtn = document.querySelector('.uhpv-full__icon, .uhpv__icon, [class*="uhpv"] button');
                if (uhpvBtn) {
                    uhpvBtn.click();
                } else {
                    location.reload();
                }
            }, 400);
        });
    }

    function disableSlep() {
        localStorage.setItem(STORAGE_KEY, '0');
        enabled = false;
        updateBtnLabel();

        Object.keys(localStorage).forEach(function (k) {
            if (/uhpv|slep|special|vision/i.test(k)) {
                if (k !== STORAGE_KEY) localStorage.removeItem(k);
            }
        });

        document.documentElement.classList.remove(
            'uhpv-full', 'uhpv', 'slep', 'special-version'
        );
        document.body.classList.remove(
            'uhpv-full', 'uhpv', 'slep', 'special-version'
        );
        document.documentElement.removeAttribute('style');
        document.body.removeAttribute('style');

        location.reload();
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (enabled) {
            disableSlep();
        } else {
            enableSlep();
        }
    });

    if (enabled) {
        loadSlepAssets(function () {
            setTimeout(function () {
                var uhpvBtn = document.querySelector('.uhpv-full__icon, .uhpv__icon, [class*="uhpv"] button');
                if (uhpvBtn) uhpvBtn.click();
            }, 400);
        });
    }
})();
</script>

<!-- ===== Поиск по сайту ===== -->
<script>
(function () {
    var input = document.getElementById('siteSearchInput');
    var btn   = document.getElementById('siteSearchBtn');
    if (!input || !btn) return;

    function doSearch() {
        var q = (input.value || '').trim();

        if (q.length < 2) {
            alert('Введите хотя бы 2 символа для поиска.');
            return;
        }

        btn.disabled = true;

        fetch('search.php?q=' + encodeURIComponent(q))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.results || data.results.length === 0) {
                    alert('Информация отсутствует.\nПо вашему запросу ничего не найдено.');
                    return;
                }
                window.location.href = data.results[0].url;
            })
            .catch(function () {
                alert('Не удалось выполнить поиск. Попробуйте позже.');
            })
            .finally(function () {
                btn.disabled = false;
            });
    }

    btn.addEventListener('click', doSearch);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            doSearch();
        }
    });
})();
</script>