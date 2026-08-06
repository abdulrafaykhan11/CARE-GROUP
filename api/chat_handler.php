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

function respondJson(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function cleanText(?string $text): string
{
    return trim(preg_replace('/\s+/', ' ', (string)$text));
}

function textContainsAny(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        $needle = trim(strtolower($needle));
        if ($needle !== '' && strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}

function keywordsFrom(string $text): array
{
    $words = preg_split('/[^a-z0-9]+/i', strtolower($text));
    $stop = array_flip(['a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'how', 'i', 'in', 'is', 'it', 'me', 'my', 'of', 'on', 'or', 'the', 'to', 'what', 'when', 'where', 'which', 'with']);
    return array_values(array_unique(array_filter($words, fn($word) => strlen($word) >= 3 && !isset($stop[$word]))));
}

function tableExists(mysqli $conn, string $table): bool
{
    $safe = mysqli_real_escape_string($conn, $table);
    $result = @mysqli_query($conn, "SHOW TABLES LIKE '$safe'");
    return $result && mysqli_num_rows($result) > 0;
}

function fetchRows(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = @mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function bulletList(array $items): string
{
    $out = '';
    foreach ($items as $item) {
        $out .= "- " . $item . "\n";
    }
    return $out;
}

function doctorLine(array $doc): string
{
    $isVerified = ($doc['verification_status'] ?? '') === 'Verified';
    $status = $isVerified ? 'Verified' : 'Profile in review';
    $clinic = cleanText($doc['clinic_name'] ?? '') ?: 'Clinic listed on profile';
    $fee = number_format((float)($doc['consultation_fee'] ?? 0));
    $line = "**Dr. {$doc['full_name']}** - {$doc['specialization_name']}, {$doc['city_name']} ({$status})\n"
        . "  Clinic: {$clinic} | Fee: PKR {$fee} | Experience: " . (int)($doc['experience_years'] ?? 0) . " years";
    return $isVerified
        ? $line . " | [View profile](doctor_details.php?doctor_id={$doc['doctor_id']})"
        : $line . " | Not visible for public booking until verified";
}

function buildClinicalMatrix(): array
{
    return [
        'fever' => [
            'spec' => 'Internal Medicine',
            'aliases' => ['fever', 'bukhar', 'temperature', 'viral', 'infection', 'flu'],
            'title' => 'Fever Guidance',
            'overview' => 'Fever is commonly caused by viral infections, bacterial infections, dehydration, heat exposure, dengue, typhoid, or other inflammatory conditions.',
            'advice' => [
                'Check temperature with a thermometer and note how long fever has been present.',
                'Drink water or ORS, rest, and avoid self-starting antibiotics.',
                'Paracetamol may help fever for many adults, but dosing depends on age, weight, liver health, and other medicines.',
            ],
            'urgent' => 'Seek urgent care if fever is 103 F / 39.4 C or higher, lasts more than 3 days, causes confusion, stiff neck, breathing difficulty, severe dehydration, rash, or fits.',
        ],
        'headache' => [
            'spec' => 'Neurology',
            'aliases' => ['headache', 'migraine', 'sar dard', 'dizziness', 'chakkar'],
            'title' => 'Headache Guidance',
            'overview' => 'Headache can come from tension, migraine, sinus issues, dehydration, high blood pressure, eye strain, stress, sleep problems, or rarely a serious neurological problem.',
            'advice' => [
                'Rest in a quiet place, hydrate, and note triggers such as screen time, sleep loss, skipped meals, or bright light.',
                'Check blood pressure if headache is unusual or intense.',
                'Repeated headaches should be assessed by a doctor, especially if they affect work or sleep.',
            ],
            'urgent' => 'Emergency evaluation is needed for sudden worst-ever headache, weakness on one side, confusion, fainting, seizure, fever with stiff neck, or new vision loss.',
        ],
        'chest pain' => [
            'spec' => 'Cardiology',
            'aliases' => ['chest pain', 'heart pain', 'seena dard', 'angina', 'shortness of breath', 'breathless'],
            'title' => 'Chest Pain Guidance',
            'overview' => 'Chest pain can be caused by acidity, muscle strain, anxiety, lung problems, or heart disease. Heart-related pain must be treated as high priority.',
            'advice' => [
                'Stop activity and sit upright while symptoms are present.',
                'Do not drive yourself if pain is heavy, spreading, or associated with sweating or breathlessness.',
                'A cardiology review may include ECG, blood pressure check, cholesterol testing, and risk assessment.',
            ],
            'urgent' => 'Go to emergency immediately if chest pressure spreads to the arm, jaw, back, or shoulder, or comes with sweating, nausea, fainting, or shortness of breath.',
        ],
        'skin' => [
            'spec' => 'Dermatology',
            'aliases' => ['skin', 'rash', 'acne', 'pimples', 'itching', 'eczema', 'fungal', 'hair fall'],
            'title' => 'Skin and Hair Guidance',
            'overview' => 'Skin concerns may involve acne, allergy, eczema, fungal infection, hair fall, pigmentation, or irritation from products.',
            'advice' => [
                'Avoid strong steroid creams unless a doctor prescribed them.',
                'Use gentle cleanser and fragrance-free moisturizer; avoid scratching itchy skin.',
                'Take photos if the rash changes, because this helps the dermatologist compare progress.',
            ],
            'urgent' => 'Seek urgent care if rash spreads rapidly, involves lips/eyes, causes breathing difficulty, fever, severe pain, or blistering.',
        ],
        'eye' => [
            'spec' => 'Ophthalmology',
            'aliases' => ['eye', 'vision', 'blur', 'glasses', 'aankh', 'eyes', 'color blind', 'red eye'],
            'title' => 'Eye and Vision Guidance',
            'overview' => 'Eye symptoms can come from digital strain, dryness, infection, allergy, refractive error, cataract, glaucoma, or retina problems.',
            'advice' => [
                'Follow the 20-20-20 rule during screen use and keep the screen about one arm away.',
                'Do not rub irritated eyes; use only doctor-recommended drops.',
                'Book an eye specialist if blur, redness, headaches, or color-vision concerns persist.',
            ],
            'urgent' => 'Sudden vision loss, severe eye pain, flashes, new floaters, eye injury, or one-sided vision shadow needs urgent eye care.',
        ],
        'stomach' => [
            'spec' => 'Gastroenterology',
            'aliases' => ['stomach', 'abdomen', 'gas', 'acidity', 'vomit', 'diarrhea', 'constipation', 'liver'],
            'title' => 'Stomach and Digestion Guidance',
            'overview' => 'Digestive symptoms may relate to acidity, gastritis, food intolerance, infection, IBS, gallbladder, liver, or bowel conditions.',
            'advice' => [
                'Eat smaller meals, avoid oily/spicy foods for now, and hydrate well.',
                'Track stool changes, vomiting, fever, weight loss, and food triggers.',
                'Persistent or recurrent symptoms deserve a gastroenterology consultation.',
            ],
            'urgent' => 'Emergency care is needed for severe abdominal pain, blood in stool/vomit, black stool, repeated vomiting, fainting, or dehydration.',
        ],
        'back pain' => [
            'spec' => 'Orthopedics',
            'aliases' => ['back pain', 'joint', 'knee', 'bone', 'fracture', 'arthritis', 'spine', 'orthopedic'],
            'title' => 'Back, Bone, and Joint Guidance',
            'overview' => 'Back and joint pain can be due to muscle strain, posture, disc problems, arthritis, injury, or inflammation.',
            'advice' => [
                'Avoid heavy lifting and prolonged sitting; use gentle movement as tolerated.',
                'Cold packs help fresh swelling; warmth may help chronic stiffness.',
                'Orthopedic review is useful for persistent pain, injury, or movement limitation.',
            ],
            'urgent' => 'Seek urgent care for weakness, numbness in the groin area, loss of bladder/bowel control, major injury, fever with back pain, or severe worsening pain.',
        ],
        'pcos' => [
            'spec' => 'Gynecology',
            'aliases' => ['pcos', 'period', 'pregnancy', 'gyne', 'menstrual', 'pregnant', 'women health'],
            'title' => 'Women Health and PCOS Guidance',
            'overview' => 'Gynecology covers menstrual concerns, pregnancy care, PCOS, fertility questions, infections, and reproductive health.',
            'advice' => [
                'Track cycle dates, bleeding pattern, pain, weight change, acne, hair growth, and medicines.',
                'For PCOS, regular activity and balanced low-glycemic meals often support treatment.',
                'A gynecologist may advise hormonal tests, ultrasound, pregnancy test, or infection screening based on symptoms.',
            ],
            'urgent' => 'Urgent care is needed for heavy bleeding, severe pelvic pain, fainting, pregnancy with bleeding, or fever after delivery/procedure.',
        ],
        'child' => [
            'spec' => 'Pediatrics',
            'aliases' => ['child', 'baby', 'infant', 'pediatric', 'kid', 'vaccination', 'bacha'],
            'title' => 'Child Health Guidance',
            'overview' => 'Pediatrics covers fever, cough, feeding, growth, vaccination, infections, allergies, and child development.',
            'advice' => [
                'Monitor drinking, urination, alertness, breathing, fever pattern, and feeding.',
                'Keep vaccination records ready for the doctor.',
                'Medicine dose for children depends on age and weight, so avoid adult dosing.',
            ],
            'urgent' => 'Newborn fever, breathing difficulty, blue lips, fits, severe dehydration, extreme sleepiness, or persistent vomiting needs urgent care.',
        ],
        'diabetes' => [
            'spec' => 'Internal Medicine',
            'aliases' => ['diabetes', 'sugar', 'glucose', 'insulin', 'hba1c'],
            'title' => 'Diabetes Guidance',
            'overview' => 'Diabetes care focuses on blood sugar control, diet, exercise, medicines, eye/kidney/foot checks, and preventing complications.',
            'advice' => [
                'Track fasting and post-meal sugar readings if your doctor advised monitoring.',
                'Prefer balanced meals with fiber, protein, and controlled portions of refined carbohydrates.',
                'Regular follow-up helps adjust medicine safely and check HbA1c, kidneys, feet, and eyes.',
            ],
            'urgent' => 'Urgent care is needed for very high sugar with vomiting, confusion, deep breathing, severe weakness, or very low sugar with sweating/fainting.',
        ],
        'blood pressure' => [
            'spec' => 'Cardiology',
            'aliases' => ['blood pressure', 'bp', 'hypertension', 'low bp', 'high bp'],
            'title' => 'Blood Pressure Guidance',
            'overview' => 'Blood pressure problems can affect the heart, brain, kidneys, and eyes. Diagnosis needs repeated accurate readings, not one random number.',
            'advice' => [
                'Measure BP after 5 minutes of rest, seated, with the arm supported.',
                'Reduce salt, avoid smoking, sleep well, and follow prescribed medicines regularly.',
                'Bring a BP log to the doctor instead of relying on memory.',
            ],
            'urgent' => 'Emergency care is needed for very high BP with chest pain, breathlessness, confusion, weakness, severe headache, or vision changes.',
        ],
    ];
}

function matchClinicalTopic(string $lowerMsg, array $matrix): ?array
{
    foreach ($matrix as $topic) {
        if (textContainsAny($lowerMsg, $topic['aliases'])) {
            return $topic;
        }
    }
    return null;
}

function isHealthRelated(string $lowerMsg, ?array $matchedTopic): bool
{
    if ($matchedTopic) return true;
    $healthWords = [
        'doctor', 'dr', 'clinic', 'hospital', 'health', 'medical', 'medicine', 'disease', 'symptom',
        'pain', 'fever', 'cough', 'heart', 'skin', 'eye', 'stomach', 'pregnancy', 'diet', 'exercise',
        'appointment', 'book', 'specialist', 'specialization', 'treatment', 'tablet', 'blood', 'bp',
        'sugar', 'diabetes', 'infection', 'allergy', 'emergency', 'patient', 'care', 'test', 'report'
    ];
    return textContainsAny($lowerMsg, $healthWords);
}

function callGeminiAPI(string $modelName, string $apiKey, array $payload): ?string
{
    if ($apiKey === '' || !function_exists('curl_init')) {
        return null;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

    $executeCurl = function(array $body) use ($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 16);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, json_decode((string)$res, true)];
    };

    [$httpCode, $data] = $executeCurl($payload);

    if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    // Fallback 1: Try with googleSearch key if google_search was rejected
    if (isset($payload['tools'])) {
        $fallbackPayload = $payload;
        $fallbackPayload['tools'] = [['googleSearch' => new stdClass()]];
        [$httpCode2, $data2] = $executeCurl($fallbackPayload);
        if ($httpCode2 === 200 && isset($data2['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data2['candidates'][0]['content']['parts'][0]['text']);
        }

        // Fallback 2: Try without tools array
        unset($fallbackPayload['tools']);
        [$httpCode3, $data3] = $executeCurl($fallbackPayload);
        if ($httpCode3 === 200 && isset($data3['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data3['candidates'][0]['content']['parts'][0]['text']);
        }
    }

    return null;
}


$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? [];
$userMessage = cleanText($input['message'] ?? '');
$chatHistory = is_array($input['history'] ?? null) ? array_slice($input['history'], -8) : [];

if ($userMessage === '') {
    respondJson([
        'status' => 'error',
        'reply' => 'Please type your health question, doctor name, disease, symptom, or booking query.',
    ]);
}

$lowerMsg = strtolower($userMessage);
$clinicalMatrix = buildClinicalMatrix();
$matchedTopic = matchClinicalTopic($lowerMsg, $clinicalMatrix);

$offTopicHints = ['movie', 'cricket', 'football', 'game', 'coding', 'javascript', 'python', 'bitcoin', 'crypto', 'politics', 'president'];
$isMathOnly = preg_match('/^\s*\d+\s*[\+\-\*\/xX]\s*\d+\s*$/', $userMessage);
if (($isMathOnly || textContainsAny($lowerMsg, $offTopicHints)) && !isHealthRelated($lowerMsg, $matchedTopic)) {
    respondJson([
        'status' => 'success',
        'reply' => "I handle health, doctors, diseases, symptoms, diet, tests, appointments, and CARE Nexus services. Ask me any medical or CARE platform question and I will answer directly.",
        'source' => 'guardrail',
        'actions' => [
            ['label' => 'Find Doctor', 'url' => 'find_doctor.php'],
            ['label' => 'Emergency Support', 'url' => 'index.php#contact'],
        ],
    ]);
}

$matchedDoctors = [];
$matchedClinics = [];
$matchedSpecs = [];
$matchedDiseases = [];
$matchedNews = [];
$websiteContext = '';
$hasDb = isset($conn) && $conn instanceof mysqli && !mysqli_connect_errno();

if ($hasDb) {
    $doctors = fetchRows($conn, "
        SELECT d.doctor_id, d.experience_years, d.consultation_fee, d.qualification, d.bio, d.verification_status,
               u.full_name, s.specialization_name, c.city_name, cl.clinic_name
        FROM doctors d
        JOIN users u ON u.user_id = d.user_id
        JOIN specializations s ON s.specialization_id = d.specialization_id
        JOIN cities c ON c.city_id = d.city_id
        LEFT JOIN doctor_clinic dc ON dc.doctor_id = d.doctor_id AND dc.is_primary = 1
        LEFT JOIN clinics cl ON cl.clinic_id = dc.clinic_id
        WHERE u.status = 'Active'
        ORDER BY d.verification_status = 'Verified' DESC, d.experience_years DESC, d.created_at DESC
        LIMIT 80
    ");

    foreach ($doctors as $doc) {
        $doctorName = strtolower($doc['full_name'] ?? '');
        $specName = strtolower($doc['specialization_name'] ?? '');
        $cityName = strtolower($doc['city_name'] ?? '');
        $clinicName = strtolower($doc['clinic_name'] ?? '');
        $topicSpec = strtolower($matchedTopic['spec'] ?? '');
        $score = 0;

        if ($doctorName && (strpos($lowerMsg, $doctorName) !== false || textContainsAny($lowerMsg, explode(' ', $doctorName)))) $score += 5;
        if ($specName && strpos($lowerMsg, $specName) !== false) $score += 4;
        if ($topicSpec && $specName === $topicSpec) $score += 4;
        if ($cityName && strpos($lowerMsg, $cityName) !== false) $score += 2;
        if ($clinicName && strpos($lowerMsg, $clinicName) !== false) $score += 3;

        if ($score > 0) {
            $doc['_score'] = $score;
            $existingIndex = null;
            foreach ($matchedDoctors as $index => $existingDoctor) {
                if ((int)$existingDoctor['doctor_id'] === (int)$doc['doctor_id']) {
                    $existingIndex = $index;
                    break;
                }
            }
            if ($existingIndex === null) {
                $matchedDoctors[] = $doc;
            } else {
                $oldClinic = cleanText($matchedDoctors[$existingIndex]['clinic_name'] ?? '');
                $newClinic = cleanText($doc['clinic_name'] ?? '');
                if ($newClinic !== '' && stripos($oldClinic, $newClinic) === false) {
                    $matchedDoctors[$existingIndex]['clinic_name'] = trim($oldClinic . ', ' . $newClinic, ', ');
                }
                $matchedDoctors[$existingIndex]['_score'] = max((int)$matchedDoctors[$existingIndex]['_score'], $score);
            }
        }
    }
    usort($matchedDoctors, fn($a, $b) => ($b['_score'] <=> $a['_score']) ?: (($b['verification_status'] === 'Verified') <=> ($a['verification_status'] === 'Verified')));

    $specSql = tableExists($conn, 'specialization_guides')
        ? "SELECT s.specialization_id, s.specialization_name, s.description, g.overview, g.when_to_book, g.care_points
           FROM specializations s
           LEFT JOIN specialization_guides g ON g.specialization_id = s.specialization_id
           WHERE s.status = 'Active'"
        : "SELECT specialization_id, specialization_name, description, NULL overview, NULL when_to_book, NULL care_points
           FROM specializations
           WHERE status = 'Active'";
    foreach (fetchRows($conn, $specSql) as $spec) {
        $name = strtolower($spec['specialization_name'] ?? '');
        $desc = strtolower(($spec['description'] ?? '') . ' ' . ($spec['overview'] ?? '') . ' ' . ($spec['when_to_book'] ?? ''));
        $topicSpec = strtolower($matchedTopic['spec'] ?? '');
        if (($name && strpos($lowerMsg, $name) !== false) || ($topicSpec && $name === $topicSpec) || (!$matchedTopic && textContainsAny($desc, keywordsFrom($lowerMsg)))) {
            $matchedSpecs[] = $spec;
        }
    }

    if (tableExists($conn, 'clinics')) {
        foreach (fetchRows($conn, "SELECT cl.clinic_name, cl.phone, cl.email, cl.address, ci.city_name FROM clinics cl JOIN cities ci ON ci.city_id = cl.city_id WHERE cl.status = 'Active' LIMIT 80") as $clinic) {
            $clinicHaystack = strtolower(($clinic['clinic_name'] ?? '') . ' ' . ($clinic['city_name'] ?? '') . ' ' . ($clinic['address'] ?? ''));
            if (textContainsAny($lowerMsg, keywordsFrom($clinicHaystack)) && (strpos($lowerMsg, 'clinic') !== false || strpos($lowerMsg, 'hospital') !== false || strpos($lowerMsg, 'address') !== false || strpos($lowerMsg, strtolower($clinic['clinic_name'])) !== false)) {
                $matchedClinics[] = $clinic;
            }
        }
    }

    if (tableExists($conn, 'diseases')) {
        foreach (fetchRows($conn, "SELECT disease_name, symptoms, prevention, treatment, description FROM diseases LIMIT 100") as $disease) {
            $haystack = strtolower(($disease['disease_name'] ?? '') . ' ' . ($disease['symptoms'] ?? '') . ' ' . ($disease['description'] ?? ''));
            if (textContainsAny($lowerMsg, keywordsFrom($haystack)) || strpos($lowerMsg, strtolower($disease['disease_name'] ?? '')) !== false) {
                $matchedDiseases[] = $disease;
            }
        }
    }

    if (tableExists($conn, 'medical_news')) {
        foreach (fetchRows($conn, "SELECT title, description, published_at FROM medical_news WHERE status = 'Published' ORDER BY published_at DESC LIMIT 30") as $news) {
            $haystack = strtolower(($news['title'] ?? '') . ' ' . strip_tags($news['description'] ?? ''));
            if (textContainsAny($haystack, keywordsFrom($lowerMsg))) {
                $matchedNews[] = $news;
            }
        }
    }
}

if ($matchedSpecs) {
    $websiteContext .= "CARE specializations and guide content:\n";
    foreach (array_slice($matchedSpecs, 0, 4) as $spec) {
        $websiteContext .= "- {$spec['specialization_name']}: " . cleanText($spec['overview'] ?: $spec['description']) . "\n";
        if (!empty($spec['when_to_book'])) $websiteContext .= "  When to book: " . cleanText($spec['when_to_book']) . "\n";
        if (!empty($spec['care_points'])) $websiteContext .= "  Care points: " . cleanText($spec['care_points']) . "\n";
    }
}

if ($matchedDoctors) {
    $websiteContext .= "\nCARE doctor matches:\n";
    foreach (array_slice($matchedDoctors, 0, 6) as $doc) {
        $websiteContext .= "- " . strip_tags(str_replace("\n", ' ', doctorLine($doc))) . "\n";
    }
}

if ($matchedClinics) {
    $websiteContext .= "\nCARE clinic matches:\n";
    foreach (array_slice($matchedClinics, 0, 5) as $clinic) {
        $websiteContext .= "- {$clinic['clinic_name']} ({$clinic['city_name']}): {$clinic['address']} | Phone: {$clinic['phone']}\n";
    }
}

if ($matchedDiseases) {
    $websiteContext .= "\nCARE disease library matches:\n";
    foreach (array_slice($matchedDiseases, 0, 3) as $disease) {
        $websiteContext .= "- {$disease['disease_name']}: " . cleanText($disease['description']) . "\n";
        if (!empty($disease['symptoms'])) $websiteContext .= "  Symptoms: " . cleanText($disease['symptoms']) . "\n";
        if (!empty($disease['prevention'])) $websiteContext .= "  Prevention: " . cleanText($disease['prevention']) . "\n";
        if (!empty($disease['treatment'])) $websiteContext .= "  Treatment: " . cleanText($disease['treatment']) . "\n";
    }
}

if ($matchedNews) {
    $websiteContext .= "\nRelevant CARE medical news:\n";
    foreach (array_slice($matchedNews, 0, 3) as $news) {
        $websiteContext .= "- {$news['title']}: " . cleanText(strip_tags($news['description'])) . "\n";
    }
}

$isBookingQuery = textContainsAny($lowerMsg, ['book', 'appointment', 'slot', 'schedule', 'visit']);
$isEmergencyQuery = textContainsAny($lowerMsg, ['emergency', 'urgent', 'ambulance', 'severe', 'sudden']);

if ($isBookingQuery && !$matchedTopic && !$matchedDoctors) {
    respondJson([
        'status' => 'success',
        'reply' => "### Booking an appointment\n\n1. Open [Find Doctor](find_doctor.php).\n2. Search by doctor name, city, or specialization.\n3. Open the doctor profile and choose an available clinic slot.\n4. Add your reason/notes and submit the appointment request.\n\nFor urgent symptoms, use emergency support instead of waiting for a routine slot.",
        'source' => 'website_knowledge',
        'actions' => [
            ['label' => 'Find Doctor', 'url' => 'find_doctor.php'],
            ['label' => 'Patient Dashboard', 'url' => 'patient/dashboard.php'],
        ],
    ]);
}

if ($isEmergencyQuery) {
    respondJson([
        'status' => 'success',
        'reply' => "### Emergency guidance\n\nIf symptoms are sudden, severe, or worsening, do not wait for chat advice. Call local emergency services or go to the nearest emergency department now.\n\nRed flags include chest pressure, breathing difficulty, one-sided weakness, fainting, severe bleeding, sudden vision loss, severe allergic reaction, confusion, seizure, or major injury.",
        'source' => 'website_knowledge',
        'actions' => [
            ['label' => 'Emergency Support', 'url' => 'index.php#contact'],
            ['label' => 'Find Doctor', 'url' => 'find_doctor.php'],
        ],
    ]);
}

$historyText = '';
foreach ($chatHistory as $turn) {
    $sender = ($turn['sender'] ?? '') === 'user' ? 'User' : 'Assistant';
    $historyText .= $sender . ': ' . cleanText($turn['text'] ?? '') . "\n";
}

$siteInstruction = $websiteContext !== ''
    ? "Use CARE website context first. If it answers part of the query, cite it naturally as CARE data."
    : "No matching CARE website context was found. Answer from general medical knowledge and say that no exact CARE site match was found if relevant.";

global $geminiSystemInstruction;
$prompt = "DUAL-SOURCE GROUNDING QUERY:\n\n"
    . "CARE Website Context:\n" . ($websiteContext ?: "No direct CARE database match.\n") . "\n"
    . "Recent Chat History:\n" . ($historyText ?: "None\n") . "\n"
    . "User Question: " . $userMessage;

$payload = [
    'systemInstruction' => [
        'parts' => [['text' => $geminiSystemInstruction]]
    ],
    'contents' => [[
        'role' => 'user',
        'parts' => [['text' => $prompt]],
    ]],
    'tools' => [
        ['google_search' => new stdClass()]
    ],
    'generationConfig' => [
        'temperature' => 0.15,
        'topK' => 20,
        'topP' => 0.85,
        'maxOutputTokens' => 1400,
    ],
];

$source = $websiteContext !== '' ? 'website_knowledge' : 'gemini_ai';
$botReply = null;

if ($websiteContext !== '') {
    if ($matchedDoctors) {
        $botReply = $matchedTopic
            ? "### {$matchedTopic['title']}\n\n{$matchedTopic['overview']}\n\n**What you can do now:**\n" . bulletList($matchedTopic['advice']) . "\n**Best specialist:** {$matchedTopic['spec']}\n\n**CARE doctor matches:**\n"
            : "### Matching doctors on CARE\n\n";
        foreach (array_slice($matchedDoctors, 0, 5) as $doc) {
            $botReply .= "- " . doctorLine($doc) . "\n";
        }
        $botReply .= "\nOpen a profile to check availability and book an appointment.";
        if ($matchedTopic) {
            $botReply .= "\n\n**Red flags:** {$matchedTopic['urgent']}\n\nNote: This guidance is informational, not a diagnosis.";
        }
    } elseif ($matchedTopic) {
        $botReply = "### {$matchedTopic['title']}\n\n"
            . $matchedTopic['overview'] . "\n\n"
            . "**What you can do now:**\n" . bulletList($matchedTopic['advice']) . "\n"
            . "**Best specialist:** {$matchedTopic['spec']}\n\n"
            . "**Red flags:** {$matchedTopic['urgent']}\n\n";
        if ($matchedSpecs) {
            $spec = $matchedSpecs[0];
            $botReply .= "**CARE specialty guide:** " . cleanText($spec['overview'] ?: $spec['description']) . "\n\n";
        }
        $botReply .= "Note: This guidance is informational, not a diagnosis.";
    } elseif ($matchedSpecs) {
        $spec = $matchedSpecs[0];
        $botReply = "### {$spec['specialization_name']}\n\n"
            . cleanText($spec['overview'] ?: $spec['description']) . "\n\n"
            . "**When to book:** " . cleanText($spec['when_to_book'] ?: 'Book when symptoms are persistent, recurring, worsening, or affecting daily life.') . "\n\n"
            . "**Before the visit:** " . cleanText($spec['care_points'] ?: 'Bring old reports, prescriptions, current medicines, and a short symptom timeline.');
    } elseif ($matchedDiseases) {
        $d = $matchedDiseases[0];
        $botReply = "### {$d['disease_name']}\n\n"
            . cleanText($d['description']) . "\n\n"
            . "**Common symptoms:** " . cleanText($d['symptoms']) . "\n\n"
            . "**Prevention:** " . cleanText($d['prevention']) . "\n\n"
            . "**Treatment direction:** " . cleanText($d['treatment']) . "\n\n"
            . "Note: This is informational. A verified doctor should confirm diagnosis and treatment.";
    } elseif ($matchedClinics) {
        $botReply = "### Matching clinics on CARE\n\n";
        foreach (array_slice($matchedClinics, 0, 5) as $clinic) {
            $phone = cleanText($clinic['phone'] ?? '') ?: 'Contact through CARE';
            $botReply .= "- **{$clinic['clinic_name']}** ({$clinic['city_name']})\n  Address: {$clinic['address']} | Phone: {$phone}\n";
        }
    } elseif ($matchedNews) {
        $botReply = "### Relevant CARE health updates\n\n";
        foreach (array_slice($matchedNews, 0, 3) as $news) {
            $botReply .= "- **{$news['title']}**: " . cleanText(strip_tags($news['description'])) . "\n";
        }
    }
}

if (!$botReply) {
    foreach (array_filter([GEMINI_MODEL, defined('GEMINI_FALLBACK_MODEL') ? GEMINI_FALLBACK_MODEL : null, 'gemini-2.0-flash-lite', 'gemini-1.5-flash']) as $model) {
        $botReply = callGeminiAPI($model, GEMINI_API_KEY, $payload);
        if ($botReply) break;
    }
}

if (!$botReply) {
    if ($matchedDoctors) {
        $botReply = "### Matching doctors on CARE\n\n";
        foreach (array_slice($matchedDoctors, 0, 5) as $doc) {
            $botReply .= "- " . doctorLine($doc) . "\n";
        }
        $botReply .= "\nOpen a profile to check availability and book an appointment.";
        $source = 'website_knowledge';
    } elseif ($matchedSpecs) {
        $spec = $matchedSpecs[0];
        $botReply = "### {$spec['specialization_name']}\n\n"
            . cleanText($spec['overview'] ?: $spec['description']) . "\n\n"
            . "**When to book:** " . cleanText($spec['when_to_book'] ?: 'Book when symptoms are persistent, recurring, worsening, or affecting daily life.') . "\n\n"
            . "**Before the visit:** " . cleanText($spec['care_points'] ?: 'Bring old reports, prescriptions, current medicines, and a short symptom timeline.');
        if ($matchedDoctors) {
            $botReply .= "\n\n**CARE doctors:**\n" . bulletList(array_map('doctorLine', array_slice($matchedDoctors, 0, 4)));
        }
        $source = 'website_knowledge';
    } elseif ($matchedDiseases) {
        $d = $matchedDiseases[0];
        $botReply = "### {$d['disease_name']}\n\n"
            . cleanText($d['description']) . "\n\n"
            . "**Common symptoms:** " . cleanText($d['symptoms']) . "\n\n"
            . "**Prevention:** " . cleanText($d['prevention']) . "\n\n"
            . "**Treatment direction:** " . cleanText($d['treatment']) . "\n\n"
            . "Note: This is informational. A verified doctor should confirm diagnosis and treatment.";
        $source = 'website_knowledge';
    } elseif ($matchedTopic) {
        $botReply = "### {$matchedTopic['title']}\n\n"
            . $matchedTopic['overview'] . "\n\n"
            . "**What you can do now:**\n" . bulletList($matchedTopic['advice']) . "\n"
            . "**Best specialist:** {$matchedTopic['spec']}\n\n"
            . "**Red flags:** {$matchedTopic['urgent']}\n\n";
        if ($matchedDoctors) {
            $botReply .= "**CARE doctor matches:**\n" . bulletList(array_map('doctorLine', array_slice($matchedDoctors, 0, 4))) . "\n";
        } else {
            $botReply .= "No exact CARE doctor match was found in the local website data for this query, but you can search the relevant specialty on [Find Doctor](find_doctor.php).\n";
        }
        $botReply .= "\nNote: This guidance is for information only, not a diagnosis.";
    } else {
        $botReply = "### Medical guidance\n\n"
            . "I could not find an exact CARE website match for this query, but here is the safest way to move forward:\n\n"
            . "- Tell me the main symptom or disease name, duration, age, severity, and any existing conditions.\n"
            . "- For mild symptoms, track temperature/pain level, hydration, medicines already taken, and what makes it better or worse.\n"
            . "- Avoid self-starting antibiotics, steroids, or strong painkillers without a doctor.\n"
            . "- Book a verified doctor if symptoms persist, recur, or interfere with daily life.\n"
            . "- Use emergency care for chest pain, breathing difficulty, fainting, sudden weakness, severe bleeding, confusion, seizure, or sudden vision loss.\n\n"
            . "Ask with the disease/symptom name and I will give a more specific plan.";
    }
}

$actions = [];
if ($matchedDoctors) {
    $actions[] = ['label' => 'Book Appointment', 'url' => 'find_doctor.php'];
    $firstVerified = null;
    foreach ($matchedDoctors as $doc) {
        if (($doc['verification_status'] ?? '') === 'Verified') {
            $firstVerified = $doc;
            break;
        }
    }
    if ($firstVerified) {
        $actions[] = ['label' => 'View Doctor Profile', 'url' => 'doctor_details.php?doctor_id=' . (int)$firstVerified['doctor_id']];
    }
} else {
    $actions[] = ['label' => 'Find Doctor', 'url' => 'find_doctor.php'];
}
$actions[] = ['label' => 'Emergency Support', 'url' => 'index.php#contact'];

respondJson([
    'status' => 'success',
    'reply' => $botReply,
    'source' => $source,
    'actions' => $actions,
]);
