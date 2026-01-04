<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Flor de Liz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100">
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Get errors from session and clear them
    $errors = $_SESSION['errors'] ?? [];
    if (!empty($errors)) {
        unset($_SESSION['errors']);
    }
    ?>
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-teal-600 to-teal-800 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-white font-bold text-2xl">FL</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Staff Portal</h1>
                <p class="text-gray-600 mt-2">Sign in to manage your tasks</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-red-600 text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="/config/auth.php" class="bg-white rounded-xl shadow-lg p-8">
                <input type="hidden" name="action" value="login_staff">

                <div id="fdl-error" class="mb-4 p-2 bg-red-50 border border-red-200 text-red-700 rounded hidden"></div>

                <div class="mb-6">
                    <label for="username" class="block text-sm font-semibold text-gray-900 mb-2">Username</label>
                    <input 
                        type="text" 
                        id="username"
                        name="username" 
                        required
                        placeholder="Enter your username"
                        maxlength="50"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"
                    >
                </div>

                <div class="mb-8">
                    <label for="password" class="block text-sm font-semibold text-gray-900 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        required
                        placeholder="Enter your password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full py-2 bg-gradient-to-r from-teal-600 to-teal-700 text-white font-semibold rounded-lg hover:from-teal-700 hover:to-teal-800 transition shadow-lg"
                >
                    Sign In
                </button>
            </form>

            <!-- Links -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Admin? <a href="/pages/login_admin.php" class="text-teal-600 hover:text-teal-700 font-semibold">Login here</a>
                </p>
                <p class="text-gray-600 mt-2">
                    <a href="/" class="text-teal-600 hover:text-teal-700 font-semibold">Back to home</a>
                </p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action="/config/auth.php"]');
            const errorEl = document.getElementById('fdl-error');
            if (!form || !errorEl) return;

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                const formData = new FormData(form);

                try {
                    const url = form.getAttribute('action') || form.action;
                    const res = await fetch(url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });
                    const defaultMsg = 'The username or password you entered is incorrect. Please try again.';

                    const contentType = res.headers.get('content-type') || '';
                    if (contentType.indexOf('application/json') === -1) {
                        showError(defaultMsg);
                        return;
                    }

                    const data = await res.json();
                    if (data.success) {
                        window.location = data.redirect;
                        return;
                    }
                    showError((data.errors && data.errors.join(' ')) || defaultMsg);
                } catch (err) {
                    showError('The username or password you entered is incorrect. Please try again.');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });

            function showError(message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
                clearTimeout(window._fdl_error_timer);
                window._fdl_error_timer = setTimeout(function () {
                    errorEl.classList.add('hidden');
                }, 4000);
            }
        });
    </script>
</body>
</html>
