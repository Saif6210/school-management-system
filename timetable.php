<?php
require_once 'includes/header.php';
require_once 'db.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-calendar-alt me-2 text-primary"></i>Timetable Management</h3>
        <p class="text-muted mb-0">Weekly class schedules and periods.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 p-md-4">
        <form class="row g-3 align-items-end" method="GET">
            <div class="col-md-5">
                <label class="form-label fw-bold text-muted text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Select Class</label>
                <select name="class" class="form-select form-select-lg bg-light fw-bold text-dark border-0 shadow-sm" onchange="this.form.submit()">
                    <option value="1" <?= (isset($_GET['class']) && $_GET['class'] == '1') ? 'selected' : '' ?>>Class 1</option>
                    <option value="2" <?= (isset($_GET['class']) && $_GET['class'] == '2') ? 'selected' : '' ?>>Class 2</option>
                    <option value="3" <?= (isset($_GET['class']) && $_GET['class'] == '3') ? 'selected' : '' ?>>Class 3</option>
                    <option value="4" <?= (isset($_GET['class']) && $_GET['class'] == '4') ? 'selected' : '' ?>>Class 4</option>
                    <option value="5" <?= (!isset($_GET['class']) || $_GET['class'] == '5') ? 'selected' : '' ?>>Class 5</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary fw-bold px-4 py-3 w-100 shadow-sm rounded-3"><i class="fas fa-search me-2"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<?php
$selected_class = $_GET['class'] ?? '5';
$subjects = ['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Computer', 'Social Studies', 'Drawing'];
$teachers = ['Mr. Ali', 'Mr. Ahmed', 'Mrs. Sana', 'Miss Fatima', 'Mr. Raza', 'Mrs. Ayesha'];

// Define time slots
// Total time: 9:00 AM to 2:00 PM
// Break: 12:00 PM to 12:30 PM
$time_slots = [
    'Period 1' => '09:00 - 09:45',
    'Period 2' => '09:45 - 10:30',
    'Period 3' => '10:30 - 11:15',
    'Period 4' => '11:15 - 12:00',
    'Break'    => '12:00 - 12:30',
    'Period 5' => '12:30 - 01:15',
    'Period 6' => '01:15 - 02:00'
];

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Seed random generator with class number so timetable remains consistent for each class
mt_srand((int)$selected_class * 100); 

function getRandomSubject($subjects, $teachers) {
    $subj = $subjects[array_rand($subjects)];
    $teach = $teachers[array_rand($teachers)];
    $colors = ['text-primary', 'text-success', 'text-info', 'text-warning', 'text-danger'];
    $color = $colors[array_rand($colors)];
    return ['subject' => $subj, 'teacher' => $teach, 'color' => $color];
}
?>

<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4 text-center border-bottom pb-3"><i class="fas fa-chalkboard text-muted me-2"></i>Class <?= htmlspecialchars($selected_class) ?> Schedule</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center m-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-4 text-uppercase fw-bold text-muted border-end-0" style="width: 10%;">Day</th>
                        <?php foreach($time_slots as $name => $time): ?>
                        <th class="py-3 px-1" style="<?= $name === 'Break' ? 'width: 5%; background-color: #f8f9fa;' : 'width: 14%;' ?>">
                            <div class="mb-1"><?= $name ?></div>
                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;"><?= $time ?></small>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($days as $index => $day): ?>
                    <tr>
                        <td class="fw-bold bg-light py-4 fs-6 border-end-0"><?= $day ?></td>
                        
                        <?php 
                        $slot_index = 0;
                        foreach($time_slots as $name => $time): 
                            if ($name === 'Break') {
                                if ($index === 0) {
                                    echo '<td class="bg-secondary bg-opacity-10 text-muted fw-bold letter-spacing-1 text-uppercase border-start-0 border-end-0" rowspan="5" style="vertical-align: middle; writing-mode: vertical-rl; transform: rotate(180deg); opacity: 0.5; font-size:0.9rem;">BREAK TIME</td>';
                                }
                            } else {
                                $data = getRandomSubject($subjects, $teachers);
                        ?>
                        <td class="py-3 px-2">
                            <div class="fw-bold <?= $data['color'] ?> mb-2" style="font-size:0.9rem;"><?= $data['subject'] ?></div>
                            <span class="badge bg-light text-dark border shadow-sm px-2 py-1" style="font-size: 0.75rem;"><i class="fas fa-user-tie text-muted me-1"></i><?= $data['teacher'] ?></span>
                        </td>
                        <?php 
                            }
                            $slot_index++;
                        endforeach; 
                        ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.letter-spacing-1 { letter-spacing: 2px; }
</style>

<?php require_once 'includes/footer.php'; ?>
