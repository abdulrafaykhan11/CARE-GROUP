<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/gemini.php';

// Get raw JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
$userMessage = trim($input['message'] ?? '');
$chatHistory = $input['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Please ask a health concern, symptom, or doctor search query.'
    ]);
    exit;
}

$lowerMsg = strtolower($userMessage);

// ==========================================
// STAGE 1: GUARDRAIL / DOMAIN CHECK
// Reject obvious off-topic non-medical queries (e.g., 2+2, math, coding, sports, movies)
// ==========================================
$isMathQuery = preg_match('/^\s*(\d+\s*[\+\-\*\/xX\^]\s*\d+|\bwhat\s+is\s+\d+[\+\-\*\/]\d+|\b2\s*\+\s*2\b)/i', $userMessage);
$nonMedicalKeywords = [
    '2+2', '2 + 2', 'math', 'calculate', 'python', 'javascript', 'html', 'css', 'code', 
    'cricket', 'football', 'messi', 'ronaldo', 'movie', 'actor', 'president', 
    'politics', 'joke', 'bitcoin', 'crypto', 'game', 'gaming', 'playstation'
];

$isOffTopic = false;
if ($isMathQuery) {
    $isOffTopic = true;
} else {
    foreach ($nonMedicalKeywords as $kw) {
        if (strpos($lowerMsg, $kw) !== false) {
            $healthContext = ['health', 'doctor', 'hospital', 'patient', 'disease', 'symptom', 'medicine', 'pain', 'fever', 'clinic', 'treatment', 'headache', 'bp', 'heart', 'skin', 'eye', 'diet', 'care'];
            $hasHealth = false;
            foreach ($healthContext as $hc) {
                if (strpos($lowerMsg, $hc) !== false) {
                    $hasHealth = true;
                    break;
                }
            }
            if (!$hasHealth) {
                $isOffTopic = true;
                break;
            }
        }
    }
}

if ($isOffTopic) {
    echo json_encode([
        'status' => 'success',
        'reply' => "I am **CARE MediBot**, a specialized AI Healthcare & Medical Assistant. 🏥\n\nI am trained exclusively to assist with medical advice, health concerns, doctors, hospital services, and patient care on CARE Nexus.\n\nPlease ask me a health or medical-related question!",
        'source' => 'guardrail',
        'actions' => [
            ['label' => '🔍 Find a Doctor', 'url' => 'find_doctor.php'],
            ['label' => '🚨 Emergency Help', 'url' => 'index.php#contact']
        ]
    ]);
    exit;
}

// ==========================================
// STAGE 2: ADVANCED LOCAL MEDICAL & CLINICAL KNOWLEDGE MATRIX
// Map symptoms & intents to expert clinical advice & specializations
// ==========================================
$symptomMatrix = [
    'fever' => [
        'title' => 'Fever & Infection Guidance 🌡️',
        'spec' => 'Internal Medicine',
        'overview' => "Fever is usually your body's immune response to an infection (viral or bacterial). Common causes include flu, respiratory infections, dengue, or typhoid.",
        'advice' => [
            "Keep hydrated with plenty of water, fresh juices, or ORS.",
            "Rest adequately and monitor body temperature using a thermometer.",
            "Paracetamol can help reduce mild fever, but avoid taking antibiotics without a doctor's prescription.",
            "⚠️ **Urgent Warning:** Seek emergency care if fever exceeds 103°F (39.4°C), lasts over 3 days, or is accompanied by severe headache, stiff neck, or difficulty breathing."
        ]
    ],
    'headache' => [
        'title' => 'Headache & Neurological Guidance 🧠',
        'spec' => 'Neurology',
        'overview' => "Headaches can range from tension headaches and migraines to sinus inflammation or stress. Identifying triggers and patterns helps in proper treatment.",
        'advice' => [
            "Rest in a quiet, dark room and ensure good hydration.",
            "Track headache frequency, duration, and triggers (e.g., lack of sleep, screen time, skipped meals).",
            "⚠️ **Red Flag Warning:** Seek immediate emergency evaluation if you experience a sudden severe 'thunderclap' headache, vision changes, confusion, or weakness on one side."
        ]
    ],
    'chest pain' => [
        'title' => 'Chest Pain & Cardiac Care 🫀',
        'spec' => 'Cardiology',
        'overview' => "Chest discomfort can be muscular, acid reflux related, or a sign of cardiac concern. Severe chest pressure should always be taken seriously.",
        'advice' => [
            "Avoid strenuous activity immediately and sit comfortably.",
            "If chest pain is mild and linked to acidity, an antacid may help.",
            "🚨 **CRITICAL EMERGENCY:** If chest pain feels like heavy pressure or squeezes, radiates to arm, shoulder, jaw, or is accompanied by shortness of breath, call **+1 (800) 123-4567** or go to Emergency immediately!"
        ]
    ],
    'heart' => [
        'title' => 'Cardiovascular & Heart Health 🫀',
        'spec' => 'Cardiology',
        'overview' => "Cardiology focuses on blood pressure, heart rhythm, cholesterol, chest pain, and heart disease prevention.",
        'advice' => [
            "Monitor blood pressure and blood lipid levels regularly.",
            "Adopt a heart-healthy diet low in sodium and saturated fats.",
            "Schedule routine checkups with a verified Cardiologist if you have high BP or family history of heart disease."
        ]
    ],
    'stomach' => [
        'title' => 'Gastrointestinal & Stomach Care 🩺',
        'spec' => 'Gastroenterology',
        'overview' => "Stomach pain, heartburn, bloating, or digestive issues can stem from hyperacidity, gastritis, food intolerance, IBS, or infections.",
        'advice' => [
            "Eat smaller, frequent meals and avoid spicy, oily, or fried foods.",
            "Stay hydrated and avoid lying down immediately after eating.",
            "⚠️ Seek medical review if stomach pain is severe, persistent, or accompanied by vomiting or blood in stool."
        ]
    ],
    'skin' => [
        'title' => 'Skin, Hair & Allergy Guidance 🧴',
        'spec' => 'Dermatology',
        'overview' => "Dermatology covers skin rashes, acne, eczema, fungal infections, hair fall, and allergic reactions.",
        'advice' => [
            "Avoid using strong steroid creams without a prescription as they can cause skin damage.",
            "Keep affected skin clean and moisturized with fragrance-free creams.",
            "Consult a verified Dermatologist for proper skin type assessment and medical treatment."
        ]
    ],
    'child' => [
        'title' => 'Pediatric & Child Health Care 👶',
        'spec' => 'Pediatrics',
        'overview' => "Pediatric care covers infant and child growth, fever, immunization, nutrition, and childhood infections.",
        'advice' => [
            "Keep vaccination records updated.",
            "Monitor child hydration, alertness, and feeding patterns closely.",
            "⚠️ **Urgent:** Newborn fever or severe lethargy in toddlers requires urgent pediatric consultation."
        ]
    ],
    'bone' => [
        'title' => 'Orthopedic & Joint Health 🦴',
        'spec' => 'Orthopedics',
        'overview' => "Orthopedic specialists evaluate bone fractures, back pain, knee joint stiffness, arthritis, and mobility concerns.",
        'advice' => [
            "Apply cold compresses for acute swelling; warm compresses for chronic stiffness.",
            "Maintain proper posture during work and daily activities.",
            "Consult an Orthopedic doctor for persistent joint pain or post-injury rehabilitation."
        ]
    ],
    'back pain' => [
        'title' => 'Spine & Back Pain Care 🦴',
        'spec' => 'Orthopedics',
        'overview' => "Back pain is often caused by muscle strain, disc degeneration, poor posture, or lack of core strength.",
        'advice' => [
            "Avoid heavy lifting and prolonged sitting without lumbar support.",
            "Engage in gentle posture stretches and core strengthening exercises.",
            "Seek specialist evaluation if pain radiates down legs or causes numbness."
        ]
    ],
    'pcos' => [
        'title' => 'Women\'s Health & PCOS Guidance 🌸',
        'spec' => 'Gynecology',
        'overview' => "Polycystic Ovary Syndrome (PCOS) and hormonal imbalances affect menstrual cycles, weight, skin, and fertility.",
        'advice' => [
            "Maintain a balanced diet rich in whole foods and low glycemic index carbohydrates.",
            "Regular physical activity helps manage insulin sensitivity.",
            "Consult a verified Gynecologist for hormonal blood panels and pelvic ultrasound evaluation."
        ]
    ],
    'eye' => [
        'title' => 'Ophthalmology & Vision Care 👁️',
        'spec' => 'Ophthalmology',
        'overview' => "Eye care includes vision correction, glaucoma, cataract evaluation, eye infections, and digital eye strain.",
        'advice' => [
            "Follow the 20-20-20 rule for digital screens (every 20 mins, look at something 20 feet away for 20 seconds).",
            "Never rub irritated eyes; use preservative-free lubricating drops if recommended.",
            "Sudden vision loss, severe eye pain, or flashes of light require immediate specialist attention."
        ]
    ],
    'cough' => [
        'title' => 'Pulmonology & Respiratory Care 🫁',
        'spec' => 'Pulmonology',
        'overview' => "Persistent cough, asthma, bronchitis, or breathlessness require respiratory evaluation.",
        'advice' => [
            "Avoid smoking and exposure to environmental dust or allergens.",
            "Use steam inhalation for chest congestion.",
            "⚠️ A cough lasting over 3 weeks or accompanied by coughing blood requires prompt Pulmonologist evaluation."
        ]
    ]
];

// Detect if user query matches any clinical symptom matrix
$matchedMatrix = null;

// Detect platform navigation intents
if (strpos($lowerMsg, 'book') !== false || strpos($lowerMsg, 'appointment') !== false) {
    if (!$matchedMatrix && empty($matchedDoctors)) {
        echo json_encode([
            'status' => 'success',
            'reply' => "### How to Book an Appointment on CARE Nexus 📅\n\nBooking a consultation with a verified medical specialist is quick and simple:\n\n1. Visit the [Find Doctor](find_doctor.php) portal.\n2. Filter by **Medical Specialization** (e.g., Cardiology, Neurology, Pediatrics) or **City**.\n3. Click on your preferred doctor's profile to view their clinic schedule.\n4. Select an available date & time slot and fill in your appointment reason.\n5. Click **Confirm Appointment Request**!\n\nYou can track all your visits from your Patient Dashboard.",
            'source' => 'website_knowledge',
            'actions' => [
                ['label' => '🔍 Find Doctor Now', 'url' => 'find_doctor.php'],
                ['label' => '👤 Patient Dashboard', 'url' => 'patient/dashboard.php']
            ]
        ]);
        exit;
    }
}

if (strpos($lowerMsg, 'register doctor') !== false || strpos($lowerMsg, 'join doctor') !== false || strpos($lowerMsg, 'doctor registration') !== false) {
    echo json_encode([
        'status' => 'success',
        'reply' => "### Join CARE Nexus as a Verified Doctor 👨‍⚕️\n\nIf you are a licensed medical practitioner looking to join our verified clinical network:\n\n1. Go to the [Doctor Registration](register_doctor.php) page.\n2. Provide your name, contact details, and PMDC registration number.\n3. Choose your medical specialization and primary city.\n4. Submit your profile for admin verification.\n\nOnce reviewed and approved, your profile and clinic slots will go live!",
        'source' => 'website_knowledge',
        'actions' => [
            ['label' => '👨‍⚕️ Doctor Registration', 'url' => 'register_doctor.php']
        ]
    ]);
    exit;
}

// Detect if user query matches any clinical symptom matrix
$matchedMatrix = null;
foreach ($symptomMatrix as $key => $data) {
    if (strpos($lowerMsg, $key) !== false) {
        $matchedMatrix = $data;
        break;
    }
}

// ==========================================
// STAGE 3: DATABASE RAG SEARCH FOR DOCTORS & CLINICS
// ==========================================
$websiteContext = "";
$matchedDoctors = [];
$matchedClinics = [];
$matchedSpecs = [];

if (isset($conn) && $conn) {
    // 1. Search Doctors
    $qDoc = mysqli_query($conn, "
        SELECT d.doctor_id, u.full_name, s.specialization_name, c.city_name, cl.clinic_name, d.consultation_fee, d.experience_years, d.qualification 
        FROM doctors d 
        JOIN users u ON u.user_id = d.user_id 
        JOIN specializations s ON s.specialization_id = d.specialization_id 
        JOIN cities c ON c.city_id = d.city_id 
        LEFT JOIN clinics cl ON cl.clinic_id = d.clinic_id 
        WHERE d.verification_status='Verified'
    ");
    if ($qDoc) {
        while ($rDoc = mysqli_fetch_assoc($qDoc)) {
            $docName = strtolower($rDoc['full_name']);
            $specName = strtolower($rDoc['specialization_name']);
            $cityName = strtolower($rDoc['city_name']);
            $clinicName = strtolower($rDoc['clinic_name'] ?? '');

            $isMatch = (strpos($lowerMsg, $docName) !== false) ||
                       (strpos($lowerMsg, $specName) !== false) ||
                       ($matchedMatrix && strtolower($matchedMatrix['spec']) === $specName) ||
                       (strpos($lowerMsg, $cityName) !== false && (strpos($lowerMsg, 'doctor') !== false || strpos($lowerMsg, 'dr') !== false || strpos($lowerMsg, 'specialist') !== false || strpos($lowerMsg, 'hospital') !== false)) ||
                       ($clinicName && strpos($lowerMsg, $clinicName) !== false);

            if ($isMatch) {
                $matchedDoctors[] = $rDoc;
            }
        }
    }

    // 2. Search Clinics
    $qClinic = mysqli_query($conn, "
        SELECT c.clinic_name, ci.city_name, c.phone, c.address 
        FROM clinics c 
        JOIN cities ci ON ci.city_id = c.city_id 
        WHERE c.status='Active'
    ");
    if ($qClinic) {
        while ($rClinic = mysqli_fetch_assoc($qClinic)) {
            if (strpos($lowerMsg, strtolower($rClinic['clinic_name'])) !== false || 
                strpos($lowerMsg, strtolower($rClinic['city_name'])) !== false) {
                $matchedClinics[] = $rClinic;
            }
        }
    }
}

// Build Context String for Gemini API
if (!empty($matchedDoctors)) {
    $websiteContext .= "\nVerified Doctors Available on CARE Nexus:\n";
    foreach (array_slice($matchedDoctors, 0, 5) as $md) {
        $websiteContext .= "- Dr. " . $md['full_name'] . " (" . $md['specialization_name'] . " in " . $md['city_name'] . ")\n";
        $websiteContext .= "  Clinic: " . ($md['clinic_name'] ?? 'Medical Center') . " | Fee: PKR " . number_format($md['consultation_fee']) . " | Experience: " . $md['experience_years'] . " yrs\n";
    }
}

// ==========================================
// STAGE 4: CALL GEMINI API (OR ADVANCED LOCAL AI SYNTHESIS)
// ==========================================
global $geminiSystemInstruction;

$promptWithContext = $geminiSystemInstruction . "\n\n" . $websiteContext . "\n\nUser Question: " . $userMessage;

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $promptWithContext]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 1024
    ]
];

$apiKey = GEMINI_API_KEY;

function callGeminiAPI($modelName, $apiKey, $payload) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// Attempt Gemini API call
$modelsToTry = [GEMINI_MODEL, 'gemini-2.0-flash-lite', 'gemini-1.5-pro'];
$botReply = "";
$source = "gemini_ai";

foreach ($modelsToTry as $m) {
    $apiResult = callGeminiAPI($m, $apiKey, $payload);
    if ($apiResult['code'] === 200 && isset($apiResult['data']['candidates'][0]['content']['parts'][0]['text'])) {
        $botReply = trim($apiResult['data']['candidates'][0]['content']['parts'][0]['text']);
        break;
    }
}

// ==========================================
// STAGE 5: ADVANCED SMART SYNTHESIS FALLBACK
// If Gemini API is rate limited or unavailable, provide deep, rich, accurate clinical answer
// ==========================================
if (empty($botReply)) {
    $source = "website_knowledge";
    
    if ($matchedMatrix) {
        $botReply = "### " . $matchedMatrix['title'] . "\n\n";
        $botReply .= $matchedMatrix['overview'] . "\n\n";
        $botReply .= "**Key Clinical Recommendations:**\n";
        foreach ($matchedMatrix['advice'] as $adv) {
            $botReply .= "• " . $adv . "\n";
        }
        
        if (!empty($matchedDoctors)) {
            $botReply .= "\n\n### Verified " . $matchedMatrix['spec'] . " Doctors Available:\n\n";
            foreach (array_slice($matchedDoctors, 0, 4) as $doc) {
                $botReply .= "• **Dr. {$doc['full_name']}** — *{$doc['specialization_name']}* ({$doc['city_name']})\n";
                $botReply .= "  📍 Clinic: " . ($doc['clinic_name'] ?? 'Medical Center') . " | 💳 PKR " . number_format($doc['consultation_fee']) . " | 💼 {$doc['experience_years']} yrs exp\n\n";
            }
            $botReply .= "Book an instant appointment on our [Find Doctor](find_doctor.php) portal.";
        } else {
            $botReply .= "\n\nYou can consult verified specialists for **{$matchedMatrix['spec']}** directly on CARE Nexus.";
        }
    } else if (!empty($matchedDoctors)) {
        $botReply = "### Verified Medical Specialists Found 🏥\n\nHere are top verified doctors matching your inquiry on CARE Nexus:\n\n";
        foreach (array_slice($matchedDoctors, 0, 5) as $doc) {
            $botReply .= "• **Dr. {$doc['full_name']}** — *{$doc['specialization_name']}* ({$doc['city_name']})\n";
            $botReply .= "  📍 Clinic: " . ($doc['clinic_name'] ?? 'Medical Center') . " | 💳 PKR " . number_format($doc['consultation_fee']) . " | 💼 {$doc['experience_years']} yrs exp\n\n";
        }
        $botReply .= "You can view complete profile details and available clinic slots on [Find Doctor](find_doctor.php).";
    } else if (!empty($matchedClinics)) {
        $botReply = "### Partner Medical Centers & Hospitals 🏥\n\nHere are top active medical centers matching your request:\n\n";
        foreach (array_slice($matchedClinics, 0, 5) as $cl) {
            $botReply .= "• **{$cl['clinic_name']}** ({$cl['city_name']})\n";
            $botReply .= "  📍 {$cl['address']}\n  📞 Contact: " . ($cl['phone'] ?: '+1 (800) 123-4567') . "\n\n";
        }
        $botReply .= "Our 24/7 Emergency Hotline is active at **+1 (800) 123-4567**.";
    } else {
        $botReply = "### CARE MediBot Clinical Guidance 🩺\n\nThank you for reaching out to CARE Nexus. Here is how I can assist you with your health query:\n\n"
                  . "1. **Find & Book Verified Doctors:** Search specialists by city or department on [Find Doctor](find_doctor.php).\n"
                  . "2. **24/7 Emergency Support:** If you have acute symptoms or an urgent situation, call **+1 (800) 123-4567** immediately.\n"
                  . "3. **Symptom & Department Inquiries:** Ask me about specific health symptoms (e.g. *fever*, *chest pain*, *headache*, *stomach pain*, *pcos*, *back pain*), and I will provide clinical guidance and matching specialist doctors!\n\n"
                  . "*Disclaimer: Always consult a verified doctor on CARE Nexus for formal medical diagnosis.*";
    }
}

// Generate Action Links
$actions = [];
if (!empty($matchedDoctors)) {
    $actions[] = ['label' => '📅 Book Appointment', 'url' => 'find_doctor.php'];
} else {
    $actions[] = ['label' => '🔍 Find Doctor', 'url' => 'find_doctor.php'];
    $actions[] = ['label' => '📞 Emergency Support', 'url' => 'index.php#contact'];
}

echo json_encode([
    'status' => 'success',
    'reply' => $botReply,
    'source' => $source,
    'actions' => $actions
]);
