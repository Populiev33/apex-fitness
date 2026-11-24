-- Accélère le chargement de la liste des adhérents et de leur dernier abonnement/paiement.
ALTER TABLE souscrire
  ADD INDEX idx_souscrire_member_latest (NumUsers, date_debut, Numabo);

ALTER TABLE paiement
  ADD INDEX idx_paiement_member_latest (NumUsers, date_paiement, Numpaiement);
