<?php
require_once 'includes/header.php';
require_once 'db.php';

$view = $_GET['view'] ?? 'all';
$success_msg = '';
$error_msg = '';

// Ensure books and issued_books tables exist
$conn->query("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    status ENUM('Available','Issued') NOT NULL DEFAULT 'Available'
)");
$conn->query("CREATE TABLE IF NOT EXISTS issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    class_name VARCHAR(20) NOT NULL,
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    return_date DATETIME NULL,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
)");

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $isbn = $conn->real_escape_string($_POST['isbn']);
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    if ($conn->query("INSERT INTO books (isbn, title, author) VALUES ('$isbn', '$title', '$author')")) {
        $success_msg = "Book added successfully!";
    } else {
        $error_msg = "Failed to add book. ISBN might be duplicate.";
    }
}

// Handle Issue Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
    $book_id = (int)$_POST['book_id'];
    $class = $conn->real_escape_string($_POST['class_name']);
    if ($conn->query("INSERT INTO issued_books (book_id, class_name) VALUES ($book_id, '$class')")) {
        $conn->query("UPDATE books SET status='Issued' WHERE id=$book_id");
        $success_msg = "Book issued successfully to $class!";
    }
}

// Handle Return Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $issue_id = (int)$_POST['issue_id'];
    $res = $conn->query("SELECT book_id FROM issued_books WHERE id=$issue_id");
    if ($res && $row = $res->fetch_assoc()) {
        $book_id = $row['book_id'];
        $conn->query("DELETE FROM issued_books WHERE id=$issue_id");
        $conn->query("UPDATE books SET status='Available' WHERE id=$book_id");
        $success_msg = "Book returned successfully!";
    }
}
?>

<?php if($success_msg): ?>
<div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <strong><i class="fas fa-check-circle me-2"></i>Success!</strong> <?= $success_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if($error_msg): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <strong><i class="fas fa-exclamation-circle me-2"></i>Error!</strong> <?= $error_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-book-reader me-2 text-primary"></i>Library Management</h3>
        <p class="text-muted mb-0">Record books, track issues and calculate fines.</p>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addBookModal">
        <i class="fas fa-plus me-2"></i>Add Book
    </button>
</div>
<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-book me-2"></i>Add New Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">ISBN</label>
                    <input type="text" name="isbn" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Author</label>
                    <input type="text" name="author" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_book" class="btn btn-primary fw-bold px-4 shadow-sm">Add Book</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom pb-3 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <h5 class="fw-bold mb-0 text-dark">Book Inventory</h5>
        <div class="btn-group shadow-sm border rounded-pill overflow-hidden bg-white p-1" style="max-width: 100%;">
            <a href="?view=all" class="btn btn-sm <?= $view !== 'issued' ? 'btn-primary' : 'btn-white text-muted' ?> fw-bold rounded-pill px-3 py-1 m-0 border-0 flex-grow-1">All Books</a>
            <a href="?view=issued" class="btn btn-sm <?= $view === 'issued' ? 'btn-primary' : 'btn-white text-muted' ?> fw-bold rounded-pill px-3 py-1 m-0 border-0 flex-grow-1">Issued</a>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">ISBN</th>
                        <th class="py-3">Book Title</th>
                        <th class="py-3">Author</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Determine which query to run based on the view
                    if ($view === 'issued') {
                        $query = "SELECT b.id, b.isbn, b.title, b.author, b.status, ib.id AS issue_id, ib.class_name 
                                  FROM books b 
                                  JOIN issued_books ib ON b.id = ib.book_id 
                                  ORDER BY ib.id DESC";
                    } else {
                        $query = "SELECT * FROM books ORDER BY id DESC";
                    }
                    
                    $result = $conn->query($query);
                    $books_data = [];
                    
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()) {
                            $books_data[] = $row;
                        }
                        foreach ($books_data as $row):
                            $isIssued = $row['status'] === 'Issued';
                    ?>
                    <tr class="<?= $isIssued ? 'table-success-soft border-bottom' : '' ?>">
                        <td class="py-3 text-muted fw-semibold"><?= htmlspecialchars($row['isbn']); ?></td>
                        <td class="py-3 fw-bold text-dark"><i class="fas fa-book text-primary me-2"></i><?= htmlspecialchars($row['title']); ?></td>
                        <td class="py-3 text-muted"><?= htmlspecialchars($row['author']); ?></td>
                        <td class="py-3">
                            <?php if ($isIssued): ?>
                                <span class="badge bg-warning px-3 py-2 rounded-pill text-dark shadow-sm">Issued</span>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Available</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 text-end px-3">
                            <?php if ($isIssued): ?>
                                <?php 
                                    // If we are in the 'all' view, we might not have issue_id in the row.
                                    // Let's fetch it if it's missing (though it shouldn't happen often if we join, but 'all' view doesn't join).
                                    $issue_id = $row['issue_id'] ?? null;
                                    if (!$issue_id) {
                                        $book_id = $row['id'];
                                        $issue_res = $conn->query("SELECT id FROM issued_books WHERE book_id = $book_id ORDER BY id DESC LIMIT 1");
                                        if ($issue_res && $i_row = $issue_res->fetch_assoc()) {
                                            $issue_id = $i_row['id'];
                                        }
                                    }
                                ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="issue_id" value="<?= htmlspecialchars($issue_id ?? '') ?>">
                                    <button type="submit" name="return_book" class="btn btn-outline-secondary fw-bold rounded-pill px-4 shadow-sm">Return</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-primary fw-bold shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#issueModal<?= $row['id']; ?>">Issue Book</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No books found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Issue Modals (rendered outside table to prevent Bootstrap modal blinking) -->
<?php if (!empty($books_data)): foreach ($books_data as $row): if ($row['status'] !== 'Issued'): ?>
<div class="modal fade" id="issueModal<?= $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2"></i>Issue Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <input type="hidden" name="book_id" value="<?= $row['id']; ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Select Class</label>
                    <select name="class_name" class="form-select" required>
                        <option value="">Choose Class...</option>
                        <option>Class 1</option>
                        <option>Class 2</option>
                        <option>Class 3</option>
                        <option>Class 4</option>
                        <option>Class 5</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="issue_book" class="btn btn-primary fw-bold px-4 shadow-sm">Issue</button>
            </div>
        </form>
    </div>
</div>
<?php endif; endforeach; endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
