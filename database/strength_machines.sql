CREATE TABLE strength_machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    weight_stack_kg INT,                 -- Poids de la pile (en kg)
    max_user_weight_kg INT,              -- Poids max de l'utilisateur
    dimensions VARCHAR(255),             -- Dimensions (L x l x H)
    adjustment_range VARCHAR(255),       -- Plage de réglage (ex: 0-15 niveaux)
    has_adjustable_seat BOOLEAN DEFAULT FALSE,
    has_adjustable_backrest BOOLEAN DEFAULT FALSE,
    has_digital_display BOOLEAN DEFAULT FALSE,
    muscle_groups_targeted TEXT,         -- Groupes musculaires ciblés
    color_options VARCHAR(255),          -- Options de couleur
    frame_material VARCHAR(100),         -- Matériau du cadre
    warranty_years INT,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);
