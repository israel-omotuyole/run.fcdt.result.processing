<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Result Audit System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1d4ed8',
                        brandBg: '#f8fafc',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brandBg text-slate-800 min-h-screen flex flex-col font-sans antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    FR
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight">Faculty Result Audit System</h1>
                    <p class="text-xs text-slate-500 font-medium">Academic Session: <?= htmlspecialchars($session_name ?? '2025/2026 — Second Semester') ?></p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-slate-500">Role:</span>
                    <span class="font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md"><?= htmlspecialchars($user_role ?? 'Exam Officer') ?></span>
                </div>
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs flex items-center justify-center border border-blue-200">
                    EO
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">

        <!-- Top Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Audited -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Total Audited</p>
                <div class="my-2">
                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $total_audited ?? 148 ?></span>
                </div>
                <p class="text-xs text-slate-500">400 Level Candidates</p>
            </div>

            <!-- Score Anomalies -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-rose-600 uppercase">Score Anomalies</p>
                <div class="my-2">
                    <span class="text-3xl font-extrabold text-rose-600 tracking-tight"><?= sprintf("%02d", $score_anomalies ?? 5) ?></span>
                </div>
                <p class="text-xs text-slate-500">Zero / Incomplete Scores</p>
            </div>

            <!-- Borderline Scores -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-amber-600 uppercase">Borderline Scores</p>
                <div class="my-2">
                    <span class="text-3xl font-extrabold text-amber-600 tracking-tight"><?= sprintf("%02d", $borderline_scores ?? 11) ?></span>
                </div>
                <p class="text-xs text-slate-500">Scores at 39, 49, 59</p>
            </div>

            <!-- Cleared for Grad -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-emerald-600 uppercase">Cleared for Grad</p>
                <div class="my-2">
                    <span class="text-3xl font-extrabold text-emerald-600 tracking-tight"><?= $cleared_for_grad ?? 132 ?></span>
                </div>
                <p class="text-xs text-slate-500">All Requirements Met</p>
            </div>
        </div>

        <!-- Filter & Action Controls -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex flex-1 w-full md:w-auto items-center space-x-3">
                <div class="relative w-full max-w-xs">
                    <input type="text" id="searchInput" placeholder="Search Matric No or Name..." 
                           class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-800 placeholder-slate-400 transition">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <select id="statusFilter" class="py-2 px-3 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700">
                    <option value="ALL">All Statuses</option>
                    <option value="CLEARED">Cleared</option>
                    <option value="PENDING">Pending Review</option>
                    <option value="UNCLEARED">Uncleared</option>
                </select>
            </div>

            <div class="flex items-center space-x-3 w-full md:w-auto justify-end">
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Upload Result File
                </button>
                <a href="<?= site_url('result/export_broadsheet') ?>" 
                   class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                    Export Board Sheet
                </a>
            </div>
        </div>

        <!-- Audit Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Graduation Clearance & Audit Sheet</h2>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Department: <span class="font-semibold text-slate-700"><?= htmlspecialchars($department_name ?? 'Information Technology') ?></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="auditTable">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Matric No</th>
                            <th class="py-3.5 px-6">Student Name</th>
                            <th class="py-3.5 px-4 text-center">TUR</th>
                            <th class="py-3.5 px-4 text-center">TUP</th>
                            <th class="py-3.5 px-4 text-center">CGPA</th>
                            <th class="py-3.5 px-6">Audit / Anomaly Flags</th>
                            <th class="py-3.5 px-6 text-center">Graduation Status</th>
                            <th class="py-3.5 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if (!empty($candidates)): ?>
                            <?php foreach ($candidates as $row): ?>
                                <tr class="hover:bg-slate-50/60 transition" data-status="<?= strtoupper($row['grad_status_raw']) ?>">
                                    <td class="py-4 px-6 font-semibold text-slate-900 whitespace-nowrap"><?= htmlspecialchars($row['matric']) ?></td>
                                    <td class="py-4 px-6 font-medium whitespace-nowrap"><?= htmlspecialchars($row['name']) ?></td>
                                    
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <?php if ((int)$row['tur'] > 24): ?>
                                            <span class="font-bold text-rose-600"><?= $row['tur'] ?>!</span>
                                        <?php else: ?>
                                            <span class="text-slate-600 font-medium"><?= $row['tur'] ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="py-4 px-4 text-center text-slate-600 font-medium whitespace-nowrap"><?= $row['tup'] ?></td>
                                    <td class="py-4 px-4 text-center font-bold text-slate-900 whitespace-nowrap"><?= number_format($row['cgpa'], 2) ?></td>
                                    
                                    <!-- Anomaly Flag Badges -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1.5 items-center">
                                            <?php foreach ($row['flags'] as $flag): ?>
                                                <?php if ($flag['type'] === 'clean'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Clean
                                                    </span>
                                                <?php elseif ($flag['type'] === 'borderline'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                        <?= htmlspecialchars($flag['label']) ?>
                                                    </span>
                                                <?php elseif ($flag['type'] === 'error'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                                        <?= htmlspecialchars($flag['label']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>

                                    <!-- Graduation Status Pill -->
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <?php if ($row['grad_status_raw'] === 'cleared'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <?= htmlspecialchars($row['grad_status_label']) ?>
                                            </span>
                                        <?php elseif ($row['grad_status_raw'] === 'pending'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Pending Review
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Uncleared
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Action -->
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <?php if ($row['grad_status_raw'] === 'cleared'): ?>
                                            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium text-xs">View Sheet</a>
                                        <?php elseif ($row['grad_status_raw'] === 'pending'): ?>
                                            <a href="#" class="text-amber-600 hover:text-amber-800 font-medium text-xs">Review</a>
                                        <?php else: ?>
                                            <a href="#" class="text-rose-600 hover:text-rose-800 font-medium text-xs">Audit Fault</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-sm text-slate-400">
                                    No result file uploaded yet. Click <strong>Upload Result File</strong> above to audit a course score sheet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Upload Modal Dialog -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 border border-slate-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-bold text-slate-900">Upload Result File</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <?= form_open_multipart('result/process_upload'); ?>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-2">Select Result Sheet (.pdf)</label>
                    <input type="file" name="result_file" required accept=".pdf"
                           class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1.5">
                </div>
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">Process & Audit</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>

    <!-- Live Client-Side Search & Filter Script -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('#auditTable tbody tr');

        function filterTable() {
            const query = searchInput.value.toLowerCase();
            const filterValue = statusFilter.value;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const status = row.getAttribute('data-status');

                const matchesQuery = text.includes(query);
                const matchesStatus = (filterValue === 'ALL' || status === filterValue);

                row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>