<?php
require_once __DIR__ . '/../config/db.php';

// Add likes and dislikes columns if they don't exist
$conn->query("ALTER TABLE medical_news ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0, ADD COLUMN IF NOT EXISTS dislikes INT DEFAULT 0");

$articles = [
    [
        'title' => 'COVID-19: A Retrospective Analysis and Future Preparedness',
        'image' => 'https://images.unsplash.com/photo-1583946099379-f9c9cb8bc030?auto=format&fit=crop&w=1200&q=80',
        'desc' => "
<p>The COVID-19 pandemic reshaped the global healthcare landscape, presenting unprecedented challenges and catalyzing rapid innovation in medical science. From the initial outbreak in late 2019 to the historic global rollout of mRNA vaccines, the journey has been marked by scientific breakthroughs, societal resilience, and critical lessons for public health systems.</p>

<h3>The Emergence and Global Spread</h3>
<p>Initially identified as a cluster of pneumonia cases, SARS-CoV-2 quickly demonstrated its high transmissibility. Healthcare systems worldwide were tested as hospitals filled beyond capacity. The clinical presentation ranged from asymptomatic carriers to severe acute respiratory distress syndrome (ARDS), prompting urgent research into the pathophysiology of the virus.</p>

<h3>Vaccine Development: A Triumph of Science</h3>
<p>The rapid development of COVID-19 vaccines stands as one of the greatest medical achievements of the 21st century. Utilizing decades of foundational research, scientists developed highly effective mRNA vaccines (Pfizer-BioNTech, Moderna) and viral vector vaccines (AstraZeneca, Johnson & Johnson) in record time. These vaccines have saved millions of lives and significantly reduced the severity of the disease in breakthrough infections.</p>

<h3>Long COVID and Ongoing Challenges</h3>
<p>While the acute phase of the pandemic has subsided in many regions, the phenomenon known as 'Long COVID' or Post-Acute Sequelae of SARS-CoV-2 infection (PASC) continues to affect millions. Symptoms such as chronic fatigue, brain fog, and cardiopulmonary issues are the subject of intense ongoing clinical trials and research.</p>

<h3>Future Preparedness</h3>
<p>The lessons learned from COVID-19 are actively shaping future pandemic preparedness. Investments in genomic surveillance, rapid diagnostic technologies, and resilient healthcare supply chains are paramount. As we navigate the post-pandemic era, a unified global health strategy remains our strongest defense against future biological threats.</p>",
    ],
    [
        'title' => 'The Rise of Artificial Intelligence in Diagnostic Medicine',
        'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
        'desc' => "
<p>Artificial Intelligence (AI) is rapidly transitioning from a futuristic concept to a fundamental tool in modern clinical settings. In the realm of diagnostic medicine, AI algorithms are demonstrating remarkable capabilities, often matching or exceeding human accuracy in specific image-recognition tasks.</p>

<h3>Radiology and Oncology</h3>
<p>One of the most prominent applications of AI is in medical imaging. Deep learning models are now being used to analyze X-rays, MRIs, and CT scans to detect early signs of lung cancer, breast cancer, and neurological abnormalities. By highlighting subtle patterns that might escape the human eye, AI acts as a powerful 'second reader' for radiologists, reducing diagnostic errors and accelerating workflow.</p>

<h3>Predictive Analytics in Patient Care</h3>
<p>Beyond imaging, AI is revolutionizing predictive analytics. Machine learning models analyze electronic health records (EHR) in real-time to predict patient deterioration, such as the onset of sepsis or acute kidney injury. This allows medical teams to intervene proactively, shifting care from reactive to preventive.</p>

<h3>Ethical Considerations</h3>
<p>Despite the immense potential, the integration of AI in healthcare brings significant ethical and operational challenges. Issues regarding data privacy, algorithm bias, and the 'black box' nature of some AI decision-making processes must be addressed. Regulatory bodies are currently working to establish frameworks that ensure AI tools are safe, transparent, and equitable.</p>

<p>As AI continues to evolve, its partnership with human clinicians will undoubtedly lead to more accurate diagnoses, personalized treatment plans, and ultimately, better patient outcomes.</p>"
    ],
    [
        'title' => 'Advancements in mRNA Technology: Beyond Vaccines',
        'image' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1200&q=80',
        'desc' => "
<p>The unprecedented success of mRNA vaccines during the COVID-19 pandemic has thrust this technology into the global spotlight. However, the potential applications of messenger RNA (mRNA) extend far beyond infectious diseases, heralding a new era of therapeutics for cancer, autoimmune disorders, and rare genetic diseases.</p>

<h3>How mRNA Therapeutics Work</h3>
<p>mRNA acts as a set of instructions that tells our cells how to build specific proteins. In the context of therapeutics, scientists can design synthetic mRNA that directs the patient's own body to produce proteins that fight disease. Because mRNA does not alter the patient's DNA and degrades quickly in the body, it offers a highly safe and targeted approach to treatment.</p>

<h3>Cancer Immunotherapy</h3>
<p>One of the most exciting frontiers for mRNA is in oncology. Researchers are developing personalized mRNA cancer vaccines. By analyzing a patient's tumor, scientists can create an mRNA vaccine that instructs the immune system to recognize and attack the unique mutations present in that specific cancer. Early clinical trials are showing promising results in treating melanoma and pancreatic cancer.</p>

<h3>Treating Rare Genetic Diseases</h3>
<p>For individuals with rare genetic disorders caused by missing or defective proteins (such as cystic fibrosis or certain metabolic disorders), mRNA therapy offers the possibility of protein replacement. By delivering the correct mRNA sequence, the patient's cells can be coaxed into producing the necessary functional protein, potentially curing the underlying cause of the disease.</p>

<p>The rapid advancement of mRNA technology marks a paradigm shift in modern medicine, offering highly customizable, rapidly producible, and deeply effective treatments for some of humanity's most challenging diseases.</p>"
    ],
    [
        'title' => 'The Gut Microbiome: The Second Brain of Human Health',
        'image' => 'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?auto=format&fit=crop&w=1200&q=80',
        'desc' => "
<p>Over the past decade, scientific research has increasingly highlighted the critical role of the gut microbiome in overall human health. Comprising trillions of bacteria, viruses, and fungi residing primarily in our intestines, this complex ecosystem is now understood to be as crucial to our well-being as any major organ.</p>

<h3>Immunity and Digestion</h3>
<p>The primary function of the microbiome is to assist in the digestion of complex carbohydrates and the synthesis of essential vitamins (like Vitamin K and certain B vitamins). Crucially, the gut microbiome also plays a foundational role in training and modulating the immune system, acting as a barrier against harmful pathogens.</p>

<h3>The Gut-Brain Axis</h3>
<p>Perhaps the most fascinating area of research is the 'gut-brain axis'—the bidirectional communication network between the gastrointestinal tract and the central nervous system. Emerging evidence suggests that the composition of gut flora can significantly influence brain health, affecting mood, cognition, and potentially playing a role in conditions like depression, anxiety, and even neurodegenerative diseases such as Parkinson's.</p>

<h3>Nurturing a Healthy Microbiome</h3>
<p>Maintaining a diverse and robust microbiome is key to preventive health. Medical professionals recommend a diet rich in dietary fiber, prebiotics (such as garlic, onions, and bananas), and fermented foods (like yogurt, kefir, and kimchi). Conversely, the overuse of antibiotics and a diet high in processed foods and refined sugars can severely disrupt microbial balance, leading to dysbiosis and increased susceptibility to disease.</p>

<p>As research deepens, microbiome-targeted therapies, including personalized probiotics and fecal microbiota transplantation (FMT), are poised to become mainstream medical treatments.</p>"
    ],
    [
        'title' => 'Global Efforts to Eradicate Polio: The Final Push',
        'image' => 'https://images.unsplash.com/photo-1618015359952-0f04e1bc5730?auto=format&fit=crop&w=1200&q=80',
        'desc' => "
<p>Poliomyelitis, a highly infectious viral disease that primarily affects young children, has been the target of one of the most ambitious global health initiatives in history. Since the launch of the Global Polio Eradication Initiative (GPEI) in 1988, wild poliovirus cases have decreased by over 99%. However, the final steps to complete eradication remain challenging.</p>

<h3>The Current Landscape</h3>
<p>Today, wild poliovirus is endemic in only two countries: Pakistan and Afghanistan. The persistence of the virus in these regions is driven by a complex interplay of factors, including geopolitical instability, difficult terrain, high population mobility, and vaccine hesitancy. Despite these hurdles, frontline health workers continue to execute extensive immunization campaigns, often at great personal risk.</p>

<h3>The Challenge of Vaccine-Derived Polio</h3>
<p>In addition to wild poliovirus, the global health community is also battling circulating vaccine-derived poliovirus (cVDPV). This occurs in under-immunized populations where the weakened virus from the oral polio vaccine can mutate and circulate. To combat this, a novel oral polio vaccine type 2 (nOPV2) has been deployed, designed to be more genetically stable and significantly reduce the risk of cVDPV outbreaks.</p>

<h3>The Final Mile</h3>
<p>Eradicating a disease is a monumental task. The 'final mile' requires sustained political will, robust funding, and innovative surveillance techniques, such as environmental sampling of wastewater. The successful eradication of polio would not only save hundreds of thousands of children from paralysis but also prove that global coordination can conquer ancient pathogens.</p>"
    ]
];

// Truncate existing to restart cleanly
$conn->query("TRUNCATE TABLE medical_news");

$stmt = $conn->prepare("INSERT INTO medical_news (title, description, image, status, published_at, likes, dislikes) VALUES (?, ?, ?, 'Published', NOW(), 0, 0)");

foreach ($articles as $art) {
    $stmt->bind_param("sss", $art['title'], $art['desc'], $art['image']);
    $stmt->execute();
}
echo "Database seeded with " . count($articles) . " comprehensive medical news articles.\n";
