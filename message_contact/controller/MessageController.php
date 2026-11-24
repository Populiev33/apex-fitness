<?php

declare(strict_types=1);

require_once __DIR__ . '/../model/Message.php';
require_once __DIR__ . '/../model/EtatConversation.php';
require_once __DIR__ . '/MessageSuppressionController.php';

final class MessageController
{
    public function __construct(
        private ?Message $message = null,
        private ?MessageSuppressionController $suppression = null
    ) {
        $this->message ??= new Message();
        $this->suppression ??= new MessageSuppressionController();
    }

    public function getAll(int $userId): array
    {
        return $this->message->getAllFor($userId);
    }

    public function getAllThreads(int $userId): array
    {
        $messages = $this->getAll($userId);
        $messagesById = array_column($messages, null, 'IdMessage');
        $threads = [];
        foreach ($messages as $message) {
            $root = $message;
            while (!empty($root['IdMessage_parent']) && isset($messagesById[$root['IdMessage_parent']])) {
                $root = $messagesById[$root['IdMessage_parent']];
            }
            $threads[$root['IdMessage']][] = $message;
        }
        return array_values($threads);
    }

    /** Prépare la boîte de réception pour la vue de l'employé d'accueil. */
    public function getInbox(int $userId, int $selectedThreadId = 0): array
    {
        $conversations = [];
        foreach ($this->getAllThreads($userId) as $messages) {
            $root = $messages[0];
            $conversations[] = [
                'id' => (int) $root['IdMessage'],
                'messages' => $messages,
                'memberId' => (int) $root['NumUsers_emetteur'] === $userId
                    ? (int) $root['NumUsers_destinataire']
                    : (int) $root['NumUsers_emetteur'],
            ];
        }

        usort($conversations, static function (array $first, array $second): int {
            $lastFirst = $first['messages'][count($first['messages']) - 1];
            $lastSecond = $second['messages'][count($second['messages']) - 1];
            return [$lastSecond['date_envoi'], $lastSecond['IdMessage']]
                <=> [$lastFirst['date_envoi'], $lastFirst['IdMessage']];
        });

        $selected = null;
        foreach ($conversations as $index => $conversation) {
            if ($conversation['id'] === $selectedThreadId) {
                $selected = $index;
                break;
            }
        }
        if ($selected === null && $conversations) {
            $selected = 0;
        }

        // Ouvrir une discussion marque uniquement ses messages reçus comme lus.
        if ($selected !== null) {
            $ids = array_column($conversations[$selected]['messages'], 'IdMessage');
            $this->message->markAsRead($userId, $ids);
            foreach ($conversations[$selected]['messages'] as &$message) {
                if ((int) $message['NumUsers_destinataire'] === $userId) {
                    $message['lu'] = 1;
                }
            }
            unset($message);
        }

        $messagesRecus = 0;
        $conversationsEnAttente = 0;
        $conversationsAvecReponse = 0;
        foreach ($conversations as &$conversation) {
            $last = end($conversation['messages']);
            $conversation['dernierMessage'] = $last;
            $conversation['dateMembreDepuis'] = $this->message->getMemberSince($userId, $conversation['memberId']);
            $nonLus = 0;
            $aRepondu = false;
            foreach ($conversation['messages'] as $message) {
                if ((int) $message['NumUsers_destinataire'] === $userId) {
                    $messagesRecus++;
                    if (empty($message['lu'])) {
                        $nonLus++;
                    }
                }
                if ((int) $message['NumUsers_emetteur'] === $userId) {
                    $aRepondu = true;
                }
            }
            $conversation['nombreNonLus'] = $nonLus;
            $conversation['etat'] = FabriqueEtatConversation::creer($nonLus > 0);
            $conversation['enAttente'] = (int) $last['NumUsers_destinataire'] === $userId;
            if ($conversation['enAttente']) {
                $conversationsEnAttente++;
            }
            if ($aRepondu) {
                $conversationsAvecReponse++;
            }
        }
        unset($conversation);

        $nombreConversations = count($conversations);
        $selectedConversation = $selected !== null ? $conversations[$selected] : null;

        return [
            'conversations' => $conversations,
            'selected' => $selectedConversation,
            'stats' => [
                'messagesRecus' => $messagesRecus,
                'enAttente' => $conversationsEnAttente,
                'tauxReponse' => $nombreConversations > 0
                    ? (int) round($conversationsAvecReponse / $nombreConversations * 100)
                    : null,
            ],
        ];
    }

    public function set(int $userId, int $parentId, string $text): void
    {
        $this->message->setReply($userId, $parentId, $text);
    }

    public function setInitial(int $userId, string $text): void
    {
        $this->message->setInitial($userId, $text);
    }

    public function delete(int $userId, int $messageId): void
    {
        $this->suppression->supprimer($userId, $messageId);
    }
}
