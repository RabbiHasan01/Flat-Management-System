<?php 

require 'config.php';

$username = $email = $name = $phone = $type = $password = $address = $repassword = "";
$errors = $success = [];

if ( isset($_POST['signup']) ) {
    if ( isset($_POST['username']) && !empty($_POST['username']) ) {
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/', $_POST['username']) || preg_match('/\s/', $_POST['username']) ) {
            $errors[] = "Username can't contains special charaters or white space";
        } else {
            $username = $conn->real_escape_string($_POST['username']);
        }
    } else {
        $errors[] = "Enter Username";
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

    if ( empty($errors) ) { //if error true.....
        if ( $password != $repassword ) {
            $errors[] = "Password & repeat password doesn't match";
        } else {
            $upass = false;
            $result = $conn->query("SELECT * FROM user WHERE username='$username'");
            if ($result->num_rows > 0) {
                $errors[] = "Username already exist";
            } else {
                $result2 = $conn->query("SELECT * FROM user WHERE email='$email'");
                if ($result2->num_rows > 0) {
                    $errors[] = "Email is already registered";
                } else {
                    $password = md5($password);
                    $date = date('Y-m-d H:i:s');
              
                    $sql = "INSERT INTO user (userID, username, roleID, email, password, name, address, phone, date) 
                                VALUES (NULL, '$username', '$type', '$email', '$password', '$name', '$address', '$phone', '$date')";

                    if ($conn->query($sql)) {
                        $_POST = array();
                        $success[] = "New user addedd successfully. <a href='login.php'>Click here</a> to login";
                        $username = $email = $name = $phone = $type = $password = $address = $repassword = "";
                        header("Location: login.php");
                    } else {
                        $errors[] = "There is an error to insert new user";
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
    <title>Flat management system</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
  </head>
    <body class="bg-light">
        <div class="container">
            <main>
                <h1 class="text-center">SignUp | <a href="login.php">Login</a></h1>
                
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
                            <div class="col-12">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $username; ?>" require>
                                <div class="form-text">Without space & Special characters.</div>
                            </div>
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
                                <select name="type" class="form-control">
                                <?php 
                                    $user_role = $conn->query("SELECT * FROM user_role ORDER BY roleID ASC");

                                    if ($user_role->num_rows > 0) {
                                        $roles = $user_role->fetch_all(MYSQLI_ASSOC);
                                        if ( is_array($roles) && !empty($roles) ) {
                                            foreach($roles as $role) { 
                                                if ($role['name'] == 'administrator') continue; ?>
                                                <option value="<?php echo $role['roleID']; ?>" <?php echo ($type ==  $role['roleID']) ? 'selected="selected"' : ''; ?>><?php echo ucfirst($role['name']); ?></option>
                                            <?php }
                                        }
                                    } ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" require>
                            </div>
                            <div class="col-12">
                                <label>Repeat password</label>
                                <input type="password" name="repassword" class="form-control" placeholder="Repeat Password" require>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="signup" class="btn btn-dark">SignUp</button>
                            </div>
                            <script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
