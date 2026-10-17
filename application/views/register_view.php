<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Faculty Result Audit System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center font-sans antialiased p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-900 p-6 text-center">
            <h2 class="text-xl font-bold text-white tracking-wide">Create Staff Account</h2>
            <p class="text-slate-400 text-sm mt-1">Faculty Result Audit System</p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if($this->session->flashdata('error')): ?>
                <div class="mb-4 bg-rose-50 text-rose-600 border border-rose-200 text-sm px-4 py-3 rounded-lg font-medium">
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?= form_open('auth/process_register', ['class' => 'space-y-4']); ?>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name</label>
                    <input type="text" name="fullname" required placeholder="Dr. Jane Doe" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Staff Email</label>
                    <input type="email" name="email" required placeholder="doe.j@run.edu.ng" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-900">
                    <p class="text-[10px] text-slate-500 mt-1">Must be an official @run.edu.ng address.</p>
                </div>

                <!-- THE NEW FACULTY PIN FIELD -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Faculty Access PIN</label>
                    <input type="password" name="faculty_pin" required placeholder="Enter the secret department code" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">System Role</label>
                    <select name="role" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-900">
                        <option value="exam_officer">Exam Officer</option>
                        <option value="dean">Dean / Sub-Dean</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                    <input type="password" name="password" required placeholder="Create a strong password" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm text-slate-900">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition mt-2">
                    Register Account
                </button>
            <?= form_close(); ?>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-slate-600">Already have an account? 
                    <a href="<?= site_url('auth') ?>" class="font-bold text-blue-600 hover:text-blue-800">Sign in here</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>