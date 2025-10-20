<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: take_quiz.php');
    exit;
}

$username = trim($_POST['username'] ?? 'Guest');
$answers = $_POST['answers'] ?? [];

// Fetch all questions
$res = $conn->query("SELECT * FROM questions ORDER BY id ASC");
$score = 0;
$total = 0;
$feedback = [];
$no = 1;

while ($row = $res->fetch_assoc()) {
    $qid = $row['id'];
    $correct = strtoupper(trim($row['correct_answer']));
    $given = isset($answers[$qid]) ? strtoupper(trim($answers[$qid])) : null;

    $isCorrect = ($given !== null && $given === $correct);
    if ($isCorrect) $score++;

    $feedback[] = [
        'number' => $no++,
        'question' => $row['question'],
        'given' => $given ?? 'No answer',
        'correct' => $correct,
        'explanation' => $row['explanation'] ?? '',
        'isCorrect' => $isCorrect,
        'options' => [
            'A' => $row['option_a'],
            'B' => $row['option_b'],
            'C' => $row['option_c'],
            'D' => $row['option_d']
        ]
    ];
    $total++;
}

$percentage = $total ? round(($score / $total) * 100, 2) : 0;

// Save result
$stmt = $conn->prepare("INSERT INTO results (username, score, total, percentage) VALUES (?, ?, ?, ?)");
$stmt->bind_param('siid', $username, $score, $total, $percentage);
$stmt->execute();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Quiz Results — Quiz System</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f8fafc;
      margin: 0;
      padding: 0;
      color: #1e293b;
    }

    .container {
      max-width: 900px;
      margin: 50px auto;
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      animation: fadeIn 0.6s ease-in-out;
    }

    h1 {
      text-align: center;
      color: #0f172a;
      margin-bottom: 10px;
    }

    .lead {
      text-align: center;
      color: #475569;
      margin-bottom: 20px;
      font-size: 16px;
    }

    .score-box {
      text-align: center;
      background: #eff6ff;
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid #2563eb;
      font-size: 18px;
      font-weight: 600;
      color: #1e3a8a;
      margin-bottom: 30px;
    }

    .question {
      margin-bottom: 25px;
      padding: 20px;
      background: #f1f5f9;
      border-radius: 10px;
      border-left: 5px solid #94a3b8;
    }

    .question h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #1e293b;
    }

    .option {
      margin: 5px 0;
      font-size: 15px;
      color: #334155;
    }

    .correct { color: #16a34a; font-weight: 600; }
    .wrong { color: #dc2626; font-weight: 600; }

    .explanation {
      background: #e0f2fe;
      padding: 10px;
      border-left: 4px solid #0ea5a4;
      margin-top: 8px;
      border-radius: 6px;
      color: #0369a1;
    }

    .actions {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 30px;
    }

    .btn {
      background: #2563eb;
      color: #fff;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn:hover {
      background: #1e40af;
      transform: translateY(-2px);
    }

    .btn-secondary {
      background: #475569;
    }

    .btn-secondary:hover {
      background: #334155;
    }

    .btn-alt {
      background: #0ea5a4;
    }

    .btn-alt:hover {
      background: #0f766e;
    }

    hr {
      border: none;
      border-top: 1px solid #e2e8f0;
      margin: 25px 0;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Quiz Results</h1>
    <h3 class="lead">Participant: <strong><?php echo htmlspecialchars($username); ?></strong></h3>

    <div class="score-box">
      🎯 Score: <?php echo $score; ?> / <?php echo $total; ?> (<?php echo $percentage; ?>%)
    </div>

    <?php foreach ($feedback as $f): ?>
      <div class="question">
        <h3><?php echo $f['number'] . '. ' . htmlspecialchars($f['question']); ?></h3>

        <?php foreach ($f['options'] as $k => $v): 
            $mark = '';
            if ($k === $f['correct']) $mark = ' <span class="correct">(Correct)</span>';
            if ($k === $f['given'] && !$f['isCorrect']) $mark .= ' <span class="wrong">(Your Answer)</span>';
        ?>
          <div class="option"><?php echo $k . '. ' . htmlspecialchars($v) . $mark; ?></div>
        <?php endforeach; ?>

        <p style="margin-top:10px;">
          <?php if ($f['isCorrect']): ?>
            <span class="correct">✅ You answered <?php echo htmlspecialchars($f['given']); ?> — Correct!</span>
          <?php else: ?>
            <span class="wrong">❌ Your answer: <?php echo htmlspecialchars($f['given']); ?> — Correct answer: <?php echo htmlspecialchars($f['correct']); ?></span>
          <?php endif; ?>
        </p>

        <?php if (!empty($f['explanation'])): ?>
          <div class="explanation"><strong>Explanation:</strong> <?php echo htmlspecialchars($f['explanation']); ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="actions">
      <a href="take_quiz.php" class="btn">Try Again</a>
      <a href="view_results.php" class="btn btn-secondary">View All Results</a>
      <a href="add_questions.php" class="btn btn-alt">Add Questions</a>
    </div>
  </div>
</body>
</html>
