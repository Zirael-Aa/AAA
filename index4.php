<?php
session_start();
$isLogged = !empty($_SESSION['user']['id']);

// Формируем ссылки в зависимости от авторизации
$linkConnection = $isLogged
    ? 'profile.php?request_type=connection#add-request'
    : 'login.php?redirect=profile&request_type=connection';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Подключение </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
    <div class="head head-connect"></div>
    
    <div class="body">
      
        
        <div class="body-content">
            <div class="text">
                <h2>Подключение к системам водоснабжения и водоотведения</h2>
                <p>ОАО «Исток» осуществляет подключение объектов капитального строительства к централизованным системам холодного водоснабжения и водоотведения города Каменск-Шахтинский и прилегающих территорий.</p>
                
                <h2>Условия подключения</h2>
                <p>Подключение осуществляется при наличии технической возможности и после заключения договора о подключении. Максимальный срок подключения - 18 месяцев.</p>
                
                
                <div class="horizontal-blocks">
                    <div class="documents-block">
                        <h2>Необходимые документы</h2>
                        <p>• Заявление на подключение<br>
                           • Правоустанавливающие документы на объект<br>
                           • Ситуационный план с расположением объекта<br>
                           • Баланс водопотребления и водоотведения<br>
                           • Технические условия (при наличии)</p>
                    </div>
                    
                    <div class="procedure-block">
                        <h2>Порядок действий</h2>
                        <p>1. Подача заявления и документов<br>
                           2. Получение технических условий<br>
                           3. Заключение договора о подключении<br>
                           4. Выполнение технических условий<br>
                           5. Фактическое подключение объекта<br>
                           6. Подписание акта о подключении</p>
                    </div>
                </div>
                
                <h2>Стоимость подключения</h2>
                <p>Стоимость определяется на основании сметы, которая разрабатывается после обследования объекта и утверждается договором о подключении.</p>
            </div>
        </div>
        
       
    </div>
     <div class="poshinka-block">
        <a href="<?= htmlspecialchars($linkConnection) ?>" class="poshinka-voda">
            <div class="poshinka">
                <img src="фото/вoда.png" alt="">
                <p>Подать заявку на подключение</p>
            </div>
        </a>
        <a href="<?= htmlspecialchars($linkConnection) ?>" class="poshinka-kanaliz">
            <div class="poshinka">
                <img src="фото/канализация.png" alt="">
                <p>Получить технические условия</p>
            </div>
        </a>
    </div>
    <div class="podval"></div>
    
   <?php require 'footer.php'; ?> 
</body>
</html>