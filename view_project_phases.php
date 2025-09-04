<?php
session_start();
// Allow access for both admin and client users
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_logged_in'])) {
    header("Location: client_login.php");
    exit();
}

include 'db.php';

$message = '';
$project_id = null;
$project_name = '';
$project_divisions = [];
$all_steps = [];

// Get project ID from URL
if (isset($_GET['project_id'])) {
    $project_id = (int)$_GET['project_id'];

    // Fetch project details
    $stmt = $conn->prepare("SELECT project_name, project_divisions FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        $project_name = $project['project_name'];
        $project_divisions = array_map('trim', explode(',', $project['project_divisions']));
        $project_divisions = array_filter($project_divisions); // Remove empty entries
    }
    $stmt->close();

    // Fetch all steps for this project
    if (!empty($project_divisions)) {
        $stmt = $conn->prepare("SELECT * FROM project_phase_steps WHERE project_id = ? ORDER BY division_name ASC, step_order ASC");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $division = $row['division_name'];
            if (!isset($all_steps[$division])) {
                $all_steps[$division] = [];
            }
            
            if (!isset($all_steps[$division][$row['step_order'] - 1])) {
                $all_steps[$division][$row['step_order'] - 1] = [
                    'step_id' => $row['step_id'],
                    'step_description' => $row['step_description'],
                    'is_finished' => $row['is_finished'],
                    'step_order' => $row['step_order'],
                    'image_path' => [],
                    'file_path' => []
                ];
            }
            
            if (!empty($row['image_path'])) {
                $all_steps[$division][$row['step_order'] - 1]['image_path'][] = $row['image_path'];
            }
            if (!empty($row['file_path'])) {
                $all_steps[$division][$row['step_order'] - 1]['file_path'][] = $row['file_path'];
            }
        }
        $stmt->close();
    }
} else {
    $message = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Project ID not provided.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Project Phases - <?php echo htmlspecialchars($project_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern design tokens */
        :root {
            --color-primary: #2563eb;
            --color-primary-600: #1d4ed8;
            --color-surface: #ffffff;
            --color-bg: #f6f7fb;
            --color-text: #1f2937;
            --color-muted: #6b7280;
            --border: #e5e7eb;
            --radius-sm: 8px;
            --radius-md: 10px;
            --radius-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.65;
            
        }

        .container { max-width: 1000px; margin: 2.5rem auto; padding: 0 1rem; }

        .header { background: var(--color-surface); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: none; margin-bottom: 1.5rem; }

        .header h1 { color: var(--color-primary); font-size: 1.375rem; margin-bottom: 0.25rem; font-weight: 700; }

        .header p { color: var(--color-muted); }

        .search-container {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.65rem 0.9rem;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.95rem;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
            background: #ffffff;
        }

        .search-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .division-section { background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: none; padding: 1.25rem; margin-bottom: 1rem; }

        .division-title { color: var(--color-primary); font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .division-title .actions { display: inline-flex; gap: 0.5rem; align-items: center; margin-left: auto; }
        .btn-toggle { background: transparent; color: var(--color-primary); border: 1px solid var(--color-primary); }
        .btn-toggle:hover { background: var(--color-primary); color: #ffffff; }

        .division-title::before { content: none; }

        .step-item { border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 0.75rem; background: #ffffff; box-shadow: none; transition: background-color var(--transition-fast), border-color var(--transition-fast); }
        .steps-container { display: none; }
        .steps-container.show { display: block; }

        .step-item:hover { border-color: #dbeafe; background-color: #fbfdff; }

        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            gap: 0.75rem;
        }

        .step-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-number { font-weight: 600; color: var(--color-primary); font-size: 1.05rem; }

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

        .step-description { color: #374151; margin-bottom: 0.75rem; font-size: 0.95rem; line-height: 1.6; }

        .step-images {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-images.show {
            opacity: 1;
            max-height: 1000px;
            margin-top: 1.5rem;
            display: grid;
        }

        .step-image {
            position: relative;
            width: 100%;
            padding-bottom: 100%;
            border-radius: 0.75rem;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .step-image:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
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

        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.8rem; border-radius: var(--radius-sm); border: 1px solid transparent; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: background-color var(--transition-fast), color var(--transition-fast), border-color var(--transition-fast); }

        .btn:focus-visible { outline: 3px solid rgba(37, 99, 235, 0.35); outline-offset: 2px; }

        .btn:active {
            transform: translateY(1px);
        }

        .btn[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-view { background: var(--color-primary); color: #ffffff; border-color: var(--color-primary); }

        .btn-view:hover:not([disabled]) { background: var(--color-primary-600); border-color: var(--color-primary-600); color: #ffffff; }

        .btn-view i {
            transition: transform 0.2s ease;
        }

        .btn-view.toggled i {
            transform: rotate(180deg);
        }

        .btn-steps { background: #059669; color: #ffffff; text-decoration: none; border-color: #059669; font-size: 0.85rem; padding: 0.4rem 0.8rem; }

        .btn-steps:hover { background: #047857; border-color: #047857; color: #ffffff; text-decoration: none; }

        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.1rem; background: var(--color-primary); color: #ffffff; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600; transition: background-color var(--transition-fast); }

        .back-btn:hover { background: var(--color-primary-600); }

        .no-steps {
            color: #64748b;
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }

        /* Carousel Modal Styles */
        .carousel-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.86); z-index: 1000; justify-content: center; align-items: center; }

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
            transition: all var(--transition-fast);
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
        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }

        /* Basic dark mode */
        @media (prefers-color-scheme: dark) {
            :root {
                --color-surface: #0b1220;
                --color-bg: #060b16;
                --color-text: #e5e7eb;
                --color-muted: #9aa4b2;
            }
            body { color: var(--color-text); }
            .header { background: var(--color-surface); box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
            .division-section { background: var(--color-surface); box-shadow: 0 1px 3px rgba(0,0,0,0.35); }
            .step-item { background: linear-gradient(180deg, #0b1220, #0f172a); border-color: #1e293b; }
            .search-input { background: #0f172a; color: var(--color-text); border-color: #1e293b; }
            .file-item { border-bottom-color: #1e293b; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Project Phases: <?php echo htmlspecialchars($project_name); ?></h1>
            <p>View all project phases and steps (Read Only)</p>
            <div class="search-container">
                <input id="phaseSearch" class="search-input" type="text" placeholder="Search phases, e.g. Phase 1 or Step 1..." aria-label="Search phases">
            </div>
        </div>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <?php if (!empty($project_divisions)): ?>
            <?php foreach ($project_divisions as $division): ?>
                <?php
                    $phaseStatusClass = 'status-pending';
                    $phaseStatusText = 'Pending';
                    if (isset($all_steps[$division]) && !empty($all_steps[$division])) {
                        $allFinished = true;
                        foreach ($all_steps[$division] as $st) {
                            if (empty($st['is_finished']) || (int)$st['is_finished'] !== 1) { $allFinished = false; break; }
                        }
                        if ($allFinished) { $phaseStatusClass = 'status-completed'; $phaseStatusText = 'Completed'; }
                    }
                ?>
                <div class="division-section">
                    <h2 class="division-title">
                        <?php echo htmlspecialchars($division); ?>
                        <span class="actions">
                            <span class="step-status <?php echo $phaseStatusClass; ?>" style="margin-right: 8px;"><?php echo $phaseStatusText; ?></span>
                            <button class="btn btn-toggle" type="button" aria-expanded="false">
                                <i class="fa fa-chevron-down"></i>
                                <span>Show steps</span>
                            </button>
                        </span>
                    </h2>
                    <div class="steps-container">
                    <?php if (isset($all_steps[$division]) && !empty($all_steps[$division])): ?>
                        <?php foreach ($all_steps[$division] as $index => $step): ?>
                            <div class="step-item">
                                <div class="step-header">
                                    <div class="step-meta">
                                        <div class="step-number">Step <?php echo $index + 1; ?></div>
                                        <div class="step-status <?php echo $step['is_finished'] ? 'status-completed' : 'status-pending'; ?>">
                                            <?php echo $step['is_finished'] ? 'Completed' : 'Pending'; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-view open-carousel-btn" data-index="<?php echo $index; ?>" <?php echo empty($step['image_path']) ? 'disabled' : ''; ?>>
                                        <i class="fa fa-images"></i>
                                        <span class="btn-text">View</span>
                                    </button>
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
                        <p class="no-steps">No steps found for this division.</p>
                    <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="division-section">
                <p class="no-steps">No divisions found for this project.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="client_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
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
            // Phase toggle buttons
            document.querySelectorAll('.division-section').forEach(section => {
                const toggleBtn = section.querySelector('.btn-toggle');
                const steps = section.querySelector('.steps-container');
                if (!toggleBtn || !steps) return;
                toggleBtn.addEventListener('click', () => {
                    const isOpen = steps.classList.toggle('show');
                    const icon = toggleBtn.querySelector('i');
                    const text = toggleBtn.querySelector('span');
                    toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    if (icon) icon.className = isOpen ? 'fa fa-chevron-up' : 'fa fa-chevron-down';
                    if (text) text.textContent = isOpen ? 'Hide steps' : 'Show steps';
                });
            });
            // Search/Filter functionality
            const searchInput = document.getElementById('phaseSearch');

            function normalize(text) {
                return (text || '').toString().toLowerCase();
            }

            function filterSteps() {
                const query = normalize(searchInput.value.trim());
                const divisions = document.querySelectorAll('.division-section');

                divisions.forEach(section => {
                    const titleEl = section.querySelector('.division-title');
                    const divisionTitle = titleEl ? normalize(titleEl.textContent) : '';
                    // Only match against the phase/division title
                    if (!query) {
                        section.style.display = '';
                        return;
                    }
                    section.style.display = divisionTitle.includes(query) ? '' : 'none';
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterSteps);
            }

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

            // Toggle images visibility when clicking the View button
            document.querySelectorAll('.open-carousel-btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (this.hasAttribute('disabled')) return;
                    const stepItem = this.closest('.step-item');
                    if (!stepItem) return;
                    const imagesContainer = stepItem.querySelector('.step-images');
                    if (!imagesContainer) return;
                    
                    const isVisible = imagesContainer.classList.contains('show');
                    const icon = this.querySelector('i');
                    const text = this.querySelector('.btn-text');
                    
                    if (isVisible) {
                        // Hide images
                        imagesContainer.classList.remove('show');
                        icon.className = 'fa fa-images';
                        this.classList.remove('toggled');
                        if (text) text.textContent = 'View';
                    } else {
                        // Show images
                        imagesContainer.classList.add('show');
                        icon.className = 'fa fa-eye-slash';
                        this.classList.add('toggled');
                        if (text) text.textContent = 'Hide';
                    }
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

            // Run initial filter (no query shows all)
            filterSteps();
        });
    </script>
</body>
</html>
