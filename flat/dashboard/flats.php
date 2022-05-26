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

$success = [];
if ($is_logged_in == true && !empty($user_data)) {
    $facilities = ['wifi' => 'No', 'ac' => 'No', 'gas' => 'No', 'lift' => 'No'];

    // flat_owner see flat
    $result = $conn->query("SELECT flatinfo.*, facilities.*, facilities.facID AS facID FROM flatinfo LEFT JOIN facilities ON flatinfo.facID = facilities.facID WHERE flatinfo.userID='$user_id' AND flatinfo.status IN('Available', 'Booked') ORDER BY date ASC");
    $flats = [];
    if ($result->num_rows > 0) {
        $flats = $result->fetch_all(MYSQLI_ASSOC);
    }

    // actions
    if ( isset($_GET['id'], $_GET['action']) && !empty($_GET['action']) ) {
        $id = (int) $_GET['id'];
        $actions = $_GET['action'];

        switch($actions) {
            case 'delete':
                // Delete flat
                $is_delete = $conn->query("DELETE FROM flatinfo WHERE userID='$user_id' AND flatID='$id'");

                if ( $is_delete ) {
                    header("Location: flats.php?deleted_flat=true");
                } else {
                    header("Location: flats.php?deleted_flat=false");
                }
            break;

            case 'booked':
                // booked
                $user_i = isset($_GET['user']) ? (int) $_GET['user'] : 0;
                if ($user_i > 0) {
                    $is_booked = $conn->query("UPDATE flatinfo SET status='Booked', bookedUser='$user_i' WHERE flatID='$id' AND userID='$user_id'");

                    if ( $is_booked ) {
                        header("Location: flats.php?booked_flat=true");
                    } else {
                        header("Location: flats.php?booked_flat=false");
                    }
                } else {
                    header("Location: flats.php?booked_flat=false");
                }
            break;

            case 'unbooked':
                // Unbooked
                $is_unbooked = $conn->query("UPDATE flatinfo SET status='Available', bookedUser=NULL WHERE flatID='$id' AND userID='$user_id'");

                if ( $is_unbooked ) {
                    header("Location: flats.php?unbooked_flat=true");
                } else {
                    header("Location: flats.php?unbooked_flat=false");
                }
            break;

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
                                <a class="nav-link" href="index.php">Add Flat</a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="flats.php">Added Flats</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </nav>
                <h1 class="text-center">Added Flats</h1>

                <?php if ( isset($_GET['deleted_flat']) && $_GET['deleted_flat'] == true ) {
                    echo '<div class="alert alert-success" role="alert">Flat deleted successfully</div>';
                } else if ( isset($_GET['deleted_flat']) && $_GET['deleted_flat'] == false ) {
                    echo '<div class="alert alert-danger" role="alert">There was a problem to delete flat</div>';
                } else if ( isset($_GET['booked_flat']) && $_GET['booked_flat'] == true ) {
                    echo '<div class="alert alert-success" role="alert">Flat booked successfully</div>';
                } else if ( isset($_GET['booked_flat']) && $_GET['booked_flat'] == false ) {
                    echo '<div class="alert alert-danger" role="alert">There was a problem to booked the flat</div>';
                } else if ( isset($_GET['unbooked_flat']) && $_GET['unbooked_flat'] == true ) {
                    echo '<div class="alert alert-success" role="alert">Flat unbooked successfully</div>';
                } else if ( isset($_GET['unbooked_flat']) && $_GET['unbooked_flat'] == false ) {
                    echo '<div class="alert alert-danger" role="alert">There was a problem to unbooked the flat</div>';
                } else if ( isset($_GET['edit_flat']) && $_GET['edit_flat'] == true ) {
                    echo '<div class="alert alert-success" role="alert">Flat updated successfully</div>';
                } ?>

                <table id="flat_table_manager" width="100%">
                    <thead>
                        <th>ID</th>

                        <th>Type</th>
                        <th>Address</th>
                        <th>Floor No.</th>
                        <th>Rent</th>
                        <th>Room</th>
                        <th>Facilities</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        <?php if(!empty($flats)) { ?>
                            <?php foreach($flats as $flat) { 
                                $flat_ID = $flat['flatID']; ?>
                                <tr>
                                    <td>#<?php echo $flat_ID; ?></td>
                                    <td>
                                        <?php echo $flat['type']; ?>
                                        <?php $images = unserialize($flat['images']);
                                        if ( is_array($images) && !empty($images) ) { ?>
                                            <div class="popup-gallery">
                                                <?php foreach($images as $image) { ?>
                                                    <a href="<?php echo HOMEURL . $image; ?>"><img src="<?php echo HOMEURL . $image; ?>" height="50" width="50"></a>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>

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
                                    <td>
                                        <?php 
                                        $booking_qry = $conn->query("SELECT * FROM bookinginfo WHERE flatID = '$flat_ID' ORDER BY reqDate ASC");
                                        $book_req = [];
                                        if ($booking_qry->num_rows > 0) {
                                            $book_req = $booking_qry->fetch_all(MYSQLI_ASSOC);
                                        } ?>
                                        <a href="index.php?id=<?php echo $flat_ID; ?>&action=edit" class="btn btn-primary mt-1 mb-1">Edit</a>
                                        <a href="flats.php?id=<?php echo $flat_ID; ?>&action=delete" class="btn btn-danger mt-1 mb-1" onclick="return confirm('Are you sure want to delete?')">Delete</a>
                                        
                                        <?php if ( $flat['status'] == "Booked" ) { ?>
                                            <a href="flats.php?id=<?php echo $flat_ID; ?>&action=unbooked" class="btn btn-secondary mt-1 mb-1">Unbooked</a>
                                        <?php } else if (!empty($book_req)) { ?> 
                                            <button class="btn btn-secondary mt-1 mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBookreq<?php echo $flat_ID; ?>" aria-expanded="false" aria-controls="collapseBookreq<?php echo $flat_ID; ?>">View Request</button>
                                            <div class="collapse" id="collapseBookreq<?php echo $flat_ID; ?>">
                                                <div class="card card-body">
                                                    <ul class="list-group">
                                                        <?php foreach($book_req as $book) {
                                                            $buid = $book['userID'];
                                                            $uresult = $conn->query("SELECT * FROM user WHERE userID = '$buid'");
                                                            if ($uresult->num_rows > 0) {
                                                                $user_data = $uresult->fetch_assoc(); ?>
                                                                <li class="list-group-item">
                                                                    <p>Name: <?php echo $user_data['name']; ?></p>
                                                                    <p>Mobile: <?php echo $user_data['phone']; ?></p>
                                                                    <p>Address: <?php echo $user_data['address']; ?></p>
                                                                    <p>Date: <?php echo date('F j, Y', strtotime($book['reqDate'])); ?></p>
                                                                    <p><a href="flats.php?id=<?php echo $flat_ID; ?>&action=booked&user=<?php echo $buid; ?>" class="btn btn-secondary mt-1 mb-1">Booked</a></p>
                                                                </li>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
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
        <script type="text/javascript" src="../js/script.js?var=<?php echo time(); ?>"></script>
    </body>
</html>
<?php } ?>