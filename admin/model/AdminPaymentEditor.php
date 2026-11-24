<?php

/** Persistance et validation des corrections de paiement réservées à l'administration. */
final class AdminPaymentEditor
{
    private const PAYMENT_METHODS = ['CB', 'Virement', 'Prelevement', 'Especes'];

    public function update(PDO $pdo, int $paymentId, string $amount, string $date, string $method): void
    {
        if ($paymentId < 1) {
            throw new InvalidArgumentException('Le paiement sélectionné est invalide.');
        }

        $amountValue = filter_var($amount, FILTER_VALIDATE_FLOAT);
        if ($amountValue === false || $amountValue <= 0 || $amountValue > 999999.99) {
            throw new InvalidArgumentException('Le montant doit être compris entre 0,01 € et 999 999,99 €.');
        }
        if (!in_array($method, self::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('Sélectionnez un moyen de paiement valide.');
        }

        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $date);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if ($dateValue === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $dateValue->format('Y-m-d\\TH:i') !== $date
            || $dateValue > new DateTimeImmutable('now')) {
            throw new InvalidArgumentException('La date du paiement doit être valide et ne pas être dans le futur.');
        }

        $pdo->beginTransaction();
        try {
            $find = $pdo->prepare('SELECT Numpaiement FROM paiement WHERE Numpaiement = ? FOR UPDATE');
            $find->execute([$paymentId]);
            if (!$find->fetchColumn()) {
                throw new RuntimeException('Ce paiement est introuvable.');
            }

            $update = $pdo->prepare(
                'UPDATE paiement SET montant = ?, date_paiement = ?, moyen_paiement = ? WHERE Numpaiement = ?'
            );
            $update->execute([number_format((float) $amountValue, 2, '.', ''), $dateValue->format('Y-m-d H:i:s'), $method, $paymentId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
