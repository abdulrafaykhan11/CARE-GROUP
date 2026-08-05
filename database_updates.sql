-- Care Connect directory and appointment updates.
-- Run this once in the `care` database.

UPDATE users
SET full_name='Admin',
    password='$2y$10$lFBwUgat0rEAEun7bms7O.W373irf3RvjT4c3aBmjBwWCn0.5ZfU.',
    role='Admin',
    status='Active'
WHERE email='admin@gmail.com';

INSERT INTO users (full_name,email,phone,password,role,status)
SELECT 'Admin','admin@gmail.com','03000000000','$2y$10$lFBwUgat0rEAEun7bms7O.W373irf3RvjT4c3aBmjBwWCn0.5ZfU.','Admin','Active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='admin@gmail.com');

ALTER TABLE appointments
  ADD COLUMN IF NOT EXISTS reschedule_reason VARCHAR(255) NULL AFTER notes,
  ADD COLUMN IF NOT EXISTS rescheduled_by ENUM('Patient','Doctor') NULL AFTER reschedule_reason,
  ADD COLUMN IF NOT EXISTS rescheduled_at TIMESTAMP NULL AFTER rescheduled_by;

ALTER TABLE appointments
  ADD COLUMN IF NOT EXISTS symptom_photo_path VARCHAR(255) NULL AFTER notes;

CREATE TABLE IF NOT EXISTS specialization_guides (
  guide_id INT AUTO_INCREMENT PRIMARY KEY,
  specialization_id INT NOT NULL UNIQUE,
  overview TEXT NOT NULL,
  when_to_book TEXT DEFAULT NULL,
  care_points TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT specialization_guides_ibfk_1 FOREIGN KEY (specialization_id) REFERENCES specializations(specialization_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS specialization_faqs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO specializations (specialization_name, description, status)
SELECT name, description, 'Active'
FROM (
  SELECT 'Internal Medicine' name, 'Adult medicine, diabetes, blood pressure, infections, fever, weakness, and complex medical conditions.' description UNION ALL
  SELECT 'Family Medicine', 'First-contact care for adults, children, preventive checkups, chronic disease follow-up, and referrals.' UNION ALL
  SELECT 'ENT', 'Ear, nose, throat, sinus, hearing, tonsil, voice, allergy, and balance concerns.' UNION ALL
  SELECT 'Ophthalmology', 'Eye health, vision problems, cataract, glaucoma, retina, infections, and eye emergencies.' UNION ALL
  SELECT 'Dentistry', 'Teeth, gums, oral pain, cleaning, fillings, braces, implants, and dental surgery.' UNION ALL
  SELECT 'Gastroenterology', 'Stomach, liver, pancreas, bowel, acidity, hepatitis, IBS, and digestive endoscopy care.' UNION ALL
  SELECT 'Pulmonology', 'Lungs, asthma, COPD, cough, chest infection, breathing difficulty, and sleep breathing problems.' UNION ALL
  SELECT 'Urology', 'Kidney stones, urinary issues, prostate, male reproductive health, and urologic surgery.' UNION ALL
  SELECT 'Nephrology', 'Kidney disease, dialysis care, high creatinine, protein in urine, and kidney-related blood pressure.' UNION ALL
  SELECT 'Endocrinology', 'Diabetes, thyroid, hormones, obesity, PCOS-related hormones, and metabolic disorders.' UNION ALL
  SELECT 'Psychiatry', 'Depression, anxiety, sleep, mood, addiction, psychosis, and medication-based mental healthcare.' UNION ALL
  SELECT 'Psychology', 'Therapy, counseling, stress, anxiety, behavior, relationship, and emotional health support.' UNION ALL
  SELECT 'Oncology', 'Cancer diagnosis support, chemotherapy planning, follow-up, symptom control, and referrals.' UNION ALL
  SELECT 'Rheumatology', 'Arthritis, autoimmune disease, joint swelling, lupus, gout, and long-term inflammatory pain.' UNION ALL
  SELECT 'Hematology', 'Blood disorders, anemia, clotting, bleeding, platelets, and blood cancers.' UNION ALL
  SELECT 'General Surgery', 'Appendix, gallbladder, hernia, wounds, breast lumps, piles, and surgical consultation.' UNION ALL
  SELECT 'Neurosurgery', 'Brain and spine surgery, head injury, disc problems, tumors, and nerve compression.' UNION ALL
  SELECT 'Cardiac Surgery', 'Heart bypass, valve surgery, congenital heart surgery, and post-surgical cardiac follow-up.' UNION ALL
  SELECT 'Plastic Surgery', 'Reconstructive surgery, burns, scars, hand injuries, cosmetic procedures, and wound repair.' UNION ALL
  SELECT 'Radiology', 'Ultrasound, X-ray, CT, MRI, mammography, and image-guided diagnosis.' UNION ALL
  SELECT 'Pathology', 'Lab test interpretation, biopsy reports, blood tests, and disease diagnosis support.' UNION ALL
  SELECT 'Nutrition', 'Diet plans for weight, diabetes, blood pressure, pregnancy, child nutrition, and medical conditions.' UNION ALL
  SELECT 'Physiotherapy', 'Pain rehabilitation, sports injury recovery, mobility, posture, stroke rehab, and strengthening.' UNION ALL
  SELECT 'Emergency Medicine', 'Urgent care for sudden illness, injury, chest pain, breathing trouble, and acute symptoms.' UNION ALL
  SELECT 'Infectious Diseases', 'Complicated infections, fever, tuberculosis, hepatitis, dengue, malaria, and antibiotic guidance.'
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM specializations s WHERE s.specialization_name = seed.name
);

INSERT INTO clinics (city_id, clinic_name, phone, email, address, status)
SELECT city_id, clinic_name, phone, email, address, 'Active'
FROM (
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1) city_id, 'Aga Khan University Hospital' clinic_name, '021-111-911-911' phone, 'info@aku.edu' email, 'Stadium Road, Karachi' address UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Liaquat National Hospital', '021-111-456-456', 'info@lnh.edu.pk', 'National Stadium Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Jinnah Postgraduate Medical Centre', '021-99201300', '', 'Rafiqui H.J. Shaheed Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Dr. Ruth K. M. Pfau Civil Hospital Karachi', '021-99215960', '', 'Baba-e-Urdu Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'The Indus Hospital Korangi', '021-111-111-880', 'info@tih.org.pk', 'Korangi Crossing, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'SIUT Karachi', '021-111-000-313', 'info@siut.org', 'Civil Hospital premises, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Ziauddin Hospital Clifton', '021-35862937', 'info@zu.edu.pk', 'Clifton, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Ziauddin Hospital North Nazimabad', '021-36648237', 'info@zu.edu.pk', 'North Nazimabad, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Ziauddin Hospital Kemari', '021-32851881', 'info@zu.edu.pk', 'Kemari, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'South City Hospital', '021-35862301', 'info@southcityhospital.org', 'Clifton, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Patel Hospital', '021-111-174-174', 'info@patel-hospital.org.pk', 'Gulshan-e-Iqbal, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Tabba Heart Institute', '021-111-844-844', 'info@tabbaheart.org', 'Federal B Area, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'NICVD Karachi', '021-99201271', 'info@nicvd.org', 'Rafiqui H.J. Shaheed Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'National Institute of Child Health', '021-99201261', '', 'Rafiqui H.J. Shaheed Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Sindh Government Qatar Hospital', '021-36619846', '', 'Orangi Town, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Abbasi Shaheed Hospital', '021-99260300', '', 'Nazimabad, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Lady Dufferin Hospital', '021-32732791', 'info@ladydufferinhospital.org', 'M.A. Jinnah Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Memon Medical Institute Hospital', '021-34691147', 'info@mmi.edu.pk', 'Safoora Goth, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Darul Sehat Hospital', '021-34662001', 'info@dsh.edu.pk', 'Gulistan-e-Jauhar, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Saifee Hospital', '021-36789400', 'info@saifeehospital.com.pk', 'North Nazimabad, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'OMI Hospital', '021-111-664-111', 'info@omi-hospital.com', 'Depot Lines, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Altamash General Hospital', '021-111-111-456', 'info@altamashhospital.com', 'Clifton, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Burhani Hospital', '021-32214459', 'info@burhanihospital.org.pk', 'Garikhata, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Hill Park General Hospital', '021-34555591', '', 'Shaheed-e-Millat Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Medicare Cardiac and General Hospital', '021-111-456-789', 'info@medicarehospital.pk', 'Shaheed-e-Millat Road, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Mamji Hospital', '021-36804777', 'info@mamjihospital.com', 'Federal B Area, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Baqai Medical University Hospital', '021-34410293', 'info@baqai.edu.pk', 'Super Highway, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Creek General Hospital', '021-35292600', 'info@cgh.com.pk', 'Korangi Creek, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'PNS Shifa Hospital', '021-48506511', '', 'DHA, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'Hashmanis Hospital', '021-111-123-151', 'info@hashmanis.com.pk', 'Saddar, Karachi' UNION ALL
  SELECT (SELECT city_id FROM cities WHERE LOWER(city_name)='karachi' LIMIT 1), 'LRBT Korangi Hospital', '021-35114149', 'info@lrbt.org.pk', 'Korangi, Karachi'
) seed
WHERE seed.city_id IS NOT NULL
AND NOT EXISTS (
  SELECT 1 FROM clinics c WHERE c.clinic_name = seed.clinic_name AND c.city_id = seed.city_id
);

INSERT INTO specialization_guides (specialization_id, overview, when_to_book, care_points)
SELECT s.specialization_id, seed.overview, seed.when_to_book, seed.care_points
FROM specializations s
JOIN (
  SELECT 'Neurology' name, 'Neurologists manage brain, spine, nerve, headache, seizure, stroke, memory, movement, numbness, and weakness problems.' overview, 'Severe or repeated headaches\nFits or seizures\nWeakness, numbness, or tingling\nStroke symptoms\nMemory loss, tremors, or balance issues' when_to_book, 'Bring MRI, CT, EEG, or previous reports if available\nWrite down episode timing and triggers\nSudden stroke symptoms need emergency care' care_points UNION ALL
  SELECT 'Cardiology', 'Cardiologists diagnose and treat heart, blood pressure, cholesterol, chest pain, rhythm, valve, and heart failure concerns.', 'Chest pain or pressure\nShortness of breath with walking\nHigh blood pressure or cholesterol\nPalpitations, dizziness, or fainting\nFamily history of heart disease', 'Bring ECG, echo, lipid profile, and BP records\nList current medicines clearly\nSevere chest pain needs emergency care' UNION ALL
  SELECT 'Dermatology', 'Dermatologists treat skin, hair, nail, allergy, acne, pigmentation, eczema, psoriasis, fungal infections, and cosmetic skin concerns.', 'Acne or acne scars\nHair fall or dandruff\nRashes, itching, or allergies\nMoles or changing skin spots\nPigmentation and cosmetic concerns', 'Bring photos of flare-ups if symptoms come and go\nList creams and medicines already used\nAvoid strong steroid creams without advice' UNION ALL
  SELECT 'Gynecology', 'Gynecologists care for menstrual health, pregnancy, fertility, infections, contraception, PCOS, menopause, and women reproductive health.', 'Pregnancy care\nIrregular or painful periods\nPCOS or fertility concerns\nPelvic pain or discharge\nMenopause symptoms', 'Bring ultrasound and lab reports\nTrack cycle dates before appointment\nUrgent bleeding or severe pain needs emergency care' UNION ALL
  SELECT 'Pediatrics', 'Pediatricians care for babies, children, and teenagers including fever, infections, growth, nutrition, allergies, asthma, and vaccinations.', 'Child fever or cough\nVaccination and growth checks\nPoor feeding or weight gain\nAllergy or asthma symptoms\nStomach pain, vomiting, or diarrhea', 'Bring vaccination card and previous prescriptions\nNote temperature readings and symptoms\nNewborn fever needs urgent care' UNION ALL
  SELECT 'Orthopedics', 'Orthopedic doctors treat bones, joints, muscles, ligaments, fractures, back pain, arthritis, sports injuries, and mobility problems.', 'Fracture or injury\nBack, knee, shoulder, or neck pain\nArthritis and joint stiffness\nSports injuries\nSwelling or movement difficulty', 'Bring X-ray or MRI reports if available\nAvoid heavy activity after injury\nNumbness with weakness needs urgent review' UNION ALL
  SELECT 'ENT', 'ENT specialists treat ear, nose, throat, sinus, tonsil, hearing, allergy, voice, snoring, and balance problems.', 'Ear pain or discharge\nBlocked nose or sinus pain\nTonsil or throat infections\nHearing loss or ringing\nVertigo or balance issues', 'Bring hearing tests if available\nMention allergy triggers\nSevere breathing or swallowing difficulty is urgent' UNION ALL
  SELECT 'Gastroenterology', 'Gastroenterologists treat acidity, stomach pain, liver disease, hepatitis, bowel problems, constipation, diarrhea, IBS, and digestive bleeding.', 'Persistent acidity or stomach pain\nBlood in stool or vomiting\nHepatitis or abnormal liver tests\nLong-term diarrhea or constipation\nUnexplained weight loss', 'Bring liver tests and ultrasound reports\nList painkillers or antibiotics used\nBlack stool or vomiting blood needs emergency care' UNION ALL
  SELECT 'Pulmonology', 'Pulmonologists treat lungs, asthma, COPD, chronic cough, pneumonia, TB-related concerns, breathing difficulty, and sleep breathing problems.', 'Long cough or wheezing\nShortness of breath\nAsthma or COPD follow-up\nChest infection symptoms\nPossible sleep apnea', 'Bring chest X-ray, CT, and spirometry reports\nMention smoking or exposure history\nSevere breathing difficulty needs emergency care' UNION ALL
  SELECT 'Urology', 'Urologists treat kidney stones, urinary infections, prostate problems, urinary leakage, male reproductive concerns, and urologic surgery needs.', 'Burning urine or repeated UTI\nKidney stone pain\nBlood in urine\nProstate symptoms\nMale fertility or sexual health concerns', 'Bring urine tests, ultrasound, or CT KUB reports\nDrink water unless restricted by doctor\nSevere flank pain with fever is urgent'
) seed ON seed.name = s.specialization_name
WHERE NOT EXISTS (SELECT 1 FROM specialization_guides g WHERE g.specialization_id = s.specialization_id);

INSERT INTO specialization_faqs (specialization_id, question, answer, sort_order)
SELECT s.specialization_id, seed.question, seed.answer, seed.sort_order
FROM specializations s
JOIN (
  SELECT 'Neurology' name, 'Is migraine a neurology issue?' question, 'Yes, recurrent or disabling headache is commonly managed by neurologists.' answer, 1 sort_order UNION ALL
  SELECT 'Neurology', 'What symptoms are urgent?', 'Sudden face droop, arm weakness, speech trouble, seizure, or severe new headache needs emergency care.', 2 UNION ALL
  SELECT 'Cardiology', 'When should I see a cardiologist?', 'Book a visit for chest pain, breathlessness, irregular heartbeat, high BP, or strong family history.', 1 UNION ALL
  SELECT 'Cardiology', 'Can I book for prevention?', 'Yes, prevention visits help manage blood pressure, cholesterol, diabetes, smoking, and weight-related risk.', 2 UNION ALL
  SELECT 'Dermatology', 'Do dermatologists treat hair fall?', 'Yes, they evaluate scalp, hormones, deficiencies, infections, and pattern hair loss.', 1 UNION ALL
  SELECT 'Dermatology', 'Should I stop creams before visiting?', 'Bring all products and prescriptions; avoid starting strong steroid creams without advice.', 2 UNION ALL
  SELECT 'Gynecology', 'Can I book for pregnancy checkup?', 'Yes, gynecologists provide antenatal care and pregnancy-related guidance.', 1 UNION ALL
  SELECT 'Gynecology', 'Is PCOS treated by gynecologists?', 'Yes, PCOS is commonly managed with lifestyle advice, medicines, and follow-up tests.', 2 UNION ALL
  SELECT 'Pediatrics', 'Do pediatricians handle vaccines?', 'Yes, they guide routine immunization schedules and catch-up vaccines.', 1 UNION ALL
  SELECT 'Pediatrics', 'When is fever urgent?', 'Newborn fever, breathing difficulty, dehydration, seizure, or extreme drowsiness needs urgent care.', 2 UNION ALL
  SELECT 'Orthopedics', 'Do I need an X-ray first?', 'The doctor can decide after examination; bring any existing reports if you already have them.', 1 UNION ALL
  SELECT 'Orthopedics', 'Can orthopedics treat back pain?', 'Yes, orthopedic specialists commonly evaluate spine and musculoskeletal pain.', 2 UNION ALL
  SELECT 'ENT', 'Can ENT treat sinus and allergy?', 'Yes, ENT doctors commonly manage sinusitis, nasal blockage, allergy symptoms, and throat issues.', 1 UNION ALL
  SELECT 'Gastroenterology', 'When should acidity be checked?', 'Persistent acidity, swallowing difficulty, weight loss, vomiting, or bleeding symptoms should be assessed.', 1 UNION ALL
  SELECT 'Pulmonology', 'Is chronic cough a lung issue?', 'A cough lasting weeks, wheeze, chest tightness, or breathlessness should be reviewed by a pulmonologist.', 1 UNION ALL
  SELECT 'Urology', 'Do urologists treat kidney stones?', 'Yes, urologists diagnose and manage kidney stones, urinary blockage, and related pain.', 1
) seed ON seed.name = s.specialization_name
WHERE NOT EXISTS (
  SELECT 1 FROM specialization_faqs f
  WHERE f.specialization_id = s.specialization_id AND f.question = seed.question
);
