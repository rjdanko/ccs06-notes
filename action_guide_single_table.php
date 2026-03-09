<?php
/*
    GUIDE: ACTION TAB WHEN YOU ONLY HAVE 1 TABLE
    ============================================

    Use this when ALL record fields are in one table only.

    Example table: tbl_member
    - id (PK)
    - firstname
    - lastname
    - email
    - status

    ACTION TAB behavior:
    - EDIT   -> GET  form.php?id=123
    - DELETE -> POST action=delete&id=123
*/

include_once("database.php");
session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

/*
    1) HANDLE DELETE (single table = single delete query)
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid CSRF token.');
    }

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM tbl_member WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Record deleted successfully.";
        } else {
            $message = "Delete failed: " . $conn->error;
        }
    } else {
        $message = "Invalid id.";
    }
}

/*
    2) FETCH LIST
*/
$result = $conn->query("SELECT id, firstname, lastname, email, status FROM tbl_member ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Single Table Action Guide</title>
</head>
<body>

<h2>Single Table Action Guide</h2>

<?php if ($message): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int) $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo ((int)$row['status'] === 1) ? 'Active' : 'Inactive'; ?></td>
                <td>
                    <!-- EDIT: route to your form page with id -->
                    <a href="form.php?id=<?php echo (int)$row['id']; ?>">Edit</a>

                    <!-- DELETE: small POST form in Action column -->
                    <form method="POST" action="" style="display:inline; margin-left:8px;"
                          onsubmit="return confirm('Delete this record?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">No records found.</td>
        </tr>
    <?php endif; ?>
</table>

<hr>

<h3>Rebuild checklist (1 table)</h3>
<ol>
    <li>Use one <code>SELECT</code> for table listing.</li>
    <li>Use <code>form.php?id=...</code> for edit mode.</li>
    <li>Use POST + CSRF for delete.</li>
    <li>Use prepared statements for update/delete.</li>
</ol>

</body>
</html>
