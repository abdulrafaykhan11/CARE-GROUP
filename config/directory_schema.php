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

    // Seed default active FAQs if none exist
    $checkFaqs = mysqli_query($conn, "SELECT COUNT(*) c FROM specialization_faqs");
    if ($checkFaqs && (int)(mysqli_fetch_assoc($checkFaqs)['c'] ?? 0) === 0) {
        mysqli_query($conn, "INSERT INTO specialization_faqs (specialization_id, question, answer, sort_order, status)
            SELECT s.specialization_id, seed.question, seed.answer, seed.sort_order, 'Active'
            FROM specializations s
            JOIN (
                SELECT 'Internal Medicine' name, 'What symptoms require an internal medicine consultation?' question, 'Adult symptoms such as persistent fever, uncontrolled blood pressure, fatigue, metabolic issues, or multiple co-existing conditions.' answer, 1 sort_order UNION ALL
                SELECT 'Internal Medicine', 'Should I bring my past blood work and prescriptions?', 'Yes, past lab reports, discharge summaries, and medication lists help the doctor assess your progress accurately.', 2 UNION ALL
                SELECT 'General Practice' name, 'What can a family doctor treat during a visit?', 'Routine checkups, blood pressure monitoring, diabetes screening, mild infections, and general medical advice.', 1 UNION ALL
                SELECT 'Cardiology', 'When should I consult a cardiologist?', 'Book a visit for chest pain, shortness of breath, irregular heartbeat, high BP, or strong family history of heart disease.', 1 UNION ALL
                SELECT 'Cardiology', 'Are preventive cardiac checkups available?', 'Yes, preventive consultations assess blood pressure, cholesterol, lifestyle factors, and cardiovascular risk.', 2 UNION ALL
                SELECT 'Dermatology', 'Do dermatologists treat hair fall and scalp issues?', 'Yes, they evaluate hair loss, scalp infections, hormonal causes, and recommend clinical treatments.', 1 UNION ALL
                SELECT 'Dermatology', 'Should I avoid applying creams before my appointment?', 'Bring all current creams and prescriptions with you; avoid starting strong steroid creams without medical advice.', 2 UNION ALL
                SELECT 'Gynecology', 'Can I book a consultation for pregnancy or PCOS care?', 'Yes, gynecologists manage routine antenatal care, PCOS, menstrual irregularities, and reproductive wellness.', 1 UNION ALL
                SELECT 'Pediatrics', 'Are routine child vaccinations handled in pediatric visits?', 'Yes, pediatricians provide official vaccination schedules and growth development checks.', 1 UNION ALL
                SELECT 'Orthopedics', 'When should I see an orthopedic specialist?', 'Consult for persistent joint pain, back or knee pain, bone fractures, sports injuries, or mobility limitations.', 1 UNION ALL
                SELECT 'ENT', 'What issues are managed by an ENT specialist?', 'Sinusitis, ear pain, hearing loss, nasal blockage, throat infections, tonsil issues, and balance disorders.', 1 UNION ALL
                SELECT 'Gastroenterology', 'When should acidity and stomach pain be checked?', 'Persistent heartburn, stomach pain, vomiting, unexpected weight loss, or digestive bleeding require specialist review.', 1 UNION ALL
                SELECT 'Pulmonology', 'What lung conditions require pulmonologist care?', 'Chronic cough, asthma, wheezing, shortness of breath, chest infections, or sleep apnea concerns.', 1 UNION ALL
                SELECT 'Urology', 'Do urologists treat kidney stones and infections?', 'Yes, urologists diagnose and treat kidney stones, urinary tract infections, prostate issues, and bladder health.', 1
            ) seed ON seed.name = s.specialization_name OR s.specialization_name LIKE CONCAT('%', seed.name, '%')");
    }
}
