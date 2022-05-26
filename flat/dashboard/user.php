<?php

require '../config.php';

$is_logged_in = false;
$user_id = 0;
$user_role = '';
$user_data = [];
if(isset($_COOKIE['logID'], $_COOKIE['logKey'])){ 
    $logID = $_COOKIE['logID']; 
    $logKey = $_COOKIE['logKey']; 
    $result = $conn->query("SELECT user.*, loginfo.logID, loginfo.userID FROM loginfo RIGHT JOIN user on loginfo.userID = user.userID WHERE loginfo.logID = '$logID' AND loginfo.securityKey='$logKey'");
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        if ( isset($user_data['userID'], $user_data['username'], $user_data['roleID']) ) {
            $is_logged_in = true;
            $user_id = (int) $user_data['userID'];
            $role_Id = (int) $user_data['roleID'];

            $role_res = $conn->query("SELECT * FROM user_role WHERE roleID = '$role_Id'");
            if ($role_res->num_rows > 0) {
                $role_data = $role_res->fetch_assoc();
                $user_role = $role_data['name'];
            }
        }
    } else {
       header("Location: ../login.php");
    }
} else{
   header("Location: ../login.php");
}


if ($is_logged_in == true && !empty($user_data) && $user_role == 'administrator') {
    $is_edited = false;
    $username = $email = $name = $phone = $type = $password = $address = $repassword = "";
    $errors = $success = [];

    if ( isset($_GET['uid'], $_GET['action']) && $_GET['action'] == 'edit' ) {
        $uid = (int) $_GET['uid'];

        $getinfo = $conn->query("SELECT user.*, user_role.roleID AS roleID FROM user LEFT JOIN user_role on user.roleID = user_role.roleID WHERE user.userID = '$uid'");
        if ($getinfo->num_rows > 0) {
            $is_edited = true;
            $user_info = $getinfo->fetch_assoc();
            $username = $user_info['username'];
            $email = $user_info['email'];
            $name = $user_info['name'];
            $phone = $user_info['phone'];
            $type = $user_info['roleID'];
            $address = $user_info['address'];
        }
    }

    if ( isset($_POST['signup']) ) {
        if (!$is_edited) {
            if ( isset($_POST['username']) && !empty($_POST['username']) ) {
                if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/', $_POST['username']) || preg_match('/\s/', $_POST['username']) ) {
                    $errors[] = "Username can't contains special charaters or white space";
                } else {
                    $username = $conn->real_escape_string($_POST['username']);
                }
            } else {
                $errors[] = "Enter Username";
            }
        }

        if ( isset($_POST['email']) && !empty($_POST['email']) ) {
            $email = $conn->real_escape_string($_POST['email']);
        } else {
            $errors[] = "Enter Valid Email address";
        }

        if ( isset($_POST['name']) && !empty($_POST['name']) ) {
            $name = $conn->real_escape_string($_POST['name']);
        } else {
            $errors[] = "Enter Your Name";
        }

        if ( isset($_POST['address']) && !empty($_POST['address']) ) {
            $address = $conn->real_escape_string($_POST['address']);
        } else {
            $errors[] = "Enter Your Name";
        }

        if ( isset($_POST['phone']) && !empty($_POST['phone']) ) {
            $phone = $conn->real_escape_string($_POST['phone']);
        } else {
            $errors[] = "Enter the mobile number";
        }

        if ( isset($_POST['type']) && !empty($_POST['type']) ) {
            $type = $conn->real_escape_string($_POST['type']);
        } else {
            $errors[] = "Select user role";
        }


        if ( $is_edited && isset($_GET['update_psw']) ) {
            if ( isset($_POST['password']) && !empty($_POST['password']) ) {
                if ( strlen($_POST['password']) < 6 ) {
                    $errors[] = "Password must be at leat 6 characters long";
                } else {
                    $password = $_POST['password'];
                }
            } else {
                $errors[] = "Enter the password";
            }

            if ( isset($_POST['repassword']) && !empty($_POST['repassword']) ) {
                $repassword = $_POST['repassword'];
            } else {
                $errors[] = "Enter the repeat password";
            }
        } else {
            if ( strlen($_POST['password']) < 6 ) {
                $errors[] = "Password must be at leat 6 characters long";
            } else {
                $password = $_POST['password'];
            }
        }

        if ( empty($errors) ) {
            if ( $password != $repassword && $is_edited && isset($_GET['update_psw']) ) {
                $errors[] = "Password & repeat password doesn't match";
            } else {
                $upass = false;
                $result = $conn->query("SELECT * FROM user WHERE username='$username'");
                if (!$is_edited && $result->num_rows > 0) {
                    $errors[] = "Username already exist";
                } else {
                    if ($is_edited) {
                        $result2 = $conn->query("SELECT * FROM user WHERE email='$email' AND userID<>'$uid'");
                    } else {
                        $result2 = $conn->query("SELECT * FROM user WHERE email='$email'");
                    }
                    
                    if ($result2->num_rows > 0) {
                        $errors[] = "Email is already registered";
                    } else {
                        $password = md5($password);
                        $date = date('Y-m-d H:i:s');

                        if ( $is_edited ) {
                            // Edit user
                            if (isset($_GET['update_psw'])) {
                                $is_updates = $conn->query("UPDATE user SET password='$password', email='$email', roleID='$type', name='$name', address='$address', phone='$phone' WHERE userID='$uid'");
                            } else {
                                $is_updates = $conn->query("UPDATE user SET email='$email', roleID='$type', name='$name', address='$address', phone='$phone' WHERE userID='$uid'");
                            }

                            if ($is_updates) {
                                $success[] = "User updated addedd successfully";
                                $username = $email = $name = $phone = $type = $password = $address = $repassword = "";
                                header("Location: index.php?edited_user=true");
                            } else {
                                $errors[] = "There is an error to edit user info";
                            }

                        } else {
                            // Add user
                            $sql = "INSERT INTO user (userID, username, roleID, email, password, name, address, phone, date) 
                                VALUES (NULL, '$username', '$type', '$email', '$password', '$name', '$address', '$phone', '$date')";

                            if ($conn->query($sql)) {
                                $success[] = "New user addedd successfully";
                                $username = $email = $name = $phone = $type = $password = $address = $repassword = "";
                            } else {
                                $errors[] = "There is an error to insert new user";
                            }
                        }
                    }
                }
            }
        }
        
    }
    
    
    
    
    ?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Flat management system</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/datatables.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
  </head>
    <body class="bg-light">
        <div class="container">
            <main>
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <a class="navbar-brand" href="#">Flat Management</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav mr-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">All Users</a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="user.php">Add User</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_flats.php">All Flats</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <h1 class="text-center"><?php echo ($is_edited) ? "Edit User" : "Add user"; ?></h1>

                <?php if( is_array($errors) && !empty($errors) ) {
                    foreach($errors as $error) {
                        echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                    }
                } else if( is_array($success) && !empty($success) ) {
                    foreach($success as $succ) {
                        echo '<div class="alert alert-success" role="alert">' . $succ . '</div>';
                    }
                } ?>

                <div class="row">
                    <div class="col-md-6 col-auto">
                        <form action="" method="POST" class="row g-3">
                            <?php if(!$is_edited) { ?>
                                <div class="col-12">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $username; ?>" require>
                                    <div class="form-text">Without space & Special characters.</div>
                                </div>
                            <?php } ?>
                            <div class="col-12">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo $email; ?>" require>
                            </div>
                            <div class="col-12">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Full Name" value="<?php echo $name; ?>" require>
                            </div>
                            <div class="col-12">
                                <label>Address</label>
                                <textarea name="address" rows="2" class="form-control" placeholder="Address" require><?php echo $address; ?></textarea>
                            </div>
                            <div class="col-12">
                                <label>Mobile</label>
                                <input type="text" name="phone" class="form-control" placeholder="Phone" value="<?php echo $phone; ?>" require>
                            </div>
                            <div class="col-12">
                                <label>Register as</label>
                                <select name="type" class="form-control"> -->
                                    <?php 
                                    $user_role = $conn->query("SELECT * FROM user_role ORDER BY roleID ASC");

                                    if ($user_role->num_rows > 0) {
                                        $roles = $user_role->fetch_all(MYSQLI_ASSOC);                                        if ( is_array($roles) && !empty($roles) ) {
                                            foreach($roles as $role) { ?>
                                                <option value="<?php echo $role['roleID']; ?>" <?php echo ($type ==  $role['roleID']) ? 'selected="selected"' : ''; ?>><?php echo ucfirst($role['name']); ?></option>
                                            <?php }
                                        }
                                    } ?>
                                </select>
                            </div>
                            <?php if ($is_edited) { ?>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="update_psw" value="1" id="up_pass">
                                        <label class="form-check-label" for="up_pass">Update Password</label>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="col-12" <?php echo ($is_edited) ? 'style="display:none;"' : ''; ?>>
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" require>
                            </div>
                            <div class="col-12" <?php echo ($is_edited) ? 'style="display:none;"' : ''; ?>>
                                <label>Repeat password</label>
                                <input type="password" name="repassword" class="form-control" placeholder="Repeat Password" require>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="signup" class="btn btn-dark"><?php echo ($is_edited) ? "Update User" : "Add user"; ?></button>
                            </div>
                            <script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>
                        </form>
                    </div>
                </div>

            </main>
        </div>
        <script type="text/javascript" src="../js/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="../js/bootstrap.bundle.min.js"></script>
        <script type="text/javascript" src="../js/pdfmake.min.js"></script>
        <script type="text/javascript" src="../js/vfs_fonts.js"></script>
        <script type="text/javascript" src="../js/datatables.min.js"></script>
        <script type="text/javascript" src="../js/script.js"></script>
    </body>
</html>

<?php }