-- Augmenter la taille du champ code_oaci pour supporter les codes ULM (ex: LF5954)
-- Les bases ULM ont des codes plus longs que les aérodromes OACI standards (4 caractères)

ALTER TABLE destinations MODIFY COLUMN code_oaci VARCHAR(10);
ALTER TABLE clubs MODIFY COLUMN code_oaci VARCHAR(10);