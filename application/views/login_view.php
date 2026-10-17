<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Faculty Result Audit System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center font-sans antialiased selection:bg-blue-100 selection:text-blue-900 p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-900 p-6 text-center">
            <div class="w-16 h-16 bg-white rounded-xl mx-auto flex items-center justify-center mb-4 p-1 shadow-sm">
                <img src="<?= base_url('images/run-colored-logo.png'); ?>" alt="RUN Logo" class="w-full h-full object-contain" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=RUN';">
            </div>
            <h2 class="text-xl font-bold text-white tracking-wide">Faculty Result System</h2>
            <p class="text-slate-400 text-sm mt-1">Secure Portal Access</p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if($this->session->flashdata('error')): ?>
                <div class="mb-4 bg-rose-50 text-rose-600 border border-rose-200 text-sm px-4 py-3 rounded-lg font-medium">
                    <?= $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>

            <?= form_open('auth/process_login', ['class' => 'space-y-5']); ?>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm text-slate-900 transition">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm text-slate-900 transition">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-sm transition mt-2">
                    Sign In
                </button>
            <?= form_close(); ?>

            <?php if($this->session->flashdata('success')): ?>
                <div class="mt-4 bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm px-4 py-3 rounded-lg font-medium text-center">
                    <?= $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-sm text-slate-600">Don't have an account? 
                    <a href="<?= site_url('auth/register') ?>" class="font-bold text-blue-600 hover:text-blue-800">Sign up here</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>