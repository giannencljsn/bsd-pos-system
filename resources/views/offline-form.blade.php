<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Offline Form Example</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #status {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h2>Submit Data (with Offline Sync)</h2>

    <div id="status">Checking network status...</div>

    <form id="submitForm">
        <label for="field1">Field 1:</label>
        <input type="text" id="field1" name="field1" required><br><br>

        <label for="field2">Field 2:</label>
        <input type="text" id="field2" name="field2" required><br><br>

        <button type="submit">Submit</button>
    </form>

    <script src="/indexeddb.js"></script>

    <script>
        const statusDisplay = document.getElementById('status');

        function updateOnlineStatus() {
            if (navigator.onLine) {
                statusDisplay.textContent = "Online";
                statusDisplay.style.color = "green";
            } else {
                statusDisplay.textContent = "Offline";
                statusDisplay.style.color = "red";
            }
        }

        window.addEventListener('online', () => {
            updateOnlineStatus();
            syncOfflineData();
        });

        window.addEventListener('offline', updateOnlineStatus);

        async function checkOnlineStatus() {
            try {
                const response = await fetch('https://httpbin.org/status/200', { method: 'HEAD', cache: 'no-store' });
                if (response.ok) {
                    updateOnlineStatus();
                    syncOfflineData();
                }
            } catch {
                statusDisplay.textContent = "Offline";
                statusDisplay.style.color = "red";
            }
        }

        setInterval(() => {
            if (navigator.onLine) {
                checkOnlineStatus();
            }
        }, 10000);

        updateOnlineStatus(); // Initial status check

        document.getElementById("submitForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const data = {
                field1: document.getElementById("field1").value,
                field2: document.getElementById("field2").value
            };

            if (navigator.onLine) {
                await fetch('/api/sync-offline-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ records: [data] })
                });
                alert("Data sent to server.");
                console.log("Data input:", data);
            } else {
                await saveOfflineData(data);
                alert("No internet. Data saved offline.");
                console.log("Data input:", data);
            }

            // Optionally clear form
            this.reset();
        });

        // Sync logic
        async function syncOfflineData() {
            if (!navigator.onLine) return;

            const data = await getAllOfflineData();
            if (data.length === 0) return;

            try {
                const response = await fetch('/api/sync-offline-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ records: data })
                });

                if (response.ok) {
                    await clearOfflineData();
                    console.log("Offline data synced successfully!");
                } else {
                    console.error("Failed to sync:", await response.text());
                }
            } catch (error) {
                console.error("Error syncing offline data:", error);
            }
        }
    </script>
</body>

</html>