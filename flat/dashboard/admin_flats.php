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
    // Get flat
    $facilities = ['wifi' => 'No', 'ac' => 'No', 'gas' => 'No', 'lift' => 'No'];

    $result = $conn->query("SELECT flatinfo.*, facilities.*, facilities.facID AS facID FROM flatinfo LEFT JOIN facilities ON flatinfo.facID = facilities.facID ORDER BY date ASC");

    $flats = [];
    if ($result->num_rows > 0) {
        $flats = $result->fetch_all(MYSQLI_ASSOC);
    } ?>
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

                <h1 class="text-center">All Flats</h1>

                <table id="flat_table" width="100%">
                    <thead>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Owner</th>
                        <th>Address</th>
                        <th>Floor No.</th>
                        <th>Rent</th>
                        <th>Room</th>
                        <th>Facilities</th>
                        <th>Time</th>
                    </thead>
                    <tbody>
                        <?php if(!empty($flats)) { ?>
                            <?php foreach($flats as $flat) {
                                $flat_ID = $flat['flatID']; ?>
                                <tr>
                                    <td>#<?php echo $flat_ID ?></td>
                                    <td>
                                        <?php echo $flat['type']; ?>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="_status" <?php echo ($flat['status'] == "Booked") ? 'checked="checked"' : ''; ?> disabled>
                                            <label class="form-check-label" for="_status">Booked</label>
                                        </div>
                                        <?php $bkduid = $flat['bookedUser'];
                                        $bkdresult = $conn->query("SELECT * FROM user WHERE userID = '$bkduid'");
                                        if ($bkdresult->num_rows > 0) {
                                            $booked_udata = $bkdresult->fetch_assoc();
                                            echo 'Booked for: ' . $booked_udata['name'];
                                        } ?>

                                        <?php switch($flat['status']) {
                                            case 'Available': 
                                                echo '<button type="button" class="btn btn-outline-success btn-sm">'.$flat['status'].'</button>';
                                                break;
                                            case 'Booked':
                                                echo '<button type="button" class="btn btn-outline-secondary btn-sm">'.$flat['status'].'</button>';
                                                break;
                                            default: 
                                                echo '<button type="button" class="btn btn-outline-danger btn-sm">'.$flat['status'].'</button>';
                                            break;
                                        } ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $user_i = $flat['userID'];
                                        $own_res = $conn->query("SELECT * FROM user WHERE userID = '$user_i'");
                                        if ($own_res->num_rows > 0) {
                                            $owner_data = $own_res->fetch_assoc();
                                            echo $owner_data['name'] . '(' . $owner_data['username'] . ')';
                                        } else {
                                            echo 'User not Exist';
                                        } ?>
                                    </td>
                                    <td><?php echo $flat['address'] . '<br/>Mobile: ' . $flat['mobile']; ?></td>
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
                                            <?php 
                                        } ?>
                                    </td>
                                    <td><?php echo date('F j, Y', strtotime($flat['date'])); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>

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

<?php
 } 
 ?>