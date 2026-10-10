<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processed Result Sheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Faculty Result Processing System</h1>
                <p class="text-xs text-gray-500">Analysis & Anomaly Detection Dashboard</p>
            </div>
            <a href="<?= site_url('result') ?>" class="bg-blue-600 text-white text-xs px-4 py-2 rounded hover:bg-blue-700">
                Upload Another File
            </a>
        </div>

        <?php if (!empty($students)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase tracking-wider border-b">
                            <th class="p-3 border">#</th>
                            <th class="p-3 border">Matric No</th>
                            <th class="p-3 border">Full Name</th>
                            <th class="p-3 border text-center">CA</th>
                            <th class="p-3 border text-center">Exam</th>
                            <th class="p-3 border text-center">Total</th>
                            <th class="p-3 border text-center">Grade</th>
                            <th class="p-3 border">Detected Anomalies / Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($students as $index => $row): ?>
                            <tr class="hover:bg-gray-50 <?= !empty($row['anomalies']) ? 'bg-red-50/40' : '' ?>">
                                <td class="p-3 border text-gray-500"><?= $index + 1 ?></td>
                                <td class="p-3 border font-semibold"><?= htmlspecialchars($row['matric']) ?></td>
                                <td class="p-3 border uppercase"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="p-3 border text-center"><?= $row['ca'] ?></td>
                                <td class="p-3 border text-center"><?= $row['exam'] ?></td>
                                <td class="p-3 border text-center font-bold"><?= $row['total'] ?></td>
                                <td class="p-3 border text-center font-bold"><?= $row['grade'] ?></td>
                                <td class="p-3 border">
                                    <?php if (empty($row['anomalies'])): ?>
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-semibold bg-green-100 text-green-800 rounded">
                                            Normal Pass
                                        </span>
                                    <?php else: ?>
                                        <?php foreach ($row['anomalies'] as $issue): ?>
                                            <span class="inline-block px-2 py-0.5 text-[10px] font-semibold mr-1 rounded <?= $issue === '0-Score' ? 'bg-black text-white' : ($issue === 'Borderline' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= $issue ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 text-sm">
                <p class="font-semibold mb-2">No structured tabular rows could be automatically parsed by regex.</p>
                <p class="mb-4">Here is the raw text extracted from the uploaded PDF document:</p>
                <pre class="bg-gray-800 text-green-400 p-4 rounded text-xs overflow-auto max-h-96"><?= htmlspecialchars($raw_text) ?></pre>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>