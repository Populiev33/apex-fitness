<?php

if (!$maintenance && $action === 'repondre_message') {
    $conversation = (int) ($_POST['conversation'] ?? 0);
    try {
      $messageController->set($moi, (int) ($_POST['message_parent'] ?? 0), (string) ($_POST['message'] ?? ''));
      flash('succes', 'Réponse envoyée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe/espace.php?onglet=messages&conversation=' . $conversation);
  }
