<?php
define('SECRET_CODE', 'SUPER-SECRET'); // I recommend using: https://www.random.org/passwords/?num=1&len=24&format=html&rnd=new
define('ACCESS_TOKEN', 'YOUR_GROUPME_ACCESS_TOKEN');
define('CHANNEL_ID', '45617130'); // UNT Robotics Exec channel id
define('API_URL', 'https://api.groupme.com/v3/groups/' . CHANNEL_ID . '/messages?token=' . ACCESS_TOKEN);
?>
