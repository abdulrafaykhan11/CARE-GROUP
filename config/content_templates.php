<?php
function deepSpecialtySections(string $name, string $overview, array $whenToBook, array $carePoints): array
{
    $whenText = $whenToBook ? implode(', ', $whenToBook) : 'ongoing symptoms, new discomfort, follow-up care, prevention, and second opinions';
    $careText = $carePoints ? implode(' ', $carePoints) : 'Bring previous reports, medicine names, and a clear timeline of symptoms.';

    return [
        'Why this field matters' => [
            "$name is not just a label on a doctor profile. It is a focused way of looking at symptoms, risks, test results, daily habits, family history, and long-term health patterns. A good $name consultation helps the patient understand what is happening, what should be watched carefully, what can be treated calmly, and what needs urgent attention.",
            $overview,
            "Many patients wait until symptoms become disruptive before booking a specialist. The better approach is to book when a pattern starts forming: repeated symptoms, discomfort that keeps returning, medicine that is not working, or a report that needs expert interpretation. Early review often makes treatment simpler, less stressful, and easier to follow."
        ],
        'Common reasons people book' => [
            "Patients commonly choose $name when they are dealing with $whenText. These reasons can look mild at first, but the value of a specialist is that they connect small clues with the bigger clinical picture. The doctor does not only ask what hurts; they ask when it started, what triggers it, what improves it, and whether it has happened before.",
            "The consultation is also useful when a patient already has a diagnosis but needs a better plan. Follow-up visits help adjust medicine, review test results, reduce side effects, and decide whether the current treatment is still the right one. For chronic conditions, this continuity matters more than a single prescription.",
            "Another strong reason to book is uncertainty. When symptoms are confusing, patients often move between home remedies, pharmacy advice, and online searches. A specialist visit turns that uncertainty into a structured plan: possible causes, tests if needed, warning signs, and the next step."
        ],
        'What happens during the visit' => [
            "A careful $name visit usually begins with history. The doctor listens to the main complaint, previous illnesses, family history, current medicines, allergies, lifestyle, and any recent tests. This is why small details matter. A symptom diary, old prescription, or lab report can save time and improve the accuracy of the plan.",
            "After history, the doctor may examine relevant body systems and decide whether the case needs treatment immediately, observation, lifestyle changes, or further testing. Good care is not always about ordering many tests. It is about ordering the right tests when they can change the decision.",
            "Patients should expect clear instructions: what medicine to take, how long to take it, what side effects to watch for, when to return, and when to seek urgent help. If the plan is not clear, it is reasonable to ask the doctor to repeat it in simple steps."
        ],
        'Tests, treatment, and follow-up' => [
            "Tests in $name are chosen according to symptoms and risk. Some patients need no test at all; others may need blood work, imaging, monitoring, or a procedure. A verified doctor uses the test result together with the patient story, not as a standalone answer.",
            "Treatment may include medicines, procedures, rehabilitation, lifestyle changes, nutrition changes, or referral to another specialist. A strong plan explains why each step is being used. Patients are more likely to follow care when they understand the reason behind it.",
            "Follow-up is often where real improvement happens. The first visit starts the plan; the next visit checks whether it worked. If symptoms improve, treatment may be reduced or continued. If symptoms remain, the doctor can revise the diagnosis, change medicine, or request more focused tests."
        ],
        'How to prepare' => [
            $careText,
            "Before booking, write a short timeline: when the problem started, how often it happens, what makes it better or worse, and any treatment already tried. Bring previous prescriptions, lab reports, scans, discharge summaries, and medicine strips if available.",
            "Patients should also be honest about habits, missed doses, allergies, pregnancy, breastfeeding, other medical conditions, and financial concerns. A realistic plan is better than a perfect plan that the patient cannot follow."
        ],
        'When to seek urgent care' => [
            "Some symptoms should not wait for a routine appointment. Sudden severe pain, breathing difficulty, fainting, heavy bleeding, weakness on one side, seizure, chest pressure, confusion, severe dehydration, or rapidly worsening symptoms need emergency care.",
            "For non-emergency concerns, booking a verified specialist is the right next step. Care Connect keeps public doctor results limited to admin-verified profiles, so patients can compare doctors with more confidence before requesting a visit."
        ]
    ];
}

function doctorLongProfileSections(array $doctor, array $clinicNames, array $availability): array
{
    $name = $doctor['full_name'] ?? 'this doctor';
    $specialty = $doctor['specialization_name'] ?? 'medicine';
    $city = $doctor['city_name'] ?? 'your city';
    $qualification = $doctor['qualification'] ?? 'qualified medical training';
    $experience = (int)($doctor['experience_years'] ?? 0);
    $fee = number_format((float)($doctor['consultation_fee'] ?? 0));
    $bio = trim($doctor['bio'] ?? '');
    $clinics = $clinicNames ? implode(', ', array_unique($clinicNames)) : 'the listed clinic location';
    $days = array_unique(array_column($availability, 'day'));
    $schedule = $days ? implode(', ', $days) : 'available clinic days';

    return [
        'Clinical Focus' => [
            "Dr. $name is a verified $specialty doctor practicing in $city. The profile combines professional registration, qualification, experience, clinic availability, and booking details so patients can understand the doctor before choosing an appointment. The aim of this page is not to rush the patient into booking; it is to make the decision clearer.",
            "The doctor has $experience years of experience and lists $qualification as the core qualification. For patients, that matters because healthcare is not only about the title of a specialty. It is about how carefully the doctor listens, how clearly the plan is explained, and how well the treatment fits the patient's real life.",
            $bio !== '' ? $bio : "This doctor profile is designed for patients who want a clear, calm appointment experience with a verified professional. The best consultation usually happens when the patient shares symptoms honestly, brings old reports, and asks questions until the plan feels understandable."
        ],
        'How This Doctor Helps Patients' => [
            "A good $specialty consultation usually starts with the patient's story. Dr. $name will need to understand the main complaint, when it started, how often it occurs, what makes it worse, what makes it better, and whether it affects sleep, work, school, family life, or daily routine. These details help separate minor temporary problems from conditions that need structured care.",
            "Patients often arrive with reports but no explanation. A doctor can translate those reports into practical meaning: what is normal, what is borderline, what needs treatment, and what can simply be monitored. This is especially useful when a patient has visited multiple places and still feels unsure about the next step.",
            "The consultation can also help patients avoid over-treatment. Not every symptom requires heavy medication or expensive testing. When the problem is mild, the doctor may focus on observation, lifestyle changes, simple medicines, or follow-up. When the problem is more serious, the doctor can escalate care quickly and explain why."
        ],
        'Patient Decision Guide' => [
            "Choosing a doctor becomes easier when the patient compares the right things. Specialty is the first filter, but it should not be the only one. Experience, qualification, clinic access, consultation fee, available timing, and the ability to explain the plan clearly all matter. This page brings those signals into one place so the patient can decide with less confusion.",
            "Dr. $name is listed here because the profile is active and verified. For a patient, that means the doctor is visible only after the administrative review process. The patient can then focus on whether this doctor's $specialty background matches the health concern, whether the clinic location is practical, and whether the available timing works for the visit.",
            "The strongest booking decision is usually a balanced one. A very senior doctor may be useful for complex symptoms, repeated treatment failure, or second opinions. A nearby clinic may be better for regular follow-ups. A lower fee may matter when multiple visits are expected. The right choice depends on the medical need and the patient's real situation."
        ],
        'What to Expect in the Appointment' => [
            "At the visit, patients should expect questions about current symptoms, previous illnesses, allergies, medicines, surgeries, family history, and lifestyle. If the issue has been going on for weeks or months, a timeline is very helpful. If symptoms come and go, photos, readings, or notes can make the visit more useful.",
            "The doctor may perform a focused examination and then decide whether tests are needed. Tests are not a sign that the doctor is unsure; they are tools used when the result can change treatment. A strong plan explains what each test is for and what decision will be made after the result.",
            "Patients should leave with a plan they can repeat in their own words. That plan may include medicine, warning signs, diet or activity advice, follow-up timing, and when to seek urgent care. If any part feels unclear, the best moment to ask is before leaving the clinic."
        ],
        'Questions Worth Asking' => [
            "Patients often feel shy about asking questions, but questions are part of good care. A useful appointment with Dr. $name can include asking what the likely cause is, what other causes are being considered, what warning signs should not be ignored, and how long it should take before improvement is expected.",
            "It is also worth asking how to take medicine correctly. Patients should know dose timing, food instructions, common side effects, missed-dose advice, and whether the medicine interacts with anything they already take. These small details can prevent confusion after the patient leaves the clinic.",
            "For ongoing conditions, ask what progress should look like. Some problems improve quickly, while others need monitoring over weeks or months. A clear follow-up point helps the patient avoid both extremes: ignoring symptoms for too long or panicking before treatment has had enough time to work."
        ],
        'Clinic, Fee, and Availability' => [
            "Dr. $name is linked with $clinics. The consultation fee shown on this page is PKR $fee. Clinic availability is currently arranged around $schedule, and the booking form only shows unbooked slots from active clinic schedules.",
            "Patients should choose the clinic and date first, then select an available time. If a slot disappears, it may have been booked by another patient. The system blocks duplicate bookings for the same doctor, date, and time to reduce appointment conflicts.",
            "If the appointment date or time later changes, the person changing it must add a reason. This reason appears to both the doctor and patient, so schedule changes stay transparent and easier to understand."
        ],
        'How to Prepare for This Doctor' => [
            "Bring previous prescriptions, test reports, scans, discharge papers, and a list of current medicines. If you are taking medicines without a prescription, include those too. Many treatment problems happen because doctors do not know what the patient is already taking.",
            "Write down the top three things you want answered. For example: what is the likely cause, what should I do now, and when should I come back? Clear questions help the doctor use the appointment time well.",
            "Also mention practical constraints. If a medicine is too expensive, if travel is difficult, if you cannot return quickly, or if you are worried about side effects, say it clearly. A realistic plan is more useful than a plan that looks perfect but cannot be followed."
        ],
        'Why Verification Matters' => [
            "Care Connect only shows this doctor to patients after admin verification. That means the admin has access to the professional profile details and documents before the doctor becomes visible in public search. Verification does not replace medical judgment, but it adds an important trust layer to the directory.",
            "Patients should still make thoughtful choices. Compare specialty, experience, clinic location, fee, and available timing. If symptoms are urgent, use emergency care instead of waiting for a routine appointment.",
            "For routine care, Dr. $name can be booked from the form on this page. Choose the clinic, date, time, reason for visit, and any notes that will help the doctor understand the concern before the appointment."
        ],
        'After the Visit' => [
            "Good care continues after the appointment. Patients should follow the written instructions, complete recommended tests, take medicine as directed, and watch whether symptoms are improving, staying the same, or getting worse. If the plan includes a follow-up, it is better to return with updates instead of starting again from zero later.",
            "If a medicine causes side effects, if a test result arrives, or if symptoms change suddenly, the patient should use the appointment dashboard and clinic instructions to decide the next step. Clear communication helps the doctor make better changes to treatment.",
            "The purpose of this profile is to make that care journey easier. The patient can see the doctor's focus, clinic details, fee, timings, booking rules, and useful preparation advice in one long page before deciding to request the appointment."
        ]
    ];
}

function doctorFaqs(array $doctor): array
{
    $name = $doctor['full_name'] ?? 'this doctor';
    $specialty = $doctor['specialization_name'] ?? 'this specialty';
    return [
        "How do I know if Dr. $name is verified?" => 'Only admin-verified doctors are visible to patients in public search and booking pages.',
        "What should I bring to a $specialty appointment?" => 'Bring old prescriptions, lab reports, scans, medicine names, allergy history, and a short symptom timeline.',
        'Can I change my appointment later?' => 'Yes, but if date or time changes, the person changing it must provide a reason that is visible to both sides.',
        'What if no slots are available?' => 'Try another clinic day, return later, or choose another verified doctor from the same specialty.',
        'Is the consultation fee final?' => 'The listed fee is the doctor profile fee. Any clinic-specific charges should be confirmed at the clinic if applicable.',
        'Should I use this page for emergencies?' => 'No. Severe or sudden symptoms should be handled through emergency care immediately.'
    ];
}
