<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads OAuth Callback</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        h1 {
            margin-top: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .status-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .status-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .field {
            margin-bottom: 20px;
        }
        .field-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field-value {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 14px;
            font-family: "SF Mono", Consolas, Monaco, monospace;
            font-size: 14px;
            word-break: break-all;
            position: relative;
            min-height: 44px;
        }
        .field-empty {
            color: #999;
            font-style: italic;
            font-family: inherit;
        }
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .copy-btn:hover { background: #096dd9; }
        .copy-btn.copied { background: #52c41a; }
        .raw-params {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        .raw-params pre {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            overflow-x: auto;
            font-size: 13px;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($error)): ?>
            <span class="status status-error">Error</span>
            <h1>Otorisasi Gagal</h1>
            <div class="field">
                <div class="field-label">Error</div>
                <div class="field-value"><?= htmlspecialchars($error) ?></div>
            </div>
            <?php if (!empty($error_description)): ?>
                <div class="field">
                    <div class="field-label">Deskripsi</div>
                    <div class="field-value"><?= htmlspecialchars($error_description) ?></div>
                </div>
            <?php endif; ?>
        <?php elseif (!empty($code)): ?>
            <span class="status status-success">Sukses</span>
            <h1>Authorization Code Diterima</h1>
            <p style="color:#666;font-size:14px;margin-bottom:24px;">
                Copy code di bawah ini lalu kirim ke Repliz untuk diproses lebih lanjut.
            </p>
            <div class="field">
                <div class="field-label">Code</div>
                <div class="field-value">
                    <span id="code-value"><?= htmlspecialchars($code) ?></span>
                    <button class="copy-btn" onclick="copyText('code-value', this)">Copy</button>
                </div>
            </div>
            <?php if (!empty($state)): ?>
                <div class="field">
                    <div class="field-label">State</div>
                    <div class="field-value">
                        <span id="state-value"><?= htmlspecialchars($state) ?></span>
                        <button class="copy-btn" onclick="copyText('state-value', this)">Copy</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1>Threads OAuth Callback</h1>
            <p style="color:#666;font-size:14px;">
                Halaman ini belum menerima parameter apa pun. Buka URL ini setelah proses authorize selesai.
            </p>
        <?php endif; ?>

        <?php if (!empty($all_params)): ?>
            <div class="raw-params">
                <div class="field-label">Semua Query Params</div>
                <pre><?= htmlspecialchars(json_encode($all_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>
        <?php endif; ?>

        <div class="footer">
            Endpoint: <?= base_url('threads_callback') ?>
        </div>
    </div>

    <script>
        function copyText(elementId, btn) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 1500);
            }).catch(() => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = 'Copy';
                    btn.classList.remove('copied');
                }, 1500);
            });
        }
    </script>
</body>
</html>
