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
<body class="bg-brandBg text-slate-800 min-h-screen flex flex-col font-sans antialiased selection:bg-blue-100 selection:text-blue-900">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-3">
            
            <!-- Left Side: Logo & Level Selector -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- University Logo Container (Flexible Width) -->
                <div class="h-10 sm:h-12 flex-shrink-0 flex items-center">
                    <img src="<?= base_url('images/run-colored-logo.png'); ?>" 
                         alt="RUN Logo" 
                         onerror="this.onerror=null; this.src='https://placehold.co/200x50?text=RUN';" 
                         class="h-full w-auto object-contain drop-shadow-sm">
                </div>
                
                <div>
                    <h1 class="text-sm sm:text-base font-extrabold text-slate-900 leading-tight tracking-tight">Faculty Result Audit System</h1>
                    <div class="flex items-center space-x-2 mt-0.5">
                        <p class="text-[11px] sm:text-xs text-slate-500 font-medium"><?= htmlspecialchars($session_name ?? '2025/2026 — First Semester') ?></p>
                        <span class="text-slate-300">|</span>
                        
                       <!-- Dynamic Context Selector Form -->
                        <form action="<?= site_url($this->router->fetch_class() . '/change_context') ?>" method="POST" class="inline flex items-center space-x-1.5 sm:space-x-2">
                            <!-- Department Dropdown -->
                            <select name="active_dept" onchange="this.form.submit()" class="text-[11px] sm:text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded px-1.5 py-0.5 focus:outline-none cursor-pointer hover:bg-slate-100 transition">
                                <option value="Information Technology" <?= (isset($active_dept) && $active_dept == 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Computer Science" <?= (isset($active_dept) && $active_dept == 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Cyber Security" <?= (isset($active_dept) && $active_dept == 'Cyber Security') ? 'selected' : '' ?>>Cyber Security</option>
                            </select>

                            <span class="text-slate-300">/</span>

                            <!-- Level Dropdown -->
                            <select name="active_level" onchange="this.form.submit()" class="text-[11px] sm:text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5 focus:outline-none cursor-pointer hover:bg-blue-100 transition">
                                <option value="100" <?= (isset($active_level) && $active_level == 100) ? 'selected' : '' ?>>100 Level</option>
                                <option value="200" <?= (isset($active_level) && $active_level == 200) ? 'selected' : '' ?>>200 Level</option>
                                <option value="300" <?= (isset($active_level) && $active_level == 300) ? 'selected' : '' ?>>300 Level</option>
                                <option value="400" <?= (!isset($active_level) || $active_level == 400) ? 'selected' : '' ?>>400 Level</option>
                            </select>

                            <span class="text-slate-300">/</span>

                            <!-- Semester Dropdown (NEW) -->
                            <select name="active_semester" onchange="this.form.submit()" class="text-[11px] sm:text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-1.5 py-0.5 focus:outline-none cursor-pointer hover:bg-emerald-100 transition">
                                <option value="First Semester" <?= (!isset($active_semester) || $active_semester == 'First Semester') ? 'selected' : '' ?>>First Semester</option>
                                <option value="Second Semester" <?= (isset($active_semester) && $active_semester == 'Second Semester') ? 'selected' : '' ?>>Second Semester</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side: User Profile & Logout -->
            <div class="flex items-center space-x-3 sm:space-x-4 ml-auto sm:ml-0">
                
                <!-- Staff Name & Role Details -->
                <div class="hidden xs:flex flex-col items-end text-right">
                    <span class="font-bold text-slate-800 text-[11px] sm:text-xs">
                        <?= htmlspecialchars($this->session->userdata('fullname') ?? 'Staff Member') ?>
                    </span>
                    <div class="flex items-center space-x-1 mt-0.5">
                        <span class="font-medium text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] uppercase tracking-wide">
                            <?= $this->session->userdata('role') === 'dean' ? 'Dean / Sub-Dean' : 'Exam Officer' ?>
                        </span>
                    </div>
                </div>

                <!-- Dynamic Initials Avatar -->
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-50 text-blue-700 font-bold text-xs sm:text-sm flex items-center justify-center border border-blue-200 shrink-0">
                    <?= $this->session->userdata('role') === 'dean' ? 'DN' : 'EO' ?>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

                <!-- Logout Button -->
                    <a href="<?= site_url('auth/logout') ?>" 
                    onclick="return confirm('Are you sure you want to log out of the Faculty Result Audit System?');"
                    title="Sign Out"
                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
            </div>
            
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 w-full flex-1">

        <!-- Top Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-6 sm:mb-8">
            <!-- Total Audited -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Total Audited</p>
                <div class="my-1.5 sm:my-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight"><?= $total_audited ?? 0 ?></span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500"><?= $active_level ?? 400 ?> Level Candidates</p>
            </div>

            <!-- Score Anomalies -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-rose-600 uppercase">Score Anomalies</p>
                <div class="my-1.5 sm:my-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 tracking-tight"><?= sprintf("%02d", $score_anomalies ?? 0) ?></span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500">Zero / Incomplete Scores</p>
            </div>

            <!-- Borderline Scores -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-amber-600 uppercase">Borderline Scores</p>
                <div class="my-1.5 sm:my-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight"><?= sprintf("%02d", $borderline_scores ?? 0) ?></span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500">Scores at 39, 49, 59, 69</p>
            </div>

            <!-- Contextual Status Card: Cleared for Grad / Good Standing -->
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <p class="text-[11px] font-bold tracking-wider text-emerald-600 uppercase">
                    <?= (isset($active_level) &&$active_level < 400) ? 'Good Standing' : 'Cleared for Grad' ?>
                </p>
                <div class="my-1.5 sm:my-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight"><?= $cleared_for_grad ?? 0 ?></span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-500">
                    <?= (isset($active_level) &&$active_level < 400) ? 'Cleared for Next Session' : 'All Requirements Met' ?>
                </p>
            </div>
        </div>

        <!-- Filter & Action Controls -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <div class="flex flex-col sm:flex-row flex-1 items-stretch sm:items-center gap-2.5">
                <div class="relative w-full sm:w-72">
                    <input type="text" id="searchInput" placeholder="Search Matric No or Name..." 
                           class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-slate-800 placeholder-slate-400 transition">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <select id="statusFilter" class="py-2 px-3 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700">
                    <option value="ALL">All Statuses</option>
                    <option value="CLEARED">Cleared</option>
                    <option value="PENDING">Pending Review</option>
                    <option value="UNCLEARED">Uncleared</option>
                </select>
            </div>

            <div class="flex items-center space-x-2 pt-1 lg:pt-0">
                <!-- ONLY Exam Officers can see the Upload Button -->
                <?php if ($this->session->userdata('role') === 'exam_officer'): ?>
                    <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center px-3.5 py-2 text-xs sm:text-sm font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Upload Result File
                    </button>

                    <a href="<?= site_url("result/reset_session_records") ?>" onclick="return confirm('WARNING: This will completely wipe all uploaded student results and grades for this level. Are you sure?');" class="inline-flex items-center justify-center px-3.5 py-2 text-xs sm:text-sm font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear Level Records
                    </a>
                <?php endif; ?>
                
                <!-- Dynamically routes export to 'result' or 'dean' based on who is logged in -->
                <a href="<?= site_url(strtolower($this->router->fetch_class()) . '/export_broadsheet') ?>" 
                class="flex-1 sm:flex-initial inline-flex items-center justify-center px-3.5 py-2 text-xs sm:text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm text-center">
                    Export Board Sheet
                </a>
            </div>
        </div>

        <!-- Audit Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wide">General Clearance & Audit Sheet</h2>
                <div class="text-[11px] sm:text-xs text-slate-500 font-medium">
                    Department: <span class="font-semibold text-slate-700"><?= htmlspecialchars($department_name ?? 'Information Technology') ?></span>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-xs sm:text-sm min-w-[700px]" id="auditTable">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 sm:px-6">Matric No</th>
                            <th class="py-3 px-4 sm:px-6">Student Name</th>
                            <th class="py-3 px-3 text-center">TUR</th>
                            <th class="py-3 px-3 text-center">TUP</th>
                            <th class="py-3 px-3 text-center">CGPA</th>
                            <th class="py-3 px-4 sm:px-6">Audit / Anomaly Flags</th>
                            <th class="py-3 px-4 sm:px-6 text-center">General Status</th>
                            <th class="py-3 px-4 sm:px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if (!empty($candidates)): ?>
                            <?php foreach ($candidates as$row): ?>
                                <tr class="hover:bg-slate-50/60 transition" data-status="<?= strtoupper($row['grad_status_raw']) ?>">
                                    <td class="py-3.5 px-4 sm:px-6 font-semibold text-slate-900 whitespace-nowrap"><?= htmlspecialchars($row['matric']) ?></td>
                                    <td class="py-3.5 px-4 sm:px-6 font-medium whitespace-nowrap"><?= htmlspecialchars($row['name']) ?></td>
                                    
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <?php if ((int)$row['tur'] > 24): ?>
                                            <span class="font-bold text-rose-600"><?= $row['tur'] ?>!</span>
                                        <?php else: ?>
                                            <span class="text-slate-600 font-medium"><?= $row['tur'] ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="py-3.5 px-3 text-center text-slate-600 font-medium whitespace-nowrap"><?= $row['tup'] ?></td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-900 whitespace-nowrap"><?= number_format($row['cgpa'], 2) ?></td>
                                    
                                    <!-- Anomaly Flag Badges -->

                                    <!-- Status Pill -->
                                    <td class="py-3.5 px-4 sm:px-6 text-center whitespace-nowrap">
                                        <?php if ($row['grad_status_raw'] === 'cleared'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <?= htmlspecialchars($row['grad_status_label']) ?>
                                            </span>
                                        <?php elseif ($row['grad_status_raw'] === 'pending'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] sm:text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Pending Review
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] sm:text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Uncleared
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Role-Based Action Column -->
                                    <td class="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap font-medium text-xs">
                                        <?php if ($row['grad_status_raw'] === 'pending' &&$this->session->userdata('role') === 'dean'): ?>
                                            <!-- DEAN ONLY: Approve Waiver -->
                                            <a href="javascript:void(0)" 
                                               onclick="approveWaiver('<?= htmlspecialchars(addslashes($row['matric']), ENT_QUOTES) ?>')" 
                                               class="text-amber-600 hover:text-amber-800 font-bold bg-amber-50 px-2 py-1 rounded border border-amber-200 transition">
                                                Approve Waiver
                                            </a>
                                        <?php else: ?>
                                            <!-- EVERYONE ELSE: View Breakdown -->
                                            <a href="javascript:void(0)" 
                                            onclick="openStudentSheet('<?= $row['id'] ?>', '<?= htmlspecialchars(addslashes($row['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($row['matric']), ENT_QUOTES) ?>')" 
                                            class="text-blue-600 hover:text-blue-800 font-semibold underline decoration-transparent hover:decoration-blue-600 transition">
                                                View Sheet
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-10 px-4 text-xs sm:text-sm text-slate-400">
                                    No result file uploaded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Upload Modal Dialog -->
    <?php if ($this->session->userdata('role') === 'exam_officer'): ?>
    <div id="uploadModal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-5 sm:p-6 border border-slate-200 animate-in fade-in zoom-in duration-150">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Upload Result File</h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <?= form_open_multipart('result/process_upload'); ?>
                <!-- Hidden inputs tag the upload with the correct department and level -->
                <input type="hidden" name="upload_level" value="<?= $active_level ?? 400 ?>">
                <input type="hidden" name="upload_dept" value="<?= htmlspecialchars($active_dept ?? 'Information Technology') ?>">
                
                <div class="mb-4">
                    <label class="block text-[11px] sm:text-xs font-semibold text-slate-600 uppercase mb-2">
                        Select <?= $active_level ?? 400 ?>L Result Sheet for <?= htmlspecialchars($active_dept ?? 'Information Technology') ?> (.pdf)
                    </label>
                    <input type="file" name="result_file" required accept=".pdf"
                           class="w-full text-xs sm:text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1.5">
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-3.5 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">Process & Audit</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Student Score Breakdown Modal -->
    <div id="sheetModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-5 sm:p-6 border border-slate-200 animate-in fade-in zoom-in duration-150">
            <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-4">
                <div>
                    <h3 id="sheetModalName" class="text-sm sm:text-base font-bold text-slate-900"></h3>
                    <p id="sheetModalMatric" class="text-xs text-slate-500 font-medium"></p>
                </div>
                <button onclick="document.getElementById('sheetModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 font-bold p-1">
                    ✕
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[11px]">
                            <th class="py-2.5 px-3">Course Code</th>
                            <th class="py-2.5 px-3 text-center">Unit</th>
                            <th class="py-2.5 px-3 text-center">Total Score</th>
                            <th class="py-2.5 px-3 text-center">Grade</th>
                            <th class="py-2.5 px-3">Audit Flag</th>
                        </tr>
                    </thead>
                    <tbody id="modalScoreBody" class="divide-y divide-slate-100 text-slate-700">
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-4 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('sheetModal').classList.add('hidden')" class="px-4 py-1.5 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Close</button>
            </div>
        </div>
    </div>

    <!-- Client-Side Scripts -->
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
                if (!status) return;

                const matchesQuery = text.includes(query);
                const matchesStatus = (filterValue === 'ALL' || status === filterValue);

                row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
            });
        }

        if(searchInput && statusFilter) {
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
        }

        function openStudentSheet(studentId, studentName, matric) {
            // 1. Open modal and set headers
            document.getElementById('sheetModal').classList.remove('hidden');
            document.getElementById('sheetModalName').textContent = studentName;
            document.getElementById('sheetModalMatric').textContent = matric;
            
            const tbody = document.getElementById('modalScoreBody');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-sm py-6 text-slate-500">Loading scores...</td></tr>';

            // 2. Dynamically fetch from the correct controller (Dean or Result)
            const currentController = '<?= strtolower($this->router->fetch_class()) ?>';
            const targetUrl = '<?= site_url() ?>/' + currentController + '/get_student_sheet/' + studentId;
            
            fetch(targetUrl)
            .then(response => {
                if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                return response.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Server Response (Not JSON):", text);
                    throw new Error('Server returned invalid data format.');
                }

                if (!data || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-sm py-6 text-red-500 font-medium">No courses found for this student.</td></tr>';
                    return;
                }

                let rowsHtml = '';
                data.forEach(course => {
                    let grade = 'F';
                    let gradeColor = 'text-red-600 font-bold';
                    
                    if (course.score >= 70) { grade = 'A'; gradeColor = 'text-green-600 font-bold'; }
                    else if (course.score >= 60) { grade = 'B'; gradeColor = 'text-green-600 font-bold'; }
                    else if (course.score >= 50) { grade = 'C'; gradeColor = 'text-blue-600 font-bold'; }
                    else if (course.score >= 45) { grade = 'D'; gradeColor = 'text-amber-600 font-bold'; }
                    else if (course.score >= 40) { grade = 'E'; gradeColor = 'text-amber-600 font-bold'; }

                    let flagBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">Normal</span>`;
                    if (course.status && course.status !== 'Normal') {
                        if (course.status.includes('Borderline')) {
                            flagBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">${course.status}</span>`;
                        } else {
                            flagBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-700 border border-red-200">${course.status}</span>`;
                        }
                    }

                    rowsHtml += `
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 text-xs font-semibold text-slate-800">${course.courseCode}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 text-center">${course.unit}</td>
                            <td class="px-4 py-3 text-xs font-bold text-slate-700 text-center">${course.score}</td>
                            <td class="px-4 py-3 text-xs text-center ${gradeColor}">${grade}</td>
                            <td class="px-4 py-3 text-xs text-center">${flagBadge}</td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = rowsHtml;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-sm py-6 text-red-500 font-medium">Failed to load scores. Check console for details.</td></tr>';
            });
        }
        
        function approveWaiver(matric) {
            if (confirm('Approve extra credit units and waive limits for ' + matric + '?')) {
                
                // Explicitly include index.php to prevent 404 routing errors on local servers
                fetch('<?= site_url("dean/approve_waiver") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'matric=' + encodeURIComponent(matric)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Server returned status ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Database Error: ' + (data.message || 'Could not approve waiver.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Server Error: Could not reach the backend.');
                });
            }
        }
    </script>
</body>
</html>