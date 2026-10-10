<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Result Processing - Upload</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg w-full border border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Faculty Result Processing</h2>
        <p class="text-sm text-gray-600 mb-6">Upload student score documents (PDF or Excel) to extract data and evaluate graduation criteria.</p>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm border border-red-200">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?= form_open_multipart('result/process_upload'); ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Select Result File (.pdf, .xlsx, .csv)</label>
                <input type="file" name="result_file" required
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded p-1">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition duration-200">
                Upload & Process Result
            </button>
        <?= form_close(); ?>
    </div>
</body>
</html>