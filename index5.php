<?php
session_start();
$isLogged = !empty($_SESSION['user']['id']);

// Обе кнопки ведут на "Ремонт"
$linkRepair = $isLogged
    ? 'profile.php?request_type=repair#add-request'
    : 'login.php?redirect=profile&request_type=repair';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ремонт</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>

<div class="head head-repair"></div>
    
    <div class="body">
        
        <div class="text">
        <h2>Ремонт воды и канализации</h2>
       <p>Правильно оформленная заявка — залог оперативного и точного решения вашей проблемы. Прежде чем отправить запрос, пожалуйста, ознакомьтесь с информацией ниже. Это поможет нашему мастеру приехать к вам со всем необходимым оборудованием и материалами.</p>
       <p>Пожалуйста, укажите в заявке: тип проблемы, суть проблемы,конкретное место, доступность места.</p>
       <h2>Что это даст?</h2>
        <div class="horizontal-blocks">
            <div class="documents-block">
       <h2>Экономия времени:</h2><p> Мастер сразу поймет сложность работ.</p>
       </div>
       <div class="procedure-block">
       <h2>Правильная комплектация:</h2><p> Он привезет нужные инструменты и запчасти.</p>
    </div>
    <div class="procedure-block">
       <h2>Предварительная оценка:</h2><p> Мы сможем сориентировать вас по примерной стоимости и срокам.</p>
     </div>
    </div>
       <p>После получения заявки наш менеджер свяжется с вами в течение 15 минут для уточнения деталей и согласования времени выезда мастера.</p>
   
    </div>
    </div>
    <div class="remont-block">
        <a href="<?= htmlspecialchars($linkRepair) ?>" class="remont-voda">
            <div class="remont">
                <p>Ремонт воды</p>
            </div>
        </a>
        <a href="<?= htmlspecialchars($linkRepair) ?>" class="remont-kanaliz">
            <div class="remont">
                <p>Ремонт канализации</p>
            </div>
        </a>
    </div>
    
     <div class="podval"></div>
    
<?php require 'footer.php'; ?>
</body>
</html>