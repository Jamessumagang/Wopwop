<?php
$conn = new mysqli("localhost", "root", "", "capstone_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $status = $_POST['status'];
    $position = $_POST['position'];

    // ✅ Validate birthday format (YYYY-MM-DD)
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday)) {
        die("Invalid birthday format. Please use YYYY-MM-DD.");
    }

    // ✅ Validate year is 4 digits
    $year = date('Y', strtotime($birthday));
    if (!preg_match("/^\d{4}$/", $year)) {
        die("Invalid year format. Year must be 4 digits.");
    }

    // Handle photo upload
    $photo = 'uploads/default.png'; // Default photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = uniqid() . "_" . basename($_FILES["photo"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
            $photo = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO employees (photo, lastname, firstname, middlename, birthday, gender, address, contact_no, status, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $photo, $lastname, $firstname, $middlename, $birthday, $gender, $address, $contact, $status, $position);
    $stmt->execute();
    header("Location: employee_list.php?added=1");
    exit();
}

// Fetch available positions for dropdown
$positions = [];
$pos_stmt = $conn->prepare("SELECT position_name FROM positions ORDER BY position_name ASC");
if ($pos_stmt) {
    $pos_stmt->execute();
    $pos_res = $pos_stmt->get_result();
    while ($row = $pos_res->fetch_assoc()) {
        $positions[] = $row['position_name'];
    }
    $pos_stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Montserrat', 'Inter', Arial, sans-serif;
            background: url('images/background.webp') no-repeat center center fixed, linear-gradient(135deg, #e0e7ff 0%, #f7fafc 100%);
            background-size: cover;
            position: relative;
        }
      
        
        .form-outer {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .form-container {
            max-width: 800px;
            width: 100%;
            margin: 120px auto 0 auto;
            border: none;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            padding: 40px 48px 80px 48px;
            box-sizing: border-box;
            position: relative;
        }
        .form-container h2 {
            margin-top: 0;
            font-size: 2em;
            margin-bottom: 30px;
            color: #2563eb;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }
        .form-group label {
            flex: 0 0 180px;
            font-size: 1.15em;
            margin-right: 10px;
            color: #2563eb;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            flex: 1;
            padding: 14px 18px;
            font-size: 1.1em;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            outline: none;
            transition: border 0.2s;
            background: #f7fafd;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            border: 2px solid #2563eb;
            background: #fff;
        }
        .form-group input[type="file"] {
            flex: 1;
            font-size: 1.1em;
        }
        .form-actions {
            position: absolute;
            bottom: 30px;
            right: 40px;
            display: flex;
            gap: 20px;
        }
        .form-actions button,
        .form-actions a {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: white;
            padding: 12px 50px;
            border: none;
            border-radius: 6px;
            font-size: 1.2em;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .form-actions button:hover,
        .form-actions a:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        .form-actions a.close-link {
            background: #e74c3c;
        }
        .form-actions a.close-link:hover {
            background: #c0392b;
        }
        
        /* Action Button Styles */
        .action-button {
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 120px;
        }
        
        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .action-button:active {
            transform: translateY(0);
        }
        
        /* Location Button */
        .location-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .location-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        /* Camera Button */
        .camera-button {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .camera-button:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        
        /* Capture Button */
        .capture-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            margin-right: 10px;
        }
        
        .capture-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        /* Close Button */
        .close-button {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .close-button:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
        }
        
        /* Camera Container Styling */
        #cameraContainer {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            margin-top: 20px;
        }
        
        #camera {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Aesthetic Camera Action Buttons */
        .camera-action-button {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85em;
            transition: all 0.2s ease;
            min-width: 100px;
            position: relative;
            overflow: hidden;
        }
        
        .camera-action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .camera-action-button:hover::before {
            left: 100%;
        }
        
        .camera-action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .camera-action-button:active {
            transform: translateY(0);
        }
        
        .button-icon {
            font-size: 1.1em;
            display: inline;
        }
        
        .button-text {
            font-size: 0.8em;
            letter-spacing: 0.3px;
        }
        
        /* Capture Button */
        .capture-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(16,185,129,0.3);
        }
        
        .capture-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
        }
        
        /* Close Button */
        .close-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }
        
        .close-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 8px 25px rgba(239,68,68,0.4);
        }
        
        /* Responsive Button Layout */
        @media (max-width: 768px) {
            .action-button {
                min-width: 100px;
                padding: 8px 12px;
                font-size: 0.8em;
            }
            
            #cameraContainer {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="form-outer">
        <form class="form-container" method="POST" action="add_employee.php" enctype="multipart/form-data">
            <h2>New Employee</h2>
            <div class="form-group">
                <label for="lastname">Lastname :</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>
            <div class="form-group">
                <label for="firstname">Firstname :</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            <div class="form-group">
                <label for="middlename">Middlename :</label>
                <input type="text" id="middlename" name="middlename">
            </div>
            <div class="form-group">
                <label for="birthday">Birthday :</label>
                <input type="text" id="birthday" name="birthday" required 
                       placeholder="YYYY-MM-DD"
                       maxlength="10"
                       onchange="calculateAge()"
                       title="Please enter a valid date in YYYY-MM-DD format">
            </div>
            <div class="form-group">
                <label for="age">Age :</label>
                <input type="text" id="age" name="age" readonly>
            </div>
            <div class="form-group">
                <label for="gender">Gender :</label>
                <input type="text" id="gender" name="gender" required>
            </div>
            <div class="form-group">
                <label for="address">Address :</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="address" name="address" required style="flex: 1;">
                    <button type="button" id="getLocation" class="action-button location-button">
                        📍 Get Location
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="contact">Contact no :</label>
                <input type="text" id="contact" name="contact" required>
            </div>
            <div class="form-group">
                <label for="status">Status :</label>
                <select id="status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="position">Position :</label>
                <select id="position" name="position" required>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
                        <div class="form-group">
                <label for="photo">Photo :</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="file" id="photo" name="photo" accept="image/*" style="flex: 1;">
                    <button type="button" id="openCamera" class="action-button camera-button">
                        📷 Camera
                    </button>
                </div>
                                <div id="cameraContainer" style="display: none; margin-top: 20px; text-align: center;">
                    <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                        <video id="camera" autoplay style="width: 100%; max-width: 450px; border-radius: 16px; border: 3px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.15); transform: scaleX(-1);"></video>
                        <div style="position: absolute; top: 15px; right: 15px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.85em; font-weight: 600; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                            📷 Live Camera
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; justify-content: center; align-items: center; margin-top: 20px;">
                        <button type="button" id="capturePhoto" class="camera-action-button capture-btn">
                            <span class="button-icon">📸</span>
                            <span class="button-text">Capture Photo</span>
                        </button>
                        <button type="button" id="closeCamera" class="camera-action-button close-btn">
                            <span class="button-icon">✕</span>
                            <span class="button-text">Close Camera</span>
                        </button>
                    </div>
                    <canvas id="photoCanvas" style="display: none;"></canvas>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">Save</button>
                <a href="employee_list.php" class="close-link">Close</a>
            </div>
        </form>
    </div>
    <script>
        function calculateAge() {
            var birthday = document.getElementById('birthday').value;
            
            // Check if birthday field is empty
            if (!birthday || birthday.trim() === '') {
                document.getElementById('age').value = '';
                return;
            }
            
            // Check if the format is correct (YYYY-MM-DD)
            if (!/^\d{4}-\d{2}-\d{2}$/.test(birthday)) {
                document.getElementById('age').value = '';
                return;
            }
            
            // Split the date into parts
            var parts = birthday.split('-');
            var year = parts[0];
            var month = parts[1];
            var day = parts[2];
            
            // Check if year is exactly 4 digits
            if (!/^\d{4}$/.test(year)) {
                document.getElementById('age').value = '';
                return;
            }
            
            // Check if month is valid (01-12)
            if (!/^\d{2}$/.test(month) || parseInt(month) < 1 || parseInt(month) > 12) {
                document.getElementById('age').value = '';
                return;
            }
            
            // Check if day is valid (01-31)
            if (!/^\d{2}$/.test(day) || parseInt(day) < 1 || parseInt(day) > 31) {
                document.getElementById('age').value = '';
                return;
            }
            
            // Check if year is reasonable (1900 to current year)
            var currentYear = new Date().getFullYear();
            var yearNum = parseInt(year);
            if (yearNum < 1900 || yearNum > currentYear) {
                document.getElementById('age').value = '';
                return;
            }
            
            // If all validations pass, calculate age
            var birthDate = new Date(birthday);
            var today = new Date();
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        }
        // Get user's current location and convert to address
        function getCurrentLocation() {
            if (navigator.geolocation) {
                document.getElementById('getLocation').textContent = '📍 Getting Location...';
                document.getElementById('getLocation').disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var latitude = position.coords.latitude;
                        var longitude = position.coords.longitude;
                        
                        // Use reverse geocoding to get address from coordinates
                        reverseGeocode(latitude, longitude);
                    },
                    function(error) {
                        var errorMessage = '';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location access denied. Please allow location access or enter address manually.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information unavailable. Please enter address manually.';
                                break;
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out. Please enter address manually.';
                                break;
                            default:
                                errorMessage = 'An unknown error occurred. Please enter address manually.';
                                break;
                        }
                        alert(errorMessage);
                        document.getElementById('getLocation').textContent = '📍 Get Location';
                        document.getElementById('getLocation').disabled = false;
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser. Please enter address manually.');
            }
        }

        // Reverse geocoding using OpenStreetMap Nominatim API
        function reverseGeocode(lat, lon) {
            var url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('address').value = data.display_name;
                        // No alert - silently fill the address
                    } else {
                        alert('Could not determine address from location. Please enter manually.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error getting address from location. Please enter manually.');
                })
                .finally(() => {
                    document.getElementById('getLocation').textContent = '📍 Get Location';
                    document.getElementById('getLocation').disabled = false;
                });
        }

        // Form validation function
        function validateForm() {
            var birthday = document.getElementById('birthday').value;
            
            // Check if birthday field is empty
            if (!birthday || birthday.trim() === '') {
                alert('Please enter your birthday in YYYY-MM-DD format.');
                document.getElementById('birthday').focus();
                return false;
            }
            
            // Check if the format is correct (YYYY-MM-DD)
            if (!/^\d{4}-\d{2}-\d{2}$/.test(birthday)) {
                alert('Invalid birthday format. Please use YYYY-MM-DD format (e.g., 1990-05-15).');
                document.getElementById('birthday').focus();
                return false;
            }
            
            // Split the date into parts
            var parts = birthday.split('-');
            var year = parts[0];
            var month = parts[1];
            var day = parts[2];
            
            // Check if year is exactly 4 digits
            if (!/^\d{4}$/.test(year)) {
                alert('Year must be exactly 4 digits (e.g., 1990, 2000).');
                document.getElementById('birthday').focus();
                return false;
            }
            
            // Check if month is valid (01-12)
            if (!/^\d{2}$/.test(month) || parseInt(month) < 1 || parseInt(month) > 12) {
                alert('Month must be between 01 and 12.');
                document.getElementById('birthday').focus();
                return false;
            }
            
            // Check if day is valid (01-31)
            if (!/^\d{2}$/.test(day) || parseInt(day) < 1 || parseInt(day) > 31) {
                alert('Day must be between 01 and 31.');
                document.getElementById('birthday').focus();
                return false;
            }
            
            // Check if year is reasonable (1900 to current year)
            var currentYear = new Date().getFullYear();
            var yearNum = parseInt(year);
            if (yearNum < 1900 || yearNum > currentYear) {
                alert('Year must be between 1900 and ' + currentYear + '.');
                document.getElementById('birthday').focus();
                return false;
            }
            
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Calculate age on page load if birthday is already set
            calculateAge();
            
            // Calculate age when user finishes entering birthday (on blur/change)
            document.getElementById('birthday').addEventListener('change', calculateAge);
            
            // Calculate age when user presses Enter in the birthday field
            document.getElementById('birthday').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    calculateAge();
                    this.blur(); // Move focus away from the field
                }
            });
            
            // Calculate age when user clicks outside the birthday field
            document.getElementById('birthday').addEventListener('blur', calculateAge);
            
            // Add location button event listener
            document.getElementById('getLocation').addEventListener('click', getCurrentLocation);
            
            // Add form validation on submit
            document.querySelector('form').addEventListener('submit', function(event) {
                if (!validateForm()) {
                    event.preventDefault(); // Prevent form submission if validation fails
                }
            });
            
            // Camera functionality
            let stream = null;
            
            // Open camera
            document.getElementById('openCamera').addEventListener('click', function() {
                console.log('Opening camera...');
                
                // Check if camera is already open
                if (stream) {
                    console.log('Camera already open');
                    return;
                }
                
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    // Request camera access with better constraints
                    const constraints = {
                        video: {
                            width: { ideal: 1280, min: 640 },
                            height: { ideal: 720, min: 480 },
                            facingMode: 'user' // Use front camera by default
                        }
                    };
                    
                    navigator.mediaDevices.getUserMedia(constraints)
                        .then(function(mediaStream) {
                            console.log('Camera access granted');
                            stream = mediaStream;
                            const video = document.getElementById('camera');
                            
                            // Wait for video to be ready
                            video.onloadedmetadata = function() {
                                console.log('Video metadata loaded');
                                video.play();
                                document.getElementById('cameraContainer').style.display = 'block';
                                document.getElementById('openCamera').style.display = 'none';
                            };
                            
                            video.srcObject = mediaStream;
                        })
                        .catch(function(error) {
                            console.error('Camera access error:', error);
                            let errorMessage = 'Unable to access camera. ';
                            
                            if (error.name === 'NotAllowedError') {
                                errorMessage += 'Please allow camera access in your browser settings.';
                            } else if (error.name === 'NotFoundError') {
                                errorMessage += 'No camera found on your device.';
                            } else if (error.name === 'NotReadableError') {
                                errorMessage += 'Camera is already in use by another application.';
                            } else {
                                errorMessage += 'Please check camera permissions or use file upload instead.';
                            }
                            
                            alert(errorMessage);
                        });
                } else {
                    alert('Camera access not supported in this browser. Please use file upload instead.');
                }
            });
            
            // Capture photo
            document.getElementById('capturePhoto').addEventListener('click', function() {
                console.log('Capturing photo...');
                
                const video = document.getElementById('camera');
                const canvas = document.getElementById('photoCanvas');
                const context = canvas.getContext('2d');
                
                // Check if video is ready
                if (video.videoWidth === 0 || video.videoHeight === 0) {
                    alert('Camera is not ready yet. Please wait a moment and try again.');
                    return;
                }
                
                // Set canvas size to match video
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                
                try {
                    // Draw video frame to canvas (un-mirror the image)
                    context.save();
                    context.scale(-1, 1); // Flip horizontally
                    context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
                    context.restore();
                    
                    // Convert canvas to blob and create file
                    canvas.toBlob(function(blob) {
                        if (blob) {
                            const file = new File([blob], 'camera_photo.jpg', { type: 'image/jpeg' });
                            
                            // Create a new FileList-like object
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            
                            // Set the file input value
                            document.getElementById('photo').files = dataTransfer.files;
                            
                            // Show preview of captured image
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                // Create a preview element
                                const preview = document.createElement('div');
                                preview.innerHTML = `
                                    <div style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 8px; border: 2px solid #0ea5e9;">
                                        <p style="margin: 0 0 10px 0; color: #0c4a6e; font-weight: 600;">📸 Photo Captured Successfully!</p>
                                        <img src="${e.target.result}" style="max-width: 200px; border-radius: 6px; border: 2px solid #e0e7ef;">
                                    </div>
                                `;
                                
                                // Insert preview after camera container
                                const cameraContainer = document.getElementById('cameraContainer');
                                cameraContainer.parentNode.insertBefore(preview, cameraContainer.nextSibling);
                            };
                            reader.readAsDataURL(blob);
                            
                            // Close camera
                            closeCamera();
                        } else {
                            alert('Failed to capture photo. Please try again.');
                        }
                    }, 'image/jpeg', 0.9);
                    
                } catch (error) {
                    console.error('Photo capture error:', error);
                    alert('Error capturing photo. Please try again.');
                }
            });
            
            // Close camera
            document.getElementById('closeCamera').addEventListener('click', closeCamera);
            
            function closeCamera() {
                console.log('Closing camera...');
                
                if (stream) {
                    stream.getTracks().forEach(track => {
                        track.stop();
                        console.log('Track stopped:', track.kind);
                    });
                    stream = null;
                }
                
                document.getElementById('cameraContainer').style.display = 'none';
                document.getElementById('openCamera').style.display = 'inline-block';
                
                // Remove any photo previews
                const previews = document.querySelectorAll('[style*="Photo Captured Successfully"]');
                previews.forEach(preview => preview.remove());
            }
        });
    </script>
</body>
</html>
