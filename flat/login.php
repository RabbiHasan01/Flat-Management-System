<?php 

require 'config.php';

function getBrowser() { 
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";
  
    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
      $platform = 'linux';
    }elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
      $platform = 'mac';
    }elseif (preg_match('/windows|win32/i', $u_agent)) {
      $platform = 'windows';
    }
  
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)){
      $bname = 'Internet Explorer';
      $ub = "MSIE";
    }elseif(preg_match('/Firefox/i',$u_agent)){
      $bname = 'Mozilla Firefox';
      $ub = "Firefox";
    }elseif(preg_match('/OPR/i',$u_agent)){
      $bname = 'Opera';
      $ub = "Opera";
    }elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
      $bname = 'Google Chrome';
      $ub = "Chrome";
    }elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
      $bname = 'Apple Safari';
      $ub = "Safari";
    }elseif(preg_match('/Netscape/i',$u_agent)){
      $bname = 'Netscape';
      $ub = "Netscape";
    }elseif(preg_match('/Edge/i',$u_agent)){
      $bname = 'Edge';
      $ub = "Edge";
    }elseif(preg_match('/Trident/i',$u_agent)){
      $bname = 'Internet Explorer';
      $ub = "MSIE";
    }
  
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
  ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
      // we have no matching number just continue
    }
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
      //we will have two since we are not using 'other' argument yet
      //see if version is before or after the name
      if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
          $version= $matches['version'][0];
      }else {
          $version= $matches['version'][1];
      }
    }else {
      $version= $matches['version'][0];
    }
  
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
  
    return array(
      'userAgent' => $u_agent,
      'name'      => $bname,
      'version'   => $version,
      'platform'  => $platform,
      'pattern'    => $pattern
    );
} 

$username = $password = "";
$error = [];

if ( isset($_POST['login']) ) {
    if ( isset($_POST['username']) && !empty($_POST['username']) ) {
        $username = $conn->real_escape_string($_POST['username']);
    }

    if ( isset($_POST['password']) && !empty($_POST['password']) ) {
        $password = md5($_POST['password']);
    }

    if ( !empty($username) && !empty($password) ) {
        $result = $conn->query("SELECT * FROM user WHERE username='$username' AND password='$password'");
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ( isset($row['userID'], $row['username'], $row['password']) ) {
                $userID = $row['userID'];
                $date = date('Y-m-d H:i:s');
                $browserinfo = getBrowser();
                $ip = $_SERVER['REMOTE_ADDR'];
                $os = $browserinfo['platform'];
                $browser = $browserinfo['name'];
                $browserversin = $browserinfo['version'];
                $securityKey = md5($userID . '_' . $ip . '_' . $os . '_' . $browser, false);

                $sql = "INSERT INTO loginfo (logID, userID, ipAddress, os, browser, date, securityKey) SELECT NULL, '$userID', '$ip', '$os', '$browser', '$date', '$securityKey' FROM DUAL WHERE NOT EXISTS (SELECT * FROM loginfo WHERE userID = '$userID' AND securityKey = '$securityKey' LIMIT 1)";

                if ($conn->query($sql)) {
                    $logID = $conn->insert_id;
                    if ( $logID < 1 ) {
                        $sqln = $conn->query("SELECT * FROM loginfo WHERE userID = '$userID' AND securityKey = '$securityKey' LIMIT 1");
                        if ($sqln->num_rows > 0) {
                            $log_data = $sqln->fetch_assoc();
                            setcookie('logID', $log_data['logID'], strtotime("+1 year"));
                        }
                    } else {
                        setcookie('logID', $logID, strtotime("+1 year"));
                    }

                    setcookie('logKey', $securityKey, strtotime("+1 year"));
                    header("Location: dashboard/index.php");
                    exit();
                } else {
                    $error[] = "There was an error to loggen in";
                }
            }
        } else {
            $error[] = 'Invalid username or password given';
        }
    } else {
        $error[] = 'Enter your username & password';
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
                <h1 class="text-center">Login</h1>
                
                <?php if( is_array($error) && !empty($error) ) {
                    foreach($error as $err) {
                        echo '<div class="alert alert-danger" role="alert">' . $err . '</div>';
                    }
                } ?>
                <form action="" method="POST" class="row g-3">
                    <h4>Welcome Back</h4>
                    <div class="col-12">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $username; ?>">
                    </div>
                    <div class="col-12">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Password">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="login" class="btn btn-dark float-end">Login</button>
                    </div>
                    <script>if ( window.history.replaceState ) { window.history.replaceState( null, null, window.location.href ); }</script>
                </form>
            </main>
        </div>
    </body>
</html>