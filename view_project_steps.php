<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$message = '';
$project_id = null;
$division_name = '';
$steps = [];
$project_name = '';

// Get project ID and division name from URL
if (isset($_GET['project_id']) && isset($_GET['division_name'])) {
    $project_id = $_GET['project_id'];
    $division_name = urldecode($_GET['division_name']);

    // Fetch project name for display
    $stmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $project_name = $result->fetch_assoc()['project_name'];
    }
    $stmt->close();

    // Fetch steps for this project and division
    $stmt = $conn->prepare("SELECT * FROM project_phase_steps WHERE project_id = ? AND division_name = ? ORDER BY step_order ASC");
    $stmt->bind_param("is", $project_id, $division_name);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($steps[$row['step_order'] - 1])) {
            $steps[$row['step_order'] - 1] = [
                'step_id' => $row['step_id'],
                'step_description' => $row['step_description'],
                'is_finished' => $row['is_finished'],
                'step_order' => $row['step_order'],
                'image_path' => [],
                'file_path' => []
            ];
        }
        if (!empty($row['image_path'])) {
            $steps[$row['step_order'] - 1]['image_path'][] = $row['image_path'];
        }
        if (!empty($row['file_path'])) {
            $steps[$row['step_order'] - 1]['file_path'][] = $row['file_path'];
        }
    }
    $stmt->close();

} else {
    $message = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Project ID or Division Name not provided.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Project Steps - <?php echo htmlspecialchars($division_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            line-height: 1.6;
             background: url('./images/background.webp') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #2563eb;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
        }

        .steps-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        .step-item {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .step-number {
            font-weight: 600;
            color: #2563eb;
            font-size: 1.25rem;
        }

        .step-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .step-description {
            color: #334155;
            margin-bottom: 1rem;
        }

        .step-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .step-image {
            position: relative;
            width: 100%;
            padding-bottom: 100%;
            border-radius: 0.5rem;
            overflow: hidden;
            cursor: pointer;
            outline: none;
            box-shadow: none;
        }

        .step-image:focus,
        .step-image:active {
            outline: none;
            box-shadow: none;
        }

        .step-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Files list */
        .step-files {
            margin-top: 1rem;
        }
        .file-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .file-item:last-child { border-bottom: none; }
        .file-item i { color: #475569; }
        .file-link { color: #2563eb; text-decoration: none; word-break: break-all; }
        .file-link:hover { text-decoration: underline; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .back-btn:hover {
            background: #1d4ed8;
        }

        /* Carousel Modal Styles */
        .carousel-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .carousel-modal.active {
            display: flex;
        }

        .carousel-content {
            position: relative;
            width: 80%;
            max-width: 800px;
            height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .carousel-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .carousel-prev {
            left: 20px;
        }

        .carousel-next {
            right: 20px;
        }

        .carousel-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .carousel-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .carousel-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Project Steps: <?php echo htmlspecialchars($division_name); ?></h1>
            <p>Project: <?php echo htmlspecialchars($project_name); ?></p>
        </div>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <div class="steps-container">
            <?php if (!empty($steps)): ?>
                <?php foreach ($steps as $index => $step): ?>
                    <div class="step-item">
                        <div class="step-header">
                            <div class="step-number">Step <?php echo $index + 1; ?></div>
                            <div class="step-status <?php echo $step['is_finished'] ? 'status-completed' : 'status-pending'; ?>">
                                <?php echo $step['is_finished'] ? 'Completed' : 'Pending'; ?>
                            </div>
                        </div>
                        <div class="step-description">
                            <?php echo htmlspecialchars($step['step_description']); ?>
                        </div>
                        <?php if (!empty($step['image_path'])): ?>
                            <div class="step-images">
                                <?php foreach ($step['image_path'] as $image): ?>
                                    <div class="step-image" data-image="<?php echo htmlspecialchars($image); ?>">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Step <?php echo $index + 1; ?> image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($step['file_path'])): ?>
                            <div class="step-files">
                                <?php foreach ($step['file_path'] as $file): ?>
                                    <?php 
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        $icon = 'fa-file';
                                        if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                                        elseif (in_array($ext, ['doc','docx'])) $icon = 'fa-file-word';
                                        elseif (in_array($ext, ['xls','xlsx'])) $icon = 'fa-file-excel';
                                        elseif ($ext === 'txt') $icon = 'fa-file-lines';
                                        elseif (in_array($ext, ['zip','rar'])) $icon = 'fa-file-archive';
                                    ?>
                                    <div class="file-item">
                                        <i class="fa <?php echo $icon; ?>"></i>
                                        <a class="file-link" href="<?php echo htmlspecialchars($file); ?>" target="_blank" rel="noopener">
                                            <?php echo htmlspecialchars(basename($file)); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No steps found for this division.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 2rem;">
            <a href="view_project.php?id=<?php echo $project_id; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Project
            </a>
        </div>
    </div>

    <!-- Carousel Modal -->
    <div class="carousel-modal">
        <div class="carousel-content">
            <button class="carousel-close"><i class="fa fa-times"></i></button>
            <button class="carousel-nav carousel-prev"><i class="fa fa-chevron-left"></i></button>
            <img class="carousel-image" src="" alt="Carousel image">
            <button class="carousel-nav carousel-next"><i class="fa fa-chevron-right"></i></button>
            <div class="carousel-counter"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carousel functionality
            const modal = document.querySelector('.carousel-modal');
            const modalImage = modal.querySelector('.carousel-image');
            const prevBtn = modal.querySelector('.carousel-prev');
            const nextBtn = modal.querySelector('.carousel-next');
            const closeBtn = modal.querySelector('.carousel-close');
            const counter = modal.querySelector('.carousel-counter');
            let currentImages = [];
            let currentIndex = 0;

            // Open carousel when clicking an image
            document.querySelectorAll('.step-image').forEach(image => {
                image.addEventListener('click', function() {
                    const stepImages = Array.from(this.closest('.step-images').querySelectorAll('.step-image'))
                        .map(img => img.getAttribute('data-image'));
                    
                    currentImages = stepImages;
                    currentIndex = stepImages.indexOf(this.getAttribute('data-image'));
                    
                    updateCarousel();
                    modal.classList.add('active');
                });
            });

            function updateCarousel() {
                modalImage.src = currentImages[currentIndex];
                counter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
            }

            prevBtn.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
                updateCarousel();
            });

            nextBtn.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % currentImages.length;
                updateCarousel();
            });

            closeBtn.addEventListener('click', () => {
                modal.classList.remove('active');
            });

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!modal.classList.contains('active')) return;
                
                if (e.key === 'ArrowLeft') {
                    prevBtn.click();
                } else if (e.key === 'ArrowRight') {
                    nextBtn.click();
                } else if (e.key === 'Escape') {
                    closeBtn.click();
                }
            });
        });
    </script>
</body>
</html> 