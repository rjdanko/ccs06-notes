<?php
/*
    ACTION TAB GUIDE (Edit + Delete)
    --------------------------------
    This file is a reference implementation you can follow to rebuild
    your form from scratch with a proper Action column.

    It assumes you already have:
    - database.php that provides $conn (mysqli connection)
    - tbl_account (id, username, email, status, type, ...)
    - tbl_user (account_id, firstname, middlename, lastname, gender, dob, ...)

    Core idea:
    1) EDIT uses GET:    form.php?id=123
    2) DELETE uses POST: action=delete + id=123 (safer than GET)
*/

include_once("database.php");

/* =============================
   1) OPTIONAL: SIMPLE CSRF TOKEN
   ============================= */
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =============================
   2) HANDLE DELETE REQUEST (POST)
   =============================
   Why POST?
   - Prevent accidental deletes via URL sharing/history.
   - Allows CSRF protection.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid CSRF token. Delete blocked.');
    }

    if ($id > 0) {
        // Use transaction because data exists in two tables.
        $conn->begin_transaction();

        try {
            // Delete child row first (tbl_user), then parent (tbl_account)
            $deleteUser = $conn->prepare("DELETE FROM tbl_user WHERE account_id = ?");
            $deleteUser->bind_param("i", $id);
            $deleteUser->execute();

            $deleteAccount = $conn->prepare("DELETE FROM tbl_account WHERE id = ?");
            $deleteAccount->bind_param("i", $id);
            $deleteAccount->execute();

            $conn->commit();
            $message = "Record deleted successfully.";
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "Delete failed: " . $e->getMessage();
        }
    } else {
        $message = "Invalid record id.";
    }
}

/* =============================
   3) FETCH LIST FOR TABLE VIEW
   ============================= */
$sql = "SELECT a.id, a.username, a.email, a.status, a.type,
               u.firstname, u.middlename, u.lastname, u.gender, u.dob
        FROM tbl_account a
        JOIN tbl_user u ON u.account_id = a.id
        ORDER BY a.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Tab Guide</title>
</head>
<body>

<h2>Records (Action Tab Demo)</h2>

<?php if (!empty($message)): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Action</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <!--
                        EDIT ACTION:
                        Send user to your existing form.php in edit mode.
                        form.php reads ?id=... and pre-fills data.
                    -->
                    <a href="form.php?id=<?php echo (int)$row['id']; ?>">Edit</a>

                    <!--
                        DELETE ACTION:
                        Use a small POST form per row.
                        Include hidden action, id, and csrf token.
                    -->
                    <form method="POST" action="" style="display:inline; margin-left:8px;"
                          onsubmit="return confirm('Delete this record? This cannot be undone.');">
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

<h3>How to integrate in your existing form.php</h3>
<ol>
    <li>Keep your current EDIT link format: <code>form.php?id=ID</code>.</li>
    <li>Add a DELETE POST form inside the Action column.</li>
    <li>At the top of the file, detect <code>$_POST['action'] === 'delete'</code> and run delete SQL.</li>
    <li>Use transaction when deleting from both <code>tbl_user</code> and <code>tbl_account</code>.</li>
    <li>Prefer prepared statements for all new queries.</li>
</ol>

</body>
</html>
