<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.name, u.email, s.* 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Card - Doon University</title>
    <style>
        /* Wikipedia-inspired Minimal & Paper-friendly Styling */
        @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --text-main: #000000;
            --text-secondary: #202122;
            --border-light: #a2a9b1;
            --bg-paper: #ffffff;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            background: #f8f9fa;
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px 0;
            line-height: 1.4;
        }

        .admit-card {
            background: var(--bg-paper);
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 1.5cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            position: relative;
        }

        .header {
            border-bottom: 2px solid var(--text-main);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .university-name {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.75rem;
            margin: 0;
            font-weight: 700;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 1.1rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
            text-decoration: underline;
        }

        .infobox {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .infobox th {
            text-align: left;
            width: 35%;
            padding: 4px 8px;
            border: 1px solid var(--border-light);
            background-color: #f8f9fa;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .infobox td {
            padding: 4px 8px;
            border: 1px solid var(--border-light);
            font-size: 0.9rem;
        }

        .section-title {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.2rem;
            border-bottom: 1px solid var(--border-light);
            margin: 1.5rem 0 0.75rem 0;
            padding-bottom: 0.2rem;
            font-weight: 700;
        }

        .exam-schedule {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .exam-schedule th {
            border: 1px solid var(--border-light);
            padding: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 0.85rem;
            background-color: #f8f9fa;
        }

        .exam-schedule td {
            border: 1px solid var(--border-light);
            padding: 10px 8px;
            font-size: 0.85rem;
        }

        .exam-schedule .sig-cell {
            height: 50px;
            width: 15%;
        }

        .instructions {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 1rem;
            border: 1px solid var(--border-light);
            padding: 10px;
        }

        .instructions h4 {
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .instructions ul {
            padding-left: 1.2rem;
            margin: 0;
        }

        .footer {
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
        }

        .sig-box {
            text-align: center;
            width: 200px;
        }

        .sig-line {
            border-top: 1px solid var(--text-main);
            margin-bottom: 5px;
        }

        .sig-text {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .print-fab {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: #3366cc;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        @media print {
            body { background: white; padding: 0; }
            .admit-card { box-shadow: none; margin: 0; width: 100%; padding: 1cm; }
            .print-fab { display: none; }
            .infobox th { background-color: transparent !important; }
            .exam-schedule th { background-color: transparent !important; }
        }
    </style>
</head>
<body>

    <button class="print-fab" onclick="window.print()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 4H6v-4h12v4z"></path></svg>
        Print Admit Card
    </button>

    <div class="admit-card">
        <div class="header">
            <h1 class="university-name">Doon University</h1>
            <div style="text-align: right; font-size: 0.75rem;">
                Official Admit Card<br>
                <?= date('Y') ?> Session
            </div>
        </div>

        <div class="document-title">ADMIT CARD (HALL TICKET)</div>

        <table class="infobox">
            <tr>
                <th>Department Name</th>
                <td><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Program</th>
                <td><?= htmlspecialchars($student['course'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Semester</th>
                <td><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Name of the Student</th>
                <td><strong><?= htmlspecialchars($student['name'] ?? 'N/A') ?></strong></td>
            </tr>
            <tr>
                <th>Father and Mother Name</th>
                <td><?= htmlspecialchars(($student['father_name'] ?? '') . ' and ' . ($student['mother_name'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>Enrollment No</th>
                <td><?= htmlspecialchars($student['enrollment_no'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>ABC ID</th>
                <td><?= htmlspecialchars($student['abc_id'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Physically Handicapped</th>
                <td><?= htmlspecialchars($student['is_ph'] ?? 'NO') ?></td>
            </tr>
            <tr>
                <th>Gender</th>
                <td><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Cast Category</th>
                <td><?= htmlspecialchars($student['cast_category'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td><?= htmlspecialchars($student['dob'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Mobile</th>
                <td><?= htmlspecialchars($student['mobile'] ?? 'N/A') ?></td>
            </tr>
        </table>

        <h2 class="section-title">Examination Schedule</h2>
        <table class="exam-schedule">
            <thead>
                <tr>
                    <th width="30%">Course Name</th>
                    <th width="15%">Course Code</th>
                    <th width="20%">Date of Exam</th>
                    <th width="17.5%">Signature of Student</th>
                    <th width="17.5%">Signature of Invigilator</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Data Structures</td>
                    <td style="text-align: center;">CS201</td>
                    <td style="text-align: center;">12 May 2026</td>
                    <td class="sig-cell"></td>
                    <td class="sig-cell"></td>
                </tr>
                <tr>
                    <td>Database Systems</td>
                    <td style="text-align: center;">CS202</td>
                    <td style="text-align: center;">15 May 2026</td>
                    <td class="sig-cell"></td>
                    <td class="sig-cell"></td>
                </tr>
                <tr>
                    <td>Discrete Mathematics</td>
                    <td style="text-align: center;">CS203</td>
                    <td style="text-align: center;">18 May 2026</td>
                    <td class="sig-cell"></td>
                    <td class="sig-cell"></td>
                </tr>
                <tr>
                    <td>Computer Architecture</td>
                    <td style="text-align: center;">CS204</td>
                    <td style="text-align: center;">21 May 2026</td>
                    <td class="sig-cell"></td>
                    <td class="sig-cell"></td>
                </tr>
                <tr>
                    <td>Operating Systems</td>
                    <td style="text-align: center;">CS205</td>
                    <td style="text-align: center;">24 May 2026</td>
                    <td class="sig-cell"></td>
                    <td class="sig-cell"></td>
                </tr>
            </tbody>
        </table>

        <div class="instructions">
            <h4>Important Instructions</h4>
            <ul>
                <li>Candidate must carry original University ID card.</li>
                <li>Report to exam center 30 minutes before commencement.</li>
                <li>Signatures in the table above must be captured in the presence of the invigilator.</li>
                <li>Malpractice in any form will lead to immediate disqualification.</li>
            </ul>
        </div>

        <div class="footer">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-text">Student Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-text">Controller of Examinations</div>
            </div>
        </div>
    </div>

</body>
</html>
