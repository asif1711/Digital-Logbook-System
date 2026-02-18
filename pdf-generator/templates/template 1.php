<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 15px;
        }

        .meta p {
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
        }

        .signature {
            margin-top: 40px;
        }

        .signature p {
            margin-bottom: 25px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Digital Logbook</h1>
        <p>Training & Work Placement Record</p>
    </div>

    <div class="meta">
        <p><strong>Student Name:</strong> <?= htmlspecialchars($student_name) ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($course_name) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="60%">Activity Description</th>
                <th width="20%">Hours</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($logbook_rows)): ?>
            <?php foreach ($logbook_rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['activity']) ?></td>
                    <td><?= htmlspecialchars($row['hours']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No logbook entries provided.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="signature">
        <p><strong>Supervisor Signature:</strong> _____________________________</p>
        <p><strong>Date:</strong> _____________________________</p>
    </div>

</body>
</html>
