<?php
/*
    GUIDE: ACTION TAB WHEN YOU HAVE 3+ TABLES WITH INTERSECTING VALUES
    ===================================================================

    Use this when one record is connected to many related tables.

    Example schema (sample only):
    1) tbl_account      (id PK, username, email, ...)
    2) tbl_user_profile (account_id FK -> tbl_account.id, firstname, lastname, ...)
    3) tbl_role_map     (account_id FK -> tbl_account.id, role_id FK -> tbl_role.id)
    4) tbl_audit_log    (account_id FK -> tbl_account.id, action, created_at)

    "Intersecting values" usually means:
    - same account_id appears in multiple related tables
    - deleting/updating one record affects other tables

    ACTION TAB behavior:
    - EDIT   -> GET form.php?id=123 (load data using JOINs)
    - DELETE -> POST action=delete&id=123 (delete in transaction)
*/

include_once("database.php");
session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

/*
    1) HANDLE DELETE FOR MULTI-TABLE STRUCTURE

    Important:
    - Delete CHILD rows first, then PARENT row.
    - Wrap all deletes in one transaction.
    - If any query fails, rollback everything.
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid CSRF token.');
    }

    if ($id > 0) {
        $conn->begin_transaction();

        try {
            // Example child table #1: role mappings
            $stmtRoleMap = $conn->prepare("DELETE FROM tbl_role_map WHERE account_id = ?");
            $stmtRoleMap->bind_param("i", $id);
            $stmtRoleMap->execute();

            // Example child table #2: audit logs (optional: keep logs instead of delete)
            $stmtAudit = $conn->prepare("DELETE FROM tbl_audit_log WHERE account_id = ?");
            $stmtAudit->bind_param("i", $id);
            $stmtAudit->execute();

            // Example child table #3: profile
            $stmtProfile = $conn->prepare("DELETE FROM tbl_user_profile WHERE account_id = ?");
            $stmtProfile->bind_param("i", $id);
            $stmtProfile->execute();

            // Finally parent table
            $stmtAccount = $conn->prepare("DELETE FROM tbl_account WHERE id = ?");
            $stmtAccount->bind_param("i", $id);
            $stmtAccount->execute();

            $conn->commit();
            $message = "Multi-table record deleted successfully.";
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "Delete failed. Rolled back. Error: " . $e->getMessage();
        }
    } else {
        $message = "Invalid id.";
    }
}

/*
    2) FETCH LIST USING JOINS
    - Use LEFT JOIN when some related rows may be missing.
    - GROUP_CONCAT is useful for showing multiple roles in one row.
*/
$sql = "SELECT
            a.id,
            a.username,
            a.email,
            p.firstname,
            p.lastname,
            GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles
        FROM tbl_account a
        LEFT JOIN tbl_user_profile p ON p.account_id = a.id
        LEFT JOIN tbl_role_map rm ON rm.account_id = a.id
        LEFT JOIN tbl_role r ON r.id = rm.role_id
        GROUP BY a.id, a.username, a.email, p.firstname, p.lastname
        ORDER BY a.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Table Action Guide</title>
</head>
<body>

<h2>Multi-Table (Intersecting Values) Action Guide</h2>

<?php if ($message): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Roles</th>
        <th>Action</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars(trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''))); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['roles'] ?? 'No role'); ?></td>
                <td>
                    <!-- EDIT: load all related values in form.php using JOIN queries -->
                    <a href="form.php?id=<?php echo (int)$row['id']; ?>">Edit</a>

                    <!-- DELETE: POST with csrf and confirm -->
                    <form method="POST" action="" style="display:inline; margin-left:8px;"
                          onsubmit="return confirm('Delete this account and all connected data?');">
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

<h3>Rebuild checklist (3+ related tables)</h3>
<ol>
    <li>List view: use JOIN/LEFT JOIN to display combined data.</li>
    <li>Edit mode: query all related table values by one primary id.</li>
    <li>Update mode: update related tables in one transaction.</li>
    <li>Delete mode: delete child records first, then parent, inside transaction.</li>
    <li>Consider <code>ON DELETE CASCADE</code> to simplify manual delete queries.</li>
</ol>

</body>
</html>
