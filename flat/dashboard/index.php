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


if ($is_logged_in == true && !empty($user_data)) {
    $floor = $rent = $room = $phone = $address = $type = $id = $facID = "";
    $facilities = ['wifi' => 'No', 'ac' => 'No', 'gas' => 'No', 'lift' => 'No'];
    $flat_images = [];

    // Edit flat
    $is_edited = false;
    if (isset($_GET['id'], $_GET['action']) && $_GET['action'] == 'edit') {
        $id = (int) $_GET['id'];

        $getinfo = $conn->query("SELECT flatinfo.*, facilities.*, facilities.facID AS facID FROM flatinfo LEFT JOIN facilities ON flatinfo.facID = facilities.facID WHERE flatinfo.flatID = '$id' AND flatinfo.userID='$user_id' ORDER BY date ASC");
        if ($getinfo->num_rows > 0) {
            $is_edited = true;
            $flat_data = $getinfo->fetch_assoc();
            $floor = $flat_data['floor'];
            $rent = $flat_data['rent'];
            $room = $flat_data['room'];
            $phone = $flat_data['mobile'];
            $address = $flat_data['address'];
            $type = $flat_data['type'];
            $facID = $flat_data['facID'];
            $facilities['wifi'] = $flat_data['wifi'];
            $facilities['ac'] = $flat_data['ac'];
            $facilities['gas'] = $flat_data['gas'];
            $facilities['lift'] = $flat_data['lift'];
            $flat_images = unserialize($flat_data['images']);
            $flat_images = is_array($flat_images) ? $flat_images : [];
        }
    }


    // Add flat
    $errors = [];
    $success = [];
    $extension = ["jpeg","jpg","png","gif"];
    if ( isset($_POST['add_flat']) ) {
        if ( isset($_POST['floor']) && !empty($_POST['floor']) ) {
            $floor = $conn->real_escape_string($_POST['floor']);
        } else {
            $errors[] = "Enter the floor";
        }

        if ( isset($_POST['rent']) && !empty($_POST['rent']) ) {
            $rent = $conn->real_escape_string($_POST['rent']);
        } else {
            $errors[] = "Enter the rent";
        }

        if ( isset($_POST['room']) && !empty($_POST['room']) ) {
            $room = $conn->real_escape_string($_POST['room']);
        } else {
            $errors[] = "Enter the room number";
        }

        if ( isset($_POST['phone']) && !empty($_POST['phone']) ) {
            $phone = $conn->real_escape_string($_POST['phone']);
        } else {
            $errors[] = "Enter the rent";
        }

        if ( isset($_POST['address']) && !empty($_POST['address']) ) {
            $address = $conn->real_escape_string($_POST['address']);
        } else {
            $errors[] = "Enter the flat location";
        }

        $old_imges = [];
        if ( $is_edited && !empty($flat_images) && isset($_POST['preloaded']) ) {
            foreach($_POST['preloaded'] as $imgid){
                if (isset($flat_images[$imgid-1])){
                    $old_imges[] = $flat_images[$imgid-1];
                }
            }
        }

        if ( isset($_POST['wifi']) ) {
            $facilities['wifi'] = 'Yes';
        }
        if ( isset($_POST['ac']) ) {
            $facilities['ac'] = 'Yes';
        }
        if ( isset($_POST['gas']) ) {
            $facilities['gas'] = 'Yes';
        }
        if ( isset($_POST['lift']) ) {
            $facilities['lift'] = 'Yes';
        }

        if ( isset($_POST['type']) && !empty($_POST['type']) ) {
            $type = $conn->real_escape_string($_POST['type']);
        } else {
            $errors[] = "Select the flat type";
        }

        if ( empty($errors) ) {
            // Insert into database
            $se_facilities = serialize($facilities);
            $date = date('Y-m-d H:i:s');
            
            foreach($_FILES["photos"]["tmp_name"] as $key => $tmp_name) {
                $file_name = $_FILES["photos"]["name"][$key];
                $file_tmp = $_FILES["photos"]["tmp_name"][$key];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);

                $image_name = $file_name;
                if(file_exists("uploads/" . $file_name)) {
                    $filename = basename($file_name, $ext);
                    $image_name = $filename . time() . "." . $ext;
                }
                
                $is_uploaded = move_uploaded_file($file_tmp, ROOTPATH . "/uploads/" . $image_name);
                if ( $is_uploaded ){
                    $old_imges[] = '/uploads/' . $image_name;
                }
            }

            $images_se = $conn->real_escape_string(serialize($old_imges));
            $is_wifi = $facilities['wifi'];
            $is_ac = $facilities['ac'];
            $is_gas = $facilities['gas'];
            $is_lift = $facilities['lift'];


            if ( isset($_POST['is_edit']) && $_POST['is_edit'] == 1 ) {
                $update_fac = $conn->query("UPDATE facilities SET wifi='$is_wifi', ac='$is_ac', gas='$is_gas', lift='$is_lift' WHERE facID='$facID'");

                if ($update_fac) {
                    $is_updates = $conn->query("UPDATE flatinfo SET type='$type', floor='$floor', rent='$rent', room='$room', address='$address', mobile='$phone', images='$images_se' WHERE flatID='$id' AND userID='$user_id'");

                    if ($is_updates) {
                        header("Location: flats.php?edit_flat=true");
                    } else {
                        $errors[] = "There is an error to update flat info";
                    }
                } else {
                    $errors[] = "There is an error to update facilities info";
                }
            } else {
                $add_fac = "INSERT INTO facilities (facID, wifi, ac, gas, lift) VALUES (NULL, '$is_wifi', '$is_ac', '$is_gas', '$is_lift')";

                if ($conn->query($add_fac)) {
                    $facID = $conn->insert_id;
                    $sql = "INSERT INTO flatinfo (flatID, userID, status, type, floor, rent, room, address, mobile, facID, date, bookedDate, bookedUser, images) VALUES (NULL, '$user_id', 'Available', '$type', '$floor', '$rent', '$room', '$address', '$phone', '$facID', '$date', NULL, NULL, '$images_se')";

                    if ($conn->query($sql)) {
                        $success[] = "New flat addedd successfully";
                    } else {
                        $errors[] = "There is an error to insert flat info";
                    }
                } else {
                    $errors[] = "There is an error to insert facilities info";
                }
            }
        }
    } 

    // General users flat list
    $flats = [];
    if ($user_role == "general") {
        if (isset($_GET['send_request'], $_GET['id']) && ($_GET['send_request'] == 'true' || $_GET['send_request'] == 'false')){
            $flat_id = (int) $_GET['id'];
            $req_type = $_GET['send_request'];

            $getinfo = $conn->query("SELECT * FROM flatinfo WHERE flatinfo.userID != '$user_id' ORDER BY date ASC");

            if ($getinfo->num_rows > 0) {
                $flat_data = $getinfo->fetch_assoc();
                $date = date('Y-m-d H:i:s');

                if ($req_type == 'true') {
                    $add_booking = "INSERT INTO bookinginfo (bookID, userID, flatID, reqDate) SELECT NULL, '$user_id', '$flat_id', '$date' FROM DUAL WHERE NOT EXISTS (SELECT * FROM bookinginfo WHERE userID = '$user_id' AND flatID = '$flat_id' LIMIT 1)";

                    if ($conn->query($add_booking)) {
                        header("Location: index.php?request_book=true");
                    }
                } else {
                    $is_delete = $conn->query("DELETE FROM bookinginfo WHERE userID='$user_id' AND flatID='$flat_id'");

                    if ( $is_delete ) {
                        header("Location: index.php?request_book=false");
                    }
                }
            }
        }

        $result = $conn->query("SELECT flatinfo.*, facilities.*, facilities.facID AS facID, (SELECT COUNT(*) FROM bookinginfo WHERE bookinginfo.userID = '$user_id' AND bookinginfo.flatID = flatinfo.flatID) as bookReq  FROM flatinfo LEFT JOIN facilities ON flatinfo.facID = facilities.facID WHERE ((flatinfo.status = 'Available') OR (flatinfo.status = 'Booked' AND flatinfo.bookedUser = '$user_id')) ORDER BY date ASC");

        if ($result->num_rows > 0) {
            $flats = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Admin user users list
    $users = [];
    if ($user_role == "administrator") {
        $user_res = $conn->query("SELECT user.*, user_role.name AS role FROM user LEFT JOIN user_role on user.roleID = user_role.roleID ORDER BY date ASC");
        $total_user = $user_res->num_rows;

        if ($total_user > 0) {
            $users = $user_res->fetch_all(MYSQLI_ASSOC);
        }

        // Delete Users
        if ( isset($_GET['uid'], $_GET['action']) && $_GET['action'] == 'delete' && $_GET['uid'] != $user_id ) {
            $uid = (int) $_GET['uid'];
            $is_delete = $conn->query("DELETE FROM users WHERE ID='$uid'");

            if ( $is_delete ) {
                $conn->query("UPDATE flat_info SET status='Trash' WHERE user_id='$uid'");
                header("Location: index.php?deleted_user=true");
            } else {
                header("Location: index.php?deleted_user=false");
            }
        }

    } ?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Flat management system</title>
    <link href="../css/jquery-ui.min.css" rel="stylesheet">
    <link href="../css/jquery.dataTables.css" rel="stylesheet" type="text/css">
	<link href="../css/jquery.dataTables_themeroller.css" rel="stylesheet" type="text/css">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/jquery.dataTables.yadcf.css" rel="stylesheet">
    <link href="../css/image-uploader.min.css" rel="stylesheet">
    <link href="../css/magnific-popup.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/design.css">
    
  </head>
    <body class="bg-light" id="background">
        <div class="container">
            <main>
                <?php switch($user_role) {
                    case 'administrator': ?>
                        <nav class="navbar navbar-expand-lg navbar-light bg-light">
                            <a class="navbar-brand" href="#">Flat Management</a>
                            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                <ul class="navbar-nav mr-auto">
                                    <li class="nav-item active">
                                        <a class="nav-link" href="index.php">All Users</a>
                                    </li>
                                    <li class="nav-item">
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

                        <h1 class="text-center">All Users (<?php echo $total_user; ?>)</h1>
                        <a href="user.php" class="btn btn-secondary mb-3">Add New user</a>

                        <?php if ( isset($_GET['deleted_user']) && $_GET['deleted_user'] == true ) {
                            echo '<div class="alert alert-success" role="alert">User deleted successfully</div>';
                        } else if ( isset($_GET['deleted_user']) && $_GET['deleted_user'] == false ) {
                            echo '<div class="alert alert-danger" role="alert">There was a problem to delete user</div>';
                        } else if ( isset($_GET['edited_user']) && $_GET['edited_user'] == true ) {
                            echo '<div class="alert alert-success" role="alert">User updated successfully</div>';
                        } ?>

                        <table id="users_table" width="100%">
                            <thead>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th>Registration date</th>
                                <th>Actions</th>
                            </thead>
                            <tbody>
                                <?php if(!empty($users)) { ?>
                                    <?php foreach($users as $user) { ?>
                                        <tr>
                                            <td>#<?php echo $user['userID']; ?></td>
                                            <td><?php echo $user['username']; ?><br/><button type="button" class="btn btn-dark btn-sm"><?php echo ucfirst($user['role']); ?></button></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['name']; ?></td>
                                            <td><?php echo $user['address']; ?></td>
                                            <td><?php echo $user['phone']; ?></td>
                                            <td><?php echo date('F j, Y', strtotime($user['date'])); ?></td>
                                            <td>
                                                <a href="user.php?uid=<?php echo $user['userID']; ?>&action=edit" class="btn btn-primary mt-1 mb-1">Edit</a>
                                                <?php if ( $user_id != $user['userID'] ) { ?>
                                                    <a href="index.php?uid=<?php echo $user['userID']; ?>&action=delete" class="btn btn-danger mt-1 mb-1">Delete</a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>

                    <?php break;

                    case 'flat_owner': ?>

                        <nav class="navbar navbar-expand-lg navbar-light bg-light">
                            <a class="navbar-brand" href="#">Flat Management</a>
                            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                <ul class="navbar-nav mr-auto">
                                    <li class="nav-item active">
                                        <a class="nav-link" href="index.php">Add Flat</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="flats.php">Added Flats</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../logout.php">Logout</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                        <h1 class="text-center"><?php echo 'Welcome ' . $user_data['name']; ?></h1>
                       
                        <div class="row">
                            <div class="col-md-6 m-auto">
                                <form action="" method="POST" class="row g-3"  enctype="multipart/form-data">
                                    <?php if( is_array($errors) && !empty($errors) ) {
                                        foreach($errors as $error) {
                                            echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
                                        }
                                    }
                                    
                                    if( is_array($success) && !empty($success) ) {
                                        foreach($success as $succ) {
                                            echo '<div class="alert alert-success" role="alert">' . $succ . '</div>';
                                        }
                                    } ?>
                                    <h3><?php echo ($is_edited) ? "Edit flat: " . $id : "Add Flat"; ?></h3>

                                    <div class="col-12">
                                        <label>Floor Number</label>
                                        <input type="text" name="floor" class="form-control" placeholder="Floor number" value="<?php echo $floor; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label>Rent</label>
                                        <input type="number" name="rent" class="form-control" placeholder="Rent" value="<?php echo $rent; ?>" required min="0">
                                    </div>
                                    <div class="col-12">
                                        <label>Room Number</label>
                                        <input type="text" name="room" class="form-control" placeholder="Room number" value="<?php echo $room; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label>Mobile Number</label>
                                        <input type="text" name="phone" class="form-control" placeholder="Mobile number" value="<?php echo $phone; ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label>Address</label>
                                        <textarea name="address" rows="3" class="form-control"><?php echo $address; ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label>Facilities</label>
                                        <?php foreach($facilities as $key => $fac) {
                                            $checked = ($fac == 'Yes') ? 'checked="checked"' : ''; ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="<?php echo $key.'_fac'; ?>" <?php echo $checked; ?> name="<?php echo $key; ?>" vlaue="1">
                                                <label class="form-check-label" for="<?php echo $key.'_fac'; ?>"><?php echo ucfirst($key); ?></label>
                                            </div>
                                            <?php 
                                        } ?>
                                    </div>
                                    <div class="col-12">
                                        <label>Room image</label>
                                        <?php if($is_edited) { ?>
                                            <script>
                                                var uimage = [];
                                                <?php $i=1;
                                                foreach($flat_images as $imgsrc) { ?>
                                                    var arr = {id: <?php echo $i; ?>, src: '<?php echo HOMEURL . $imgsrc; ?>'};
                                                    uimage.push(arr);
                                                    <?php $i++;
                                                } ?>
                                            </script>
                                        <?php } ?>
                                        <div class="room-photos"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" value="Flat" id="flat_type" <?php echo ($type == "Flat" || empty($type)) ? 'checked="checked"' : ''; ?>>
                                            <label class="form-check-label" for="flat_type">Flat</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" value="Mess" id="mess_type" <?php echo ($type == "Mess") ? 'checked="checked"' : ''; ?>>
                                            <label class="form-check-label" for="mess_type">Mess</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" value="Cottage" id="cottage_type" <?php echo ($type == "Cottage") ? 'checked="checked"' : ''; ?>>
                                            <label class="form-check-label" for="cottage_type">Cottage</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <input type="hidden" name="is_edit" value="<?php echo ($is_edited) ? "1" : "0"; ?>">
                                        <button type="submit" name="add_flat" class="btn btn-dark float-end"><?php echo ($is_edited) ? "Update Flat" : "Add Flat"; ?></button>
                                    </div>
                                    <script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>
                                </form>
                            </div>
                        </div>

                    <?php 
                    break;

                    case 'general': ?>
                        <nav class="navbar navbar-expand-lg navbar-light bg-light">
                            <a class="navbar-brand" href="#">Flat Management</a>
                            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                <ul class="navbar-nav mr-auto">
                                    <li class="nav-item active">
                                        <a class="nav-link" href="index.php">Dashboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../logout.php">Logout</a>
                                    </li>
                                </ul>
                            </div>
                        </nav>

                        <h1 class="text-center">Book Flat</h1>

                        <?php if ( isset($_GET['request_book']) && $_GET['request_book'] == true ) {
                            echo '<div class="alert alert-success" role="alert">Booking Request Updated</div>';
                        } ?>

                        <table cellpadding="0" cellspacing="0" border="0"  width="100%" class="display second_third" id="flat_table">
                            <thead>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Address</th>
                                <th>Floor No.</th>
                                <th>Rent</th>
                                <th>Room</th>
                                <th>Facilities</th>
                            </thead>
                            <tbody>
                                <?php if(!empty($flats)) { ?>
                                    <?php foreach($flats as $flat) { ?>
                                        <tr>
                                            <td>#<?php echo $flat['flatID']; ?></td>
                                            <td>
                                                <?php echo $flat['type']; ?></td>
                                            <td><?php echo $flat['address'] . '<br/>Mobile: ' . $flat['mobile']; ?>
                                                <?php $images = unserialize($flat['images']);
                                                if ( is_array($images) && !empty($images) ) { ?>
                                                    <div class="popup-gallery">
                                                        <?php foreach($images as $image) { ?>
                                                            <a href="<?php echo HOMEURL . $image; ?>"><img src="<?php echo HOMEURL . $image; ?>" height="50" width="50"></a>
                                                        <?php } ?>
                                                    </div>
                                                <?php }

                                                $is_request = false;
                                                $req_url = '?send_request=true&id='.$flat['flatID'];
                                                $bookclass = "btn btn-outline-success btn-sm";
                                                if ( $flat['bookReq'] >= 1 ) {
                                                    $is_request = true;
                                                    $req_url = '?send_request=false&id='.$flat['flatID'];
                                                    $bookclass = "btn btn-outline-danger btn-sm";
                                                }
                                                if ($flat['bookedUser'] == $user_id) { ?>
                                                    <button type="button" class="btn btn-success btn-sm">Booked</button>
                                                <?php } else { ?>
                                                    <a href="<?php echo $req_url; ?>" class="<?php echo $bookclass; ?>" onclick="if (confirm('Make this action?')){return true;}else{event.stopPropagation(); event.preventDefault();};"><?php echo ($is_request) ? 'Cancel Request' : 'Request for Booking'; ?></a>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo $flat['floor']; ?></td>
                                            <td><?php echo $flat['rent']; ?></td>
                                            <td><?php echo $flat['room']; ?></td>
                                            <td>
                                            <?php foreach($facilities as $key => $fac) {
                                                $checked = ($flat[$key] == "Yes") ? 'checked="checked"' : ''; ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="<?php echo $key.'_fac'; ?>" <?php echo $checked; ?> disabled>
                                                    <label class="form-check-label" for="<?php echo $key.'_fac'; ?>"><?php echo ucfirst($key); ?></label>
                                                </div>
                                            <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>

                    <?php break;

                    default:
                        "Invalid access";
                    break;
                } ?>
            </main>
        </div>
        
        <script type="text/javascript" src="../js/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/bootstrap.bundle.min.js"></script>
        <script type="text/javascript" src="../js/pdfmake.min.js"></script>
        <script type="text/javascript" src="../js/vfs_fonts.js"></script>
        <script type="text/javascript" src="../js/jquery.dataTables.full.js"></script>
        <script type="text/javascript" src="../js/jquery.dataTables.yadcf.js"></script>
        <script type="text/javascript" src="../js/image-uploader.min.js"></script>
        <script type="text/javascript" src="../js/jquery.magnific-popup.min.js"></script>
        <script type="text/javascript" src="../js/script.js"></script>
    </body>
</html>
<?php } ?>