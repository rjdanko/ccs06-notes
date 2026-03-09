<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Forms</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        label {
            display: inline-block;
            width: 100px;
        }

        #submit {
            padding: 10px 15px;
            margin-left: 90px;
        }
    </style>    
</head>
<body>
    <?php
        include_once "connection.php";

        $edit_id = "";
        $username = $password = $firstname = $middlename = $lastname = $gender = "";
        if (isset($_GET["id"])) {
            $edit_id = (int)$_GET["id"];
            $get_sql = "SELECT * FROM tbl_account WHERE id = ".$edit_id;
            $result = $conn->query($get_sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $username = $row["username"];
                    $firstname = $row["firstname"];
                    $middlename = $row["middlename"];
                    $lastname = $row["lastname"];
                    $gender = $row["gender"];
                }
            }
        }
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $edit_id = isset($_POST["edit_id"]) ? (int)$_POST["edit_id"] : 0;
            $username = test_input($_POST["username"]);
            $password_input = test_input($_POST["password"]);
            $firstname = test_input($_POST["firstname"]);
            $middlename = test_input($_POST["middlename"]);
            $lastname = test_input($_POST["lastname"]);
            $gender = test_input($_POST["gender"]);
            $date_updated = date("Y-m-d H:i:s");
            $date_created = date("Y-m-d H:i:s");

            if ($edit_id > 0) {
                $update_sql = "UPDATE tbl_account SET username='".$username."', firstname='".$firstname."', middlename='".$middlename."', lastname='".$lastname."', gender='".$gender."', date_updated='".$date_updated."'";

                if ($password_input !== "") {
                    $password = sha1($password_input);
                    $update_sql .= ", password='".$password."'";
                }

                $update_sql .= " WHERE id=".$edit_id;

                if ($conn->query($update_sql) === TRUE) {
                    echo "Record updated successfully";
                } else {
                    echo "Error: " . $conn->error;
                }
            } else {
                if ($password_input === "") {
                    echo "Password is required for new records";
                } else {
                    $password = sha1($password_input);
                    $insert_sql = "INSERT INTO tbl_account (username, password, firstname, middlename, lastname, gender, date_created, date_updated) VALUES ('".$username."', '".$password."', '".$firstname."', '".$middlename."', '".$lastname."', '".$gender."', '".$date_created."', '".$date_updated."')";

                    if ($conn->query($insert_sql) === TRUE) {
                        echo "New record created successfully";
                        $edit_id = "";
                        $username = $password = $firstname = $middlename = $lastname = $gender = "";
                    } else {
                        echo "Error: " . $conn->error;
                    }
                }
            }
        }
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

    ?>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Gender</th>
            <th>Date Created</th>
            <th>Date Updated</th>
            <th>Action</th>
            <?php
                $sql = "SELECT * FROM tbl_account";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
            ?>
            <tr>
                <td><?php echo $row["id"]; ?></td>
                <td><?php echo $row["username"]; ?></td>
                <td><?php echo $row["password"]; ?></td>
                <td><?php echo $row["firstname"]; ?></td>
                <td><?php echo $row["middlename"]; ?></td>
                <td><?php echo $row["lastname"]; ?></td>
                <td><?php echo $row["gender"]; ?></td>
                <td><?php echo $row["date_created"]; ?></td>
                <td><?php echo $row["date_updated"]; ?></td>
                <td><a href="form.php?id=<?php echo $row["id"]; ?>">EDIT</a></td>
            </tr>
            <?php
                    }
                }
                $conn->close();
            ?>

    <h1>My PHP Form</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
        <label for="username" >Username: </label>
        <input type="text" id="username" name="username"  value="<?php echo $username?>"required><br><br>

        <label for="password">Password: </label>
        <input type="password" id="password" name="password" <?php echo ($edit_id ? "" : "required"); ?>><br><br>

        <label for="firstname">First name: </label>
        <input type="text" name="firstname"  value="<?php echo $firstname?>"required><br><br>

        <label for="middlename">Middle name: </label>
        <input type="text" name="middlename"  value="<?php echo $middlename?>"><br><br>

        <label for="lastname">Last name: </label>
        <input type="text" name="lastname"  value="<?php echo $lastname?>"required><br><br>

        <!-- <label for="email">Email: </label>
        <input type="email" name="email" required><br><br> -->

        <label for="gender">Gender: </label>
        <select name="gender" required>
            <option value="1" <?php echo ($gender == "1" ? "selected" : ""); ?>>Male</option>
            <option value="2" <?php echo ($gender == "2" ? "selected" : ""); ?>>Female</option>
            <option value="3" <?php echo ($gender == "3" ? "selected" : ""); ?>>Other</option>
        </select><br><br>

        <!-- <label for="dateOfBirth">Date of Birth: </label>
        <input type="date" name="dateOfBirth" required><br><br> -->
        <input type="submit" value="<?php echo ($edit_id ? "Update" : "Register"); ?>" id="submit"><br><br>
    </form>
</body>
</html>