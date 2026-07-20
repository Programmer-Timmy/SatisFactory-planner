CREATE TABLE IF NOT EXISTS production_line_import_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    importing_production_lines_id INT NOT NULL,
    exporting_production_lines_id INT NOT NULL,
    items_id INT NOT NULL,
    requested_amount DECIMAL(12, 5) NOT NULL DEFAULT 0,
    assigned_amount DECIMAL(12, 5) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_import_source_item (importing_production_lines_id, exporting_production_lines_id, items_id),
    KEY idx_import_source_exporting_item (exporting_production_lines_id, items_id),
    KEY idx_import_source_item (items_id),
    CONSTRAINT fk_import_sources_importing_line FOREIGN KEY (importing_production_lines_id) REFERENCES production_lines(id) ON DELETE CASCADE,
    CONSTRAINT fk_import_sources_exporting_line FOREIGN KEY (exporting_production_lines_id) REFERENCES production_lines(id) ON DELETE CASCADE,
    CONSTRAINT fk_import_sources_item FOREIGN KEY (items_id) REFERENCES items(id) ON DELETE CASCADE
);