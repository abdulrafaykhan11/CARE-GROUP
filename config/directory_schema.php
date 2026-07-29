<?php
/** Adds DB-backed specialty guide and FAQ content for the public doctor directory. */
function ensureDirectorySchema(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS specialization_guides (
        guide_id INT AUTO_INCREMENT PRIMARY KEY,
        specialization_id INT NOT NULL UNIQUE,
        overview TEXT NOT NULL,
        when_to_book TEXT DEFAULT NULL,
        care_points TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT specialization_guides_ibfk_1 FOREIGN KEY (specialization_id) REFERENCES specializations(specialization_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS specialization_faqs (
        faq_id INT AUTO_INCREMENT PRIMARY KEY,
        specialization_id INT NOT NULL,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_specialization_faqs (specialization_id, status, sort_order),
        CONSTRAINT specialization_faqs_ibfk_1 FOREIGN KEY (specialization_id) REFERENCES specializations(specialization_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
