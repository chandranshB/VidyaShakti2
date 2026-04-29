<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.phone, u.profile_image, u.signature_image, s.* 
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
    <title>Admit Card — <?= htmlspecialchars($student['name']) ?> — Doon University</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #e8e8e8;
            color: #000;
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 24px 0;
            line-height: 1.4;
            font-size: 13px;
            -webkit-print-color-adjust: exact;
        }

        .admit-card {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 16mm 18mm;
            box-shadow: 0 4px 40px rgba(0,0,0,0.12);
            display: flex;
            flex-direction: column;
        }

        /* ── Header ── */
        .uni-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 3px;
        }

        .uni-logo { width: 65px; height: 65px; object-fit: contain; flex-shrink: 0; }
        .uni-info { flex: 1; }

        .uni-name {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            line-height: 1;
        }

        .uni-type { font-size: 0.75rem; font-style: italic; color: #333; margin-top: 2px; }
        .uni-addr { font-size: 0.65rem; color: #555; margin-top: 2px; }

        .rule-thick { border: none; border-top: 2px solid #000; margin: 6px 0 1px 0; }
        .rule-thin { border: none; border-top: 0.5px solid #000; margin: 0 0 10px 0; }

        /* ── Title ── */
        .title-block { text-align: center; margin-bottom: 12px; }

        .title-main {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.3rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
        }

        .title-session {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 1px;
        }

        .title-exam { font-size: 0.72rem; color: #444; margin-top: 1px; }

        /* ── Unified Student Info Block ── */
        .info-block {
            display: flex;
            border: 1.5px solid #000;
            margin-bottom: 12px;
        }

        /* Photo area */
        .ib-photo {
            width: 105px;
            padding: 6px;
            border-right: 1px solid #888;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .photo-box {
            width: 90px;
            height: 112px;
            border: 0.5px solid #aaa;
            overflow: hidden;
        }

        .photo-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-box .empty {
            height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 0.5rem; color: #aaa; text-transform: uppercase; text-align: center; padding: 4px;
        }

        /* Data area — single compact column */
        .ib-data {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        /* Name + Roll hero row */
        .ib-hero {
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid #888;
        }

        .ib-name-zone {
            flex: 1;
            padding: 6px 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .student-name {
            font-family: 'Libre Baskerville', serif;
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .ib-programme {
            font-size: 0.72rem;
            color: #333;
            margin-top: 2px;
        }

        .ib-roll-zone {
            border-left: 1px solid #888;
            padding: 6px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }

        .roll-label {
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #555;
        }

        .roll-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        /* Compact detail rows */
        .ib-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .d-row {
            display: flex;
            padding: 2.5px 10px;
            border-bottom: 0.5px solid #ddd;
            font-size: 0.73rem;
            line-height: 1.45;
        }

        .d-row:nth-child(odd) { border-right: 0.5px solid #ddd; }
        .d-row.full { grid-column: 1 / -1; border-right: none; }
        .d-row.last { border-bottom: none; }

        .d-label {
            width: 90px;
            font-weight: 600;
            color: #444;
            flex-shrink: 0;
            font-size: 0.68rem;
        }

        .d-val { flex: 1; font-weight: 500; }

        /* ── Signatures (between info and schedule) ── */
        .sig-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 14px;
            padding: 0 4px;
        }

        .sig-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 170px;
        }

        .sig-img { max-width: 140px; max-height: 45px; object-fit: contain; margin-bottom: 3px; }
        .sig-space { height: 45px; }
        .sig-line { width: 100%; border-top: 0.75px solid #000; margin-bottom: 3px; }
        .sig-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }

        /* ── Exam Table ── */
        .schedule-header {
            font-family: 'Libre Baskerville', serif;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .exam-table th {
            border: 1px solid #888;
            padding: 4px 6px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            letter-spacing: 0.2px;
        }

        .exam-table td {
            border: 1px solid #aaa;
            padding: 5px 6px;
            font-size: 0.75rem;
        }

        .exam-table .c-sno { width: 5%; text-align: center; }
        .exam-table .c-name { width: 36%; }
        .exam-table .c-code { width: 11%; text-align: center; }
        .exam-table .c-date { width: 13%; text-align: center; }
        .exam-table .c-sign { width: 14%; }

        .exam-table td.sno { text-align: center; font-weight: 600; }
        .exam-table td.code { text-align: center; font-weight: 600; font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; }
        .exam-table td.date { text-align: center; }

        /* ── Footer ── */
        .footer-note {
            margin-top: auto;
            padding-top: 10px;
            border-top: 0.5px solid #ccc;
            font-size: 0.6rem;
            color: #666;
            line-height: 1.5;
        }

        .footer-note strong { color: #333; }

        /* ── Print ── */
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .admit-card { box-shadow: none; margin: 0; width: 100%; min-height: 100vh; padding: 14mm 16mm; }
            .print-fab { display: none !important; }
        }

        .print-fab {
            position: fixed; bottom: 36px; right: 36px;
            background: #111; color: #fff; border: none;
            padding: 14px 28px; border-radius: 50px;
            font-size: 0.95rem; font-weight: 700; cursor: pointer;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            display: flex; align-items: center; gap: 10px;
            z-index: 1000; transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .print-fab:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(0,0,0,0.4); }
    </style>
</head>
<body>

<button class="print-fab" onclick="window.print()">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 4H6v-4h12v4z"></path></svg>
    Print / Save PDF
</button>

<div class="admit-card">

    <!-- Header -->
    <div class="uni-header">
        <img src="assets/logo.svg" alt="Doon University" class="uni-logo">
        <div class="uni-info">
            <div class="uni-name">Doon University</div>
            <div class="uni-type">A State University</div>
            <div class="uni-addr">Kedarpur, Mothrowala Road, Dehradun, Uttarakhand — 248001</div>
        </div>
    </div>
    <hr class="rule-thick">
    <hr class="rule-thin">

    <!-- Title -->
    <div class="title-block">
        <div class="title-main">Admit Card</div>
        <div class="title-session">For the Session 2025–26</div>
        <div class="title-exam">Semester Examinations — May <?= date('Y') ?></div>
    </div>

    <!-- Unified Compact Student Info -->
    <div class="info-block">
        <!-- Photo -->
        <div class="ib-photo">
            <div class="photo-box">
                <?php if (!empty($student['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($student['profile_image']) ?>" alt="Photo">
                <?php else: ?>
                    <div class="empty">Affix photo</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Data -->
        <div class="ib-data">
            <!-- Name + Roll (hero row) -->
            <div class="ib-hero">
                <div class="ib-name-zone">
                    <div class="student-name"><?= htmlspecialchars($student['name'] ?? 'N/A') ?></div>
                    <div class="ib-programme"><?= htmlspecialchars($student['course'] ?? 'N/A') ?> · Dept. of <?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></div>
                </div>
                <div class="ib-roll-zone">
                    <div class="roll-label">Roll No.</div>
                    <div class="roll-value"><?= htmlspecialchars($student['roll_no'] ?? $student['enrollment_no'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Compact detail rows -->
            <div class="ib-details">
                <div class="d-row">
                    <span class="d-label">Enrollment</span>
                    <span class="d-val"><?= htmlspecialchars($student['enrollment_no'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">Semester</span>
                    <span class="d-val"><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">Father</span>
                    <span class="d-val"><?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">Mother</span>
                    <span class="d-val"><?= htmlspecialchars($student['mother_name'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">DOB / Gender</span>
                    <span class="d-val"><?= htmlspecialchars(($student['dob'] ?? 'N/A') . ' / ' . ($student['gender'] ?? 'N/A')) ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">Category / PwD</span>
                    <span class="d-val"><?= htmlspecialchars(($student['cast_category'] ?? 'N/A') . ' / ' . ($student['is_ph'] ?? 'NO')) ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">ABC ID</span>
                    <span class="d-val"><?= htmlspecialchars($student['abc_id'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row">
                    <span class="d-label">Mobile</span>
                    <span class="d-val"><?= htmlspecialchars($student['mobile'] ?? $student['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="d-row full last">
                    <span class="d-label">Email</span>
                    <span class="d-val"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="sig-row">
        <div class="sig-group">
            <div class="sig-space"></div>
            <div class="sig-line"></div>
            <div class="sig-label">Signature of HOD</div>
        </div>
        <div class="sig-group">
            <?php if (!empty($student['signature_image'])): ?>
                <img src="<?= htmlspecialchars($student['signature_image']) ?>" class="sig-img" alt="Signature">
            <?php else: ?>
                <div class="sig-space"></div>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div class="sig-label">Signature of Student</div>
        </div>
    </div>

    <!-- Examination Schedule -->
    <div class="schedule-header">List of Courses in which Student will Appear</div>
    <table class="exam-table">
        <thead>
            <tr>
                <th class="c-sno">S.No</th>
                <th class="c-name">Course Name</th>
                <th class="c-code">Code</th>
                <th class="c-date">Date</th>
                <th class="c-sign">Student Sign.</th>
                <th class="c-sign">Invigilator Sign.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="sno">1</td>
                <td>CSC251 – Database Management System</td>
                <td class="code">724111</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">2</td>
                <td>CSC252 – Numerical and Statistical Computing</td>
                <td class="code">724112</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">3</td>
                <td>CSC253 – Design and Analysis of Algorithms</td>
                <td class="code">724113</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">4</td>
                <td>CSS250 – Server-side Web Technologies</td>
                <td class="code">724211</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">5</td>
                <td>CSS250 – Server-side Web Technologies</td>
                <td class="code">724211</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">6</td>
                <td>DNG251 – Interaction Design B</td>
                <td class="code">214411</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">7</td>
                <td>DUA103 – Spanish Language II</td>
                <td class="code">100514</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
            <tr>
                <td class="sno">8</td>
                <td>DUV111 – Mathematical Ability for Competitive Exam</td>
                <td class="code">100621</td>
                <td class="date"></td>
                <td></td><td></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer-note">
        <strong>Instructions:</strong>
        (1) This admit card must be presented at every examination along with a valid university ID card.
        (2) Students must reach the examination hall 30 minutes before the scheduled time.
        (3) Electronic devices including mobile phones are strictly prohibited inside the exam hall.
        (4) Students found using unfair means will be subject to disciplinary action as per university regulations.
    </div>

</div>

</body>
</html>
