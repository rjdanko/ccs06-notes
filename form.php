<!--
    Group Ratings:
    Lacson, Wendel - 10
    Macatula, Riejed Aniko - 10
    Pangilinan, Laurence - 10
    Santos, Jakov Benedict - 10
-->



<?php
include_once("database.php");

$id = "";
$firstname = $middlename = $lastname = $gender = $dob = "";
$username = $email = $status = $type = "";

/* ================= EDIT MODE ================= */
if (isset($_GET["id"])) {
    $id = $_GET["id"];

    $sql = "SELECT a.*, u.firstname, u.middlename, u.lastname, u.gender, u.dob
            FROM tbl_account a
            JOIN tbl_user u ON u.account_id = a.id
            WHERE a.id = $id";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $firstname  = $row["firstname"];
        $middlename = $row["middlename"];
        $lastname   = $row["lastname"];
        $gender     = $row["gender"];
        $dob        = $row["dob"];

        $username = $row["username"];
        $email    = $row["email"];
        $status   = $row["status"];
        $type     = $row["type"];
    }
}

/* INSERT OR UPDATE */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST["id"];

    $firstname  = test_input($_POST["firstname"]);
    $middlename = test_input($_POST["middlename"]);
    $lastname   = test_input($_POST["lastname"]);
    $gender     = intval($_POST["gender"]);
    $dob        = test_input($_POST["dob"]);

    $username = test_input($_POST["username"]);
    $email    = test_input($_POST["email"]);
    $status   = intval($_POST["status"]);
    $type     = intval($_POST["type"]);

    // Only hash password if provided (optional on edit)
    $password = !empty($_POST["password"]) ? sha1(test_input($_POST["password"])) : null;

    $date_updated = date("Y-m-d H:i:s");

    if ($id == "") {

        /* ===== INSERT ===== */
        $date_created = date("Y-m-d H:i:s");

        $conn->query("INSERT INTO tbl_account
            (username, password, email, status, type, date_created, date_updated)
            VALUES
            ('$username','$password','$email','$status','$type','$date_created','$date_created')");

        $account_id = $conn->insert_id;

        $conn->query("INSERT INTO tbl_user
            (account_id, firstname, middlename, lastname, gender, dob)
            VALUES
            ('$account_id','$firstname','$middlename','$lastname','$gender','$dob')");

        echo "Record Added Successfully";

        // Clear
        $id = "";
        $firstname = $middlename = $lastname = $gender = $dob = "";
        $username = $email = $status = $type = "";

    } else {

        /* ===== UPDATE ===== */

        // Verify record exists before updating
        $check = $conn->query("SELECT id FROM tbl_account WHERE id='$id' LIMIT 1");

        if ($check && $check->num_rows > 0) {

            // Begin transaction to update both tables simultaneously
            $conn->begin_transaction();

            try {
                // Build account UPDATE query (conditionally include password)
                if ($password !== null) {
                    $accountResult = $conn->query("UPDATE tbl_account SET
                        username='$username',
                        password='$password',
                        email='$email',
                        status='$status',
                        type='$type',
                        date_updated='$date_updated'
                        WHERE id='$id'");
                } else {
                    $accountResult = $conn->query("UPDATE tbl_account SET
                        username='$username',
                        email='$email',
                        status='$status',
                        type='$type',
                        date_updated='$date_updated'
                        WHERE id='$id'");
                }

                if (!$accountResult) {
                    throw new Exception("Failed to update account: " . $conn->error);
                }

                // Update user table
                $userResult = $conn->query("UPDATE tbl_user SET
                    firstname='$firstname',
                    middlename='$middlename',
                    lastname='$lastname',
                    gender='$gender',
                    dob='$dob'
                    WHERE account_id='$id'");

                if (!$userResult) {
                    throw new Exception("Failed to update user: " . $conn->error);
                }

                // Both updates succeeded — commit transaction
                $conn->commit();
                echo "Record Updated Successfully";

            } catch (Exception $e) {
                // Roll back both updates if either fails
                $conn->rollback();
                echo "Error updating record: " . $e->getMessage();
            }

        } else {
            echo "Error: Record not found.";
        }

        // Clear
        $id = "";
        $firstname = $middlename = $lastname = $gender = $dob = "";
        $username = $email = $status = $type = "";
    }
}

function test_input($data) {
    return htmlspecialchars(trim($data));
}
?>

<!-- ================= TABLE ================= -->

<table border="1">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Gender</th>
        <th>DOB</th>
        <th>Email</th>
        <th>Status</th>
        <th>Type</th>
        <th>Date Created</th>
        <th>Date Updated</th>
        <th>Action</th>
    </tr>

    <?php
    $sql = "SELECT a.*, u.firstname, u.middlename, u.lastname, u.gender, u.dob
            FROM tbl_account a
            JOIN tbl_user u ON u.account_id = a.id";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    ?>
    <tr>
        <td><?php echo $row["id"]; ?></td>
        <td><?php echo $row["username"]; ?></td>
        <td><?php echo $row["firstname"]; ?></td>
        <td><?php echo $row["middlename"]; ?></td>
        <td><?php echo $row["lastname"]; ?></td>
        <td><?php echo $row["gender"] == 1 ? "Male" : "Female"; ?></td>
        <td><?php echo date("F j, Y", strtotime($row["dob"])); ?></td>
        <td><?php echo $row["email"]; ?></td>
        <td><?php echo $row["status"] == 1 ? "Active" : "Inactive"; ?></td>
        <td><?php echo $row["type"] == 1 ? "Admin" : "User"; ?></td>
        <td><?php echo date("F j, Y", strtotime($row["date_created"])); ?></td>
        <td><?php echo date("F j, Y", strtotime($row["date_updated"])); ?></td>
        <td>
            <a href="form.php?id=<?php echo $row['id']; ?>">EDIT</a>
        </td>
    </tr>
    <?php
        }
    }
    ?>
</table>

<br><hr><br>

<!-- ================= FORM ================= -->

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">

    <input type="hidden" name="id" value="<?php echo $id; ?>">

    <h2>Personal Info</h2>
    First Name:  <input type="text" name="firstname"  value="<?php echo $firstname; ?>"  required><br><br>
    Middle Name: <input type="text" name="middlename" value="<?php echo $middlename; ?>"><br><br>
    Last Name:   <input type="text" name="lastname"   value="<?php echo $lastname; ?>"   required><br><br>
    Gender:
    <select name="gender" required>
        <option value="" disabled <?php if ($gender == "") echo "selected"; ?>>Select</option>
        <option value="1" <?php if ($gender == 1) echo "selected"; ?>>Male</option>
        <option value="0" <?php if ($gender == 0) echo "selected"; ?>>Female</option>
    </select><br><br>
    DOB: <input type="date" name="dob" value="<?php echo $dob; ?>" required><br><br>

    <hr>

    <h2>Account Info</h2>
    Username: <input type="text"     name="username" value="<?php echo $username; ?>" required><br><br>
    Password: <input type="password" name="password" autocomplete="off" <?php if ($id === "") echo 'required'; ?> placeholder="<?php if ($id !== "") echo 'Leave blank to keep current password'; ?>"><br><br>
    Email:    <input type="email"    name="email"    value="<?php echo $email; ?>"    required><br><br>
    Status:
    <select name="status" required>
        <option value="" disabled <?php if ($status == "") echo "selected"; ?>>Select</option>
        <option value="1" <?php if ($status == 1) echo "selected"; ?>>Active</option>
        <option value="0" <?php if ($status == 0) echo "selected"; ?>>Inactive</option>
    </select><br><br>
    Type:
    <select name="type" required>
        <option value="" disabled <?php if ($type == "") echo "selected"; ?>>Select</option>
        <option value="1" <?php if ($type == 1) echo "selected"; ?>>Admin</option>
        <option value="2" <?php if ($type == 2) echo "selected"; ?>>User</option>
    </select><br><br>

    <button type="submit">
        <?php echo ($id == "") ? "Submit" : "Update"; ?>
    </button>

</form>