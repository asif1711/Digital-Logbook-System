<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        h1 {
            border-bottom: 1px solid #000;
        }
    </style>
</head>
<body>

<h1>Simple Logbook</h1>

<p><strong>Student:</strong> <?= htmlspecialchars($student_name) ?></p>
<p><strong>Course:</strong> <?= htmlspecialchars($course_name) ?></p>

<hr>

<?php foreach ($logbook_rows as $row): ?>
    <p>
        <strong>Date:</strong> <?= htmlspecialchars($row['date']) ?><br>
        <strong>Activity:</strong> <?= htmlspecialchars($row['activity']) ?><br>
        <strong>Hours:</strong> <?= htmlspecialchars($row['hours']) ?>
    </p>
<?php endforeach; ?>

</body>
</html>
