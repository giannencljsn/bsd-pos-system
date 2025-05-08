<div class="position-relative">
    <div class="card mb-0 border-0 shadow-sm">
        <div class="card-body">
            <div class="form-group mb-0">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text">
                            <i class="bi bi-search text-primary"></i>
                        </div>
                    </div>
                    <!-- This is the barcode scanner button -->
                    <button onclick="startScanner()" class="btn btn-primary form-control">
                        Start Barcode Scanner
                    </button>
                    <input id="product-input" type="text" class="form-control mt-2"
                        placeholder="Scan or type product name or code..." readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Feed for Barcode Scanning -->
    <div id="camera-container" style="display: none;">
        <video id="camera" width="320" height="240" autoplay></video>
        <canvas id="canvas" style="display: none;"></canvas>
    </div>

    <script>
        let video;
        let canvas;
        let context;

        // Initialize ZXing scanner
        function startScanner() {
            document.getElementById('camera-container').style.display = 'block';
            video = document.getElementById('camera');
            canvas = document.getElementById('canvas');
            context = canvas.getContext('2d');

            // Start camera stream
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function (stream) {
                    video.srcObject = stream;
                    scanBarcode();
                })
                .catch(function (err) {
                    console.error('Error accessing camera: ', err);
                });
        }

        // Function to scan barcode continuously
        function scanBarcode() {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);

            // Send captured image to server for barcode decoding
            const image = canvas.toDataURL('image/png'); // Convert image to base64 format
            fetch('/decode-barcode', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: image })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.barcode) {
                        console.log('Barcode Result:', data.barcode);
                        // Set value to the input field with scanned barcode
                        document.getElementById('product-input').value = data.barcode;
                        // Optionally stop scanning after successful scan
                        video.srcObject.getTracks().forEach(track => track.stop());
                        document.getElementById('camera-container').style.display = 'none';
                    } else {
                        requestAnimationFrame(scanBarcode); // Keep scanning
                    }
                })
                .catch(err => {
                    console.error('Error during barcode decoding:', err);
                });
        }
    </script>

    <div wire:loading class="card position-absolute mt-1 border-0" style="z-index: 1;left: 0;right: 0;">
        <div class="card-body shadow">
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($query))
        <div wire:click="resetQuery" class="position-fixed w-100 h-100"
            style="left: 0; top: 0; right: 0; bottom: 0;z-index: 1;"></div>
        @if($search_results->isNotEmpty())
            <div class="card position-absolute mt-1" style="z-index: 2;left: 0;right: 0;border: 0;">
                <div class="card-body shadow">
                    <ul class="list-group list-group-flush">
                        @foreach($search_results as $result)
                            <li class="list-group-item list-group-item-action">
                                <a wire:click="resetQuery" wire:click.prevent="selectProduct({{ $result }})" href="#">
                                    {{ $result->product_name }} | {{ $result->product_code }}
                                </a>
                            </li>
                        @endforeach
                        @if($search_results->count() >= $how_many)
                            <li class="list-group-item list-group-item-action text-center">
                                <a wire:click.prevent="loadMore" class="btn btn-primary btn-sm" href="#">
                                    Load More <i class="bi bi-arrow-down-circle"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @else
            <div class="card position-absolute mt-1 border-0" style="z-index: 1;left: 0;right: 0;">
                <div class="card-body shadow">
                    <div class="alert alert-warning mb-0">
                        No Product Found....
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>