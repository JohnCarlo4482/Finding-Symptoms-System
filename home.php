<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

// Initialize history array in session if it doesn't exist
if (!isset($_SESSION['diagnosis_history'])) {
    $_SESSION['diagnosis_history'] = [];
}

// Data structures
class Symptom {
    public $id;
    public $name;
    public $category;
    public $description;

    public function __construct($id, $name, $category, $description) {
        $this->id = $id;
        $this->name = $name;
        $this->category = $category;
        $this->description = $description;
    }
}


class Illness {
    public $id;
    public $name;
    public $symptoms;
    public $description;
    public $causes;
    public $homeRemedies;
    public $severity;
    public $whenToSeekHelp;

    public function __construct($id, $name, $symptoms, $description, $causes, $homeRemedies, $severity, $whenToSeekHelp) {
        $this->id = $id;
        $this->name = $name;
        $this->symptoms = $symptoms;
        $this->description = $description;
        $this->causes = $causes;
        $this->homeRemedies = $homeRemedies;
        $this->severity = $severity;
        $this->whenToSeekHelp = $whenToSeekHelp;
    }
}

// Data
$symptoms = [
    // Head & Neurological
    new Symptom('headache', 'Headache', 'Head & Neurological', 'Pain or discomfort in the head, ranging from mild to severe'),
    new Symptom('dizziness', 'Dizziness', 'Head & Neurological', 'Feeling lightheaded, unsteady, or like the room is spinning'),
    new Symptom('confusion', 'Confusion', 'Head & Neurological', 'Difficulty thinking clearly or understanding surroundings'),
    new Symptom('blurred-vision', 'Blurred Vision', 'Head & Neurological', 'Lack of sharpness in vision making objects appear out of focus'),
    
    // Respiratory
    new Symptom('cough', 'Cough', 'Respiratory', 'Sudden expulsion of air to clear airways'),
    new Symptom('shortness-of-breath', 'Shortness of breath', 'Respiratory', 'Difficulty breathing or feeling like you can\'t get enough air'),
    new Symptom('chest-pain', 'Chest pain', 'Respiratory', 'Discomfort or pain in the chest area'),
    new Symptom('wheezing', 'Wheezing', 'Respiratory', 'Whistling sound when breathing'),
    
    // Digestive
    new Symptom('nausea', 'Nausea', 'Digestive', 'Feeling of sickness with an inclination to vomit'),
    new Symptom('vomiting', 'Vomiting', 'Digestive', 'Forceful expulsion of stomach contents'),
    new Symptom('abdominal-pain', 'Abdominal pain', 'Digestive', 'Pain in the stomach or belly area'),
    new Symptom('diarrhea', 'Diarrhea', 'Digestive', 'Frequent loose or watery bowel movements'),
    
    // Musculoskeletal
    new Symptom('joint-pain', 'Joint pain', 'Musculoskeletal', 'Pain or discomfort in joints'),
    new Symptom('muscle-weakness', 'Muscle weakness', 'Musculoskeletal', 'Reduced strength in muscles'),
    new Symptom('back-pain', 'Back pain', 'Musculoskeletal', 'Pain in the back area'),
    new Symptom('muscle-aches', 'Muscle aches', 'Musculoskeletal', 'General pain or soreness in muscles'),
    
    // General
    new Symptom('fever', 'Fever', 'General', 'Elevated body temperature above normal'),
    new Symptom('fatigue', 'Fatigue', 'General', 'Extreme tiredness or lack of energy'),
    new Symptom('chills', 'Chills', 'General', 'Feeling of cold with shivering'),
    new Symptom('sweating', 'Sweating', 'General', 'Excessive perspiration')
];

$illnesses = [
    new Illness(
        'migraine',
        'Migraine',
        ['headache', 'nausea', 'dizziness', 'blurred-vision'],
        'A neurological condition that causes severe headaches, often accompanied by other symptoms.',
        [
            'Hormonal changes',
            'Stress',
            'Certain foods or drinks',
            'Environmental factors',
            'Sleep changes',
            'Intense physical exertion'
        ],
        [
            'Rest in a quiet, dark room',
            'Apply cold or warm compresses',
            'Stay hydrated',
            'Practice relaxation techniques',
            'Use over-the-counter pain relievers',
            'Try caffeine in small amounts'
        ],
        'Moderate',
        [
            'Headache is severe or different from usual migraines',
            'Symptoms last longer than 72 hours',
            'You experience neurological symptoms like confusion',
            'Pain is accompanied by fever or stiff neck'
        ]
    ),
    new Illness(
        'flu',
        'Influenza',
        ['headache', 'cough', 'muscle-weakness', 'shortness-of-breath', 'fever', 'chills', 'fatigue'],
        'A viral infection that attacks your respiratory system, causing both respiratory and whole-body symptoms.',
        [
            'Influenza viruses',
            'Close contact with infected people',
            'Touching contaminated surfaces',
            'Airborne transmission',
            'Weakened immune system'
        ],
        [
            'Get plenty of rest',
            'Stay hydrated',
            'Take over-the-counter pain relievers',
            'Use a humidifier',
            'Gargle with salt water',
            'Try honey for cough relief'
        ],
        'Moderate to Severe',
        [
            'Difficulty breathing',
            'Chest pain',
            'Severe muscle pain',
            'Dehydration',
            'Worsening of chronic medical conditions'
        ]
    ),
    new Illness(
        'gastritis',
        'Gastritis',
        ['nausea', 'vomiting', 'abdominal-pain', 'fatigue'],
        'Inflammation of the stomach lining that can cause various digestive symptoms and discomfort.',
        [
            'H. pylori infection',
            'Regular use of pain relievers',
            'Excessive alcohol consumption',
            'Stress',
            'Autoimmune disorders',
            'Bile reflux'
        ],
        [
            'Eat smaller, more frequent meals',
            'Avoid trigger foods',
            'Try ginger tea',
            'Practice stress management',
            'Avoid lying down after eating',
            'Use probiotics'
        ],
        'Mild to Moderate',
        [
            'Severe abdominal pain',
            'Blood in vomit or stool',
            'Excessive vomiting',
            'Inability to eat or drink',
            'Rapid weight loss'
        ]
    ),
    new Illness(
        'bronchitis',
        'Bronchitis',
        ['cough', 'shortness-of-breath', 'wheezing', 'fatigue', 'chest-pain'],
        'Inflammation of the bronchial tubes that carry air to and from your lungs.',
        [
            'Viral infections',
            'Bacterial infections',
            'Exposure to irritants',
            'Smoking',
            'Air pollution'
        ],
        [
            'Use a humidifier',
            'Stay hydrated',
            'Try honey for cough',
            'Practice deep breathing exercises',
            'Avoid smoking and secondhand smoke',
            'Get adequate rest'
        ],
        'Moderate',
        [
            'Cough lasting more than 3 weeks',
            'Fever above 100.4°F (38°C)',
            'Coughing up blood',
            'Difficulty breathing',
            'Chest pain'
        ]
    ),
    new Illness(
        'anxiety',
        'Anxiety Disorder',
        ['headache', 'dizziness', 'chest-pain', 'sweating', 'fatigue'],
        'A mental health condition characterized by persistent feelings of worry and fear that interfere with daily activities.',
        [
            'Genetic factors',
            'Brain chemistry',
            'Environmental stress',
            'Trauma',
            'Medical conditions',
            'Substance use'
        ],
        [
            'Practice deep breathing exercises',
            'Regular exercise',
            'Meditation and mindfulness',
            'Maintain a regular sleep schedule',
            'Limit caffeine and alcohol',
            'Keep a worry journal'
        ],
        'Varies',
        [
            'Thoughts of self-harm',
            'Inability to perform daily tasks',
            'Severe panic attacks',
            'Depression symptoms',
            'Social isolation'
        ]
    )
];

// Group symptoms by category
$groupedSymptoms = [];
foreach ($symptoms as $symptom) {
    if (!isset($groupedSymptoms[$symptom->category])) {
        $groupedSymptoms[$symptom->category] = [];
    }
    $groupedSymptoms[$symptom->category][] = $symptom;
}

// Handle form submission
$selectedSymptoms = [];
$diagnosis = null;
$possibleIllnesses = $illnesses;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['symptoms'])) {
        $selectedSymptoms = $_POST['symptoms'];
        
        // Filter illnesses based on selected symptoms
        $possibleIllnesses = array_filter($illnesses, function($illness) use ($selectedSymptoms) {
            return count(array_intersect($selectedSymptoms, $illness->symptoms)) > 0;
        });

        // Calculate match percentage for each illness
        $matchScores = [];
        foreach ($possibleIllnesses as $illness) {
            $matchedSymptoms = count(array_intersect($selectedSymptoms, $illness->symptoms));
            $totalIllnessSymptoms = count($illness->symptoms);
            $score = ($matchedSymptoms / $totalIllnessSymptoms) * 100;
            $matchScores[$illness->id] = $score;
        }

        // Sort by match score
        arsort($matchScores);

        // Get the best match
        if (!empty($matchScores)) {
            $bestMatchId = array_key_first($matchScores);
            foreach ($possibleIllnesses as $illness) {
                if ($illness->id === $bestMatchId) {
                    $diagnosis = [
                        'illness' => $illness,
                        'confidence' => min($matchScores[$bestMatchId], 95)
                    ];
                    break;
                }
            }

            // Add to history
            if ($diagnosis) {
                $stmt = $conn->prepare("INSERT INTO diagnosis_history (user_id, symptoms, diagnosis, confidence) VALUES (?, ?, ?, ?)");
                $symptomsJson = json_encode($selectedSymptoms);
                $stmt->bind_param("issd", $_SESSION['user_id'], $symptomsJson, $diagnosis['illness']->name, $diagnosis['confidence']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Get diagnosis history
$stmt = $conn->prepare("SELECT * FROM diagnosis_history WHERE user_id = ?");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$historyResult = $stmt->get_result();
$diagnosisHistory = [];
while ($row = $historyResult->fetch_assoc()) {
    $diagnosisHistory[] = [
        'id' => $row['id'],
        'symptoms' => json_decode($row['symptoms'], true),
        'diagnosis' => $row['diagnosis'],
        'confidence' => $row['confidence'],
        'timestamp' => $row['created_at']
    ];
}
$stmt->close();

// Handle history view toggle
$showHistory = isset($_GET['view']) && $_GET['view'] === 'history';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Diagnosis System</title>
    <style>
        /* Your existing CSS styles here */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .navbar {
            background-color: white;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1a73e8;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: #1a73e8;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .nav-links a:hover {
            background-color: #f0f2f5;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .symptoms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .symptom-category {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #1a73e8;
        }

        .symptom-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .symptom-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .submit-button {
            background-color: #1a73e8;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin: 2rem auto;
            display: block;
        }

        .submit-button:hover {
            background-color: #1557b0;
        }

        .diagnosis-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .confidence-bar {
            background-color: #f0f2f5;
            height: 1.5rem;
            border-radius: 0.75rem;
            margin: 1rem 0;
            overflow: hidden;
        }

        .confidence-fill {
            height: 100%;
            background-color: #1a73e8;
            transition: width 0.6s ease-out;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .history-item {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .history-date {
            color: #666;
            font-size: 0.875rem;
        }

        .history-symptoms {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .symptom-tag {
            background-color: #f0f2f5;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .symptoms-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">HEALTH SCAN</div>
            <div class="nav-links">
                <a href="?view=<?php echo $showHistory ? '' : 'history'; ?>">
                    <?php echo $showHistory ? 'Back to Diagnosis' : 'View History'; ?>
                </a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($showHistory): ?>
            <div class="history-list">
                <h2>Diagnosis History</h2>
                <?php foreach ($diagnosisHistory as $entry): ?>
                    <div class="history-item">
                        <div class="history-date">
                            <?php echo date('F j, Y, g:i a', strtotime($entry['timestamp'])); ?>
                        </div>
                        <h3><?php echo $entry['diagnosis']; ?></h3>
                        <div class="confidence-bar">
                            <div class="confidence-fill" style="width: <?php echo $entry['confidence']; ?>%"></div>
                        </div>
                        <div class="history-symptoms">
                            <?php foreach ($entry['symptoms'] as $symptomId): ?>
                                <?php foreach ($symptoms as $symptom): ?>
                                    <?php if ($symptom->id === $symptomId): ?>
                                        <span class="symptom-tag"><?php echo $symptom->name; ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="symptoms-grid">
                    <?php foreach ($groupedSymptoms as $category => $categorySymptoms): ?>
                        <div class="symptom-category">
                            <h3 class="category-title"><?php echo $category; ?></h3>
                            <div class="symptom-list">
                                <?php foreach ($categorySymptoms as $symptom): ?>
                                    <label class="symptom-checkbox">
                                        <input type="checkbox" 
                                               name="symptoms[]" 
                                               value="<?php echo $symptom->id; ?>"
                                               <?php echo in_array($symptom->id, $selectedSymptoms) ? 'checked' : ''; ?>>
                                        <?php echo $symptom->name; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="submit-button">Analyze Symptoms</button>

                <?php if ($diagnosis): ?>
                    <div class="diagnosis-card">
                        <h2>Diagnosis Result</h2>
                        <p>Based on your symptoms, there's a <?php echo round($diagnosis['confidence']); ?>% chance you have:</p>
                        <h3><?php echo $diagnosis['illness']->name; ?></h3>
                        <div class="confidence-bar">
                            <div class="confidence-fill" style="width: <?php echo $diagnosis['confidence']; ?>%"></div>
                        </div>
                        
                        <h4>Description</h4>
                        <p><?php echo $diagnosis['illness']->description; ?></p>
                        
                        <h4>Possible Causes</h4>
                        <ul>
                            <?php foreach ($diagnosis['illness']->causes as $cause): ?>
                                <li><?php echo $cause; ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <h4>Home Remedies</h4>
                        <ul>
                            <?php foreach ($diagnosis['illness']->homeRemedies as $remedy): ?>
                                <li><?php echo $remedy; ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <h4>When to Seek Medical Help</h4>
                        <ul>
                            <?php foreach ($diagnosis['illness']->whenToSeekHelp as $warning): ?>
                                <li><?php echo $warning; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>