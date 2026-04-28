<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.name, s.enrollment_no, s.course, s.batch_year 
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
    <title>Admit Card - Doon University</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap');
        
        body {
            background: #e2e8f0;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            padding: 2rem;
            color: #1e293b;
        }
        
        .admit-card {
            background: #ffffff;
            width: 800px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-top: 10px solid #6366f1;
            position: relative;
        }

        .university-header {
            text-align: center;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .university-header h1 {
            font-family: 'Playfair Display', serif;
            color: #0f172a;
            font-size: 2.5rem;
            margin: 0;
            letter-spacing: 1px;
        }

        .university-header p {
            color: #64748b;
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
        }

        .card-title {
            text-align: center;
            font-weight: 600;
            font-size: 1.3rem;
            color: #6366f1;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .student-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-group {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        .exam-table th, .exam-table td {
            border: 1px solid #cbd5e1;
            padding: 1rem;
            text-align: left;
        }

        .exam-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 5rem;
            padding-top: 1rem;
        }

        .signature-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid #94a3b8;
            padding-top: 0.5rem;
            font-size: 0.9rem;
            color: #475569;
        }

        .print-btn {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #6366f1;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: transform 0.2s;
        }

        .print-btn:hover {
            transform: translateY(-2px);
        }

        @media print {
            body { background: white; padding: 0; }
            .admit-card { box-shadow: none; border: none; padding: 0; width: 100%; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print Admit Card</button>

<div class="admit-card">
    <div class="university-header">
        <h1>Doon University</h1>
        <p>Mothrowala Road, Kedarpur, Dehradun, Uttarakhand 248001</p>
    </div>

    <div class="card-title">Hall Ticket / Admit Card - Semester End Examinations</div>

    <div class="student-details">
        <div class="detail-group">
            <div class="detail-label">Student Name</div>
            <div class="detail-value"><?= htmlspecialchars($student['name']) ?></div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Enrollment Number</div>
            <div class="detail-value"><?= htmlspecialchars($student['enrollment_no']) ?></div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Course / Program</div>
            <div class="detail-value"><?= htmlspecialchars($student['course']) ?></div>
        </div>
        <div class="detail-group">
            <div class="detail-label">Batch Year</div>
            <div class="detail-value"><?= htmlspecialchars($student['batch_year']) ?></div>
        </div>
    </div>

    <table class="exam-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>12 May 2026</td>
                <td>CS201</td>
                <td>Data Structures</td>
                <td>10:00 AM - 01:00 PM</td>
            </tr>
            <tr>
                <td>15 May 2026</td>
                <td>CS202</td>
                <td>Database Systems</td>
                <td>10:00 AM - 01:00 PM</td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">Student Signature</div>
        <div class="signature-box">Controller of Examinations</div>
    </div>
</div>

</body>
</html>
