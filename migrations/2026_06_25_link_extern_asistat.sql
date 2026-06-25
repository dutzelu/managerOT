-- Linkuri externe securizate pentru completarea datelor de catre beneficiari.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS asistat_external_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiar_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_by VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_external_token_hash (token_hash),
    INDEX idx_external_links_beneficiar (beneficiar_id),
    INDEX idx_external_links_status (expires_at, used_at, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistat_external_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    beneficiar_id INT NOT NULL,
    payload_json LONGTEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'nou',
    submitted_ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    applied_at DATETIME NULL,
    applied_by VARCHAR(190) NULL,
    notes TEXT NULL,
    INDEX idx_external_submissions_beneficiar (beneficiar_id),
    INDEX idx_external_submissions_link (link_id),
    INDEX idx_external_submissions_status (status, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
