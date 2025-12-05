-- projet-medicare/sql/contraintes.sql
-- Contraintes et triggers pour MediCare

-- Contraintes CHECK
ALTER TABLE patients 
ADD CONSTRAINT chk_date_naissance 
CHECK (date_naissance < CURDATE());

ALTER TABLE rendez_vous 
ADD CONSTRAINT chk_date_rdv 
CHECK (date_heure > NOW());

ALTER TABLE medicaments 
ADD CONSTRAINT chk_stock 
CHECK (stock >= 0);

ALTER TABLE factures 
ADD CONSTRAINT chk_montant 
CHECK (montant > 0);

-- Contraintes UNIQUE
ALTER TABLE medecins 
ADD CONSTRAINT uniq_email_medecin 
UNIQUE (email);

-- Trigger pour mettre à jour le statut des rendez-vous passés
DELIMITER //
CREATE TRIGGER update_rdv_passed
BEFORE INSERT ON rendez_vous
FOR EACH ROW
BEGIN
    IF NEW.date_heure < NOW() THEN
        SET NEW.statut = 'passe';
    END IF;
END//
DELIMITER ;

-- Trigger pour journaliser les modifications importantes
DELIMITER //
CREATE TRIGGER log_patient_modification
AFTER UPDATE ON patients
FOR EACH ROW
BEGIN
    IF OLD.nom != NEW.nom OR OLD.prenom != NEW.prenom OR OLD.telephone != NEW.telephone THEN
        INSERT INTO historiques (action, table_concerne, id_concerne)
        VALUES ('Modification patient', 'patients', NEW.id);
    END IF;
END//
DELIMITER ;

-- Trigger pour mettre à jour le stock des médicaments
DELIMITER //
CREATE TRIGGER update_medic_stock
AFTER INSERT ON prescriptions
FOR EACH ROW
BEGIN
    UPDATE medicaments 
    SET stock = stock - 1 
    WHERE id = NEW.id_medicament AND stock > 0;
END//
DELIMITER ;

-- Index pour optimiser les performances
CREATE INDEX idx_patients_nom ON patients(nom, prenom);
CREATE INDEX idx_rendez_vous_date ON rendez_vous(date_heure);
CREATE INDEX idx_consultations_patient ON consultations(id_patient);
CREATE INDEX idx_medicaments_nom ON medicaments(nom);