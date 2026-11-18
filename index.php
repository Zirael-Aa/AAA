<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чистая вода — ОАО «Исток»</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>

    <!-- ===== КАРУСЕЛЬ ===== -->
    <div class="carousel-container carousel-main">
        <div class="carousel-slide">
            <div class="carousel-item active">
                <img src="фото/фон.png" alt="Слайд 1">
            </div>
            <div class="carousel-item">
                <img src="фото/фон2%20банер.png" alt="Слайд 2">
            </div>
        </div>
        <button class="carousel-prev" aria-label="Назад">&#10094;</button>
        <button class="carousel-next" aria-label="Вперёд">&#10095;</button>
        <div class="carousel-indicators">
            <span class="indicator active" data-slide="0"></span>
            <span class="indicator" data-slide="1"></span>
        </div>
    </div>

    <!-- ===== ОСНОВНОЙ КОНТЕНТ ===== -->
    <div class="body">
        <div class="body-content">

            <!-- О нас -->
            <section class="left">
                <h2>О нас</h2>
                <div class="foto-left">
                    <img src="фото/Фото_о_нас.png" alt="О нас">
                </div>
                <p>Компания заключила 272 контракта на общую сумму 1,7 млрд руб. в роли заказчика и 1444 контракта на общую сумму 351,1 млн руб. в роли поставщика.</p>
                <p>Основной вид деятельности: забор и очистка воды для питьевых и промышленных нужд. Также зарегистрировано в категориях ОКВЭД: сбор и обработка сточных вод, передача электроэнергии и технологическое присоединение к распределительным электросетям, распределение воды для питьевых и промышленных нужд.</p>
            </section>

            <!-- Новости -->
            <section class="news">
                <h2>Новости</h2>

                <div class="news-list">
                    <article class="news-item">
                        <img src="фото/news1.png" alt="">
                        <div class="news-item-content">
                            <h3>С 13 февраля 2025г</h3>
                            <a href="news.php#news-1">МКР Лиховского будет с пониженным давлением 13.02.2025г с 9.00</a>
                        </div>
                    </article>

                    <article class="news-item">
                        <img src="фото/news2.png" alt="">
                        <div class="news-item-content">
                            <h3>С 10 февраля 2025г</h3>
                            <a href="news.php#news-2">Ватутина, 29, будет прекращена подача холодной воды по ул. Ватутина</a>
                        </div>
                    </article>

                    <article class="news-item">
                        <img src="фото/news3.png" alt="">
                        <div class="news-item-content">
                            <h3>С 4 февраля 2025г</h3>
                            <a href="news.php#news-3">Мусина — пер. Астаховский, будет прекращена подача холодной воды</a>
                        </div>
                    </article>
                </div>
            </section>

        </div>
    </div>

    <!-- ===== РЕКЛАМНЫЕ БЛОКИ ===== -->
    <div class="podval">
        <div class="reklama-container">
            <a href="#" class="reklama1">
                <h3>Предложение</h3>
                <span>Получи образование, подходящие специальности для ОАО "Исток"</span>
                <img src="фото/reklama1.png" alt="">
            </a>
            <a href="#" class="reklama2">
                <h3>Предложение</h3>
                <span>Трудоустройство на ОАО "Исток"</span>
                <img src="фото/reklama2.png" alt="">
            </a>
            <a href="#" class="reklama3">
                <h3>Проект</h3>
                <span>Новые технологии очистки воды</span>
                <img src="фото/reklama3.png" alt="">
            </a>
            <a href="#" class="reklama4">
                <h3>Проект</h3>
                <span>Экологические инициативы компании</span>
                <img src="фото/reklama4.png" alt="">
            </a>
        </div>
    </div>

<?php require 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const carousel = document.querySelector('.carousel-main');
        if (!carousel) return;

        const slides     = carousel.querySelectorAll('.carousel-item');
        const indicators = carousel.querySelectorAll('.carousel-main .indicator');
        const nextBtn    = carousel.querySelector('.carousel-next');
        const prevBtn    = carousel.querySelector('.carousel-prev');
        const totalSlides = slides.length;

        let currentSlide = 0;
        let autoSlideInterval = null;

        function showSlide(n) {
            slides.forEach(s => s.classList.remove('active'));
            indicators.forEach(i => i.classList.remove('active'));
            currentSlide = (n + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('active');
            indicators[currentSlide].classList.add('active');
        }

        function nextSlide() { showSlide(currentSlide + 1); }
        function prevSlide() { showSlide(currentSlide - 1); }
        function startAutoSlide() { stopAutoSlide(); autoSlideInterval = setInterval(nextSlide, 5000); }
        function stopAutoSlide() { if (autoSlideInterval) { clearInterval(autoSlideInterval); autoSlideInterval = null; } }

        nextBtn.addEventListener('click', () => { nextSlide(); startAutoSlide(); });
        prevBtn.addEventListener('click', () => { prevSlide(); startAutoSlide(); });

        indicators.forEach((ind, i) => {
            ind.addEventListener('click', () => { showSlide(i); startAutoSlide(); });
        });

        // Пауза при наведении (на десктопе)
        carousel.addEventListener('mouseenter', stopAutoSlide);
        carousel.addEventListener('mouseleave', startAutoSlide);

        // Пауза при касании (на мобильных)
        carousel.addEventListener('touchstart', stopAutoSlide, { passive: true });
        carousel.addEventListener('touchend',   startAutoSlide, { passive: true });

        startAutoSlide();
    });
</script>
</body>
</html>