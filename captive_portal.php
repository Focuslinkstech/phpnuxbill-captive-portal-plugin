<?php

register_menu("Captive Portal", true, "captive_portal_overview", 'AFTER_SETTINGS', 'ion ion-earth');


function captive_portal_overview()
{
  global $ui, $routes;
  _admin();
  $ui->assign('_title', Lang::T('Captive Portal Dashboard'));
  $ui->assign('_system_menu', '');
  $admin = Admin::_info();
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }


  $ui->display('captive_portal_overview.tpl');
}


function captive_portal_slider()
{
  global $ui, $routes;
  _admin();
  $ui->assign('_title', Lang::T('Captive Portal Sliders'));
  $ui->assign('_system_menu', '');
  $admin = Admin::_info();
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }

  // Read JSON data from file
  $jsonFile = 'system/plugin/captive_portal/slider.json';
  $jsonData = file_get_contents($jsonFile);
  $data = json_decode($jsonData, true);

  // Check if the form is submitted
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data and process it
    $newSlide = [
      "title" => $_POST['title'],
      "description" => $_POST['description'],
      "link" => $_POST['link'],
      "button" => $_POST['button']
    ];

    // Upload image
    $targetDirectory = 'system/plugin/captive_portal/sliders/';
    $timestamp = time();
    $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imageFilename = $timestamp . '.' . $imageExtension;
    $targetFile = $targetDirectory . $imageFilename;
    // Move uploaded image to target directory
    move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);

    // Check if image creation was successful

    // Open the uploaded image
    $image = imagecreatefromjpeg($targetFile);

    if ($image !== false) {
      // Get image dimensions
      $imageWidth = imagesx($image);
      $imageHeight = imagesy($image);


      // Get image dimensions
      $imageWidth = imagesx($image);
      $imageHeight = imagesy($image);

      // Calculate the aspect ratio
      $aspectRatio = $imageWidth / $imageHeight;

      // Define the maximum width and height of the resized image
      $maxWidth = 2200; // Adjust the desired maximum width
      $maxHeight = 900; // Adjust the desired maximum height

      // Calculate the new dimensions while maintaining the aspect ratio
      if ($imageWidth > $maxWidth || $imageHeight > $maxHeight) {
        if ($maxWidth / $maxHeight > $aspectRatio) {
          $newWidth = $maxHeight * $aspectRatio;
          $newHeight = $maxHeight;
        } else {
          $newWidth = $maxWidth;
          $newHeight = $maxWidth / $aspectRatio;
        }
      } else {
        $newWidth = $imageWidth;
        $newHeight = $imageHeight;
      }

      // Create a new image with the resized dimensions
      $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

      // Perform the resize
      imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);

      // Save the resized image
      imagejpeg($resizedImage, $targetFile);

      // Generate thumbnail
      $thumbnailDirectory = 'system/plugin/captive_portal/sliders/thumbnails/';
      $thumbnailWidth = 80; // Adjust the desired thumbnail width
      $thumbnailHeight = 40; // Adjust the desired thumbnail height
      $thumbnailFilename = $timestamp . '_thumbnail.' . $imageExtension;
      $thumbnailFile = $thumbnailDirectory . $thumbnailFilename;

      // Create a new image with the desired thumbnail size
      $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

      // Resize the resized image to the thumbnail size
      imagecopyresampled($thumbnailImage, $resizedImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $newWidth, $newHeight);

      // Save the thumbnail
      imagejpeg($thumbnailImage, $thumbnailFile);

      // Add the image and thumbnail paths to the new slide data
      $newSlide['image'] = $targetFile;
      $newSlide['thumbnail'] = $thumbnailFile;

      // Add the new slider to the data array
      $data[] = $newSlide;

      // Write JSON data back to file
      $jsonData = json_encode($data, JSON_PRETTY_PRINT);
      file_put_contents($jsonFile, $jsonData);

      r2(U . "plugin/captive_portal_slider", 's', Lang::T("Slider Added Successfully"));
    } else {
      // Handle the case when image creation fails
      r2(U . "plugin/captive_portal_slider", 'e', Lang::T("Failed to create the image"));
    }
  }



  // Assign slider data to the template variable only if the data exists
  if (!empty($data)) {
    $ui->assign('slides', $data);
  }

  $ui->display('captive_portal_slider.tpl');
}


// Edit page for a specific slider
function captive_portal_slider_edit()
{
  global $ui, $routes;
  _admin();
  $admin = Admin::_info();
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }
  // Read JSON data from file
  $jsonFile = 'system/plugin/captive_portal/slider.json';
  $jsonData = file_get_contents($jsonFile);
  $data = json_decode($jsonData, true);

  // Retrieve the slider index or identifier from the request
  $slideIndex = $_GET['slideIndex']; // Modify this based on your URL structure

  // Check if the slider index is valid
  if (!isset($data[$slideIndex])) {
    r2(U . "plugin/captive_portal_slider", 'e', Lang::T("Invalid Slide Index"));
  }

  // Retrieve the existing slider details
  $existingSlide = $data[$slideIndex];

  // Check if the form is submitted
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data and process it
    $editedSlide = [
      "title" => $_POST['title'],
      "description" => $_POST['description'],
      "link" => $_POST['link'],
      "button" => $_POST['button'],
      "image" => $existingSlide['image'], // Preserve the existing image path
      "thumbnail" => $existingSlide['thumbnail'] // Preserve the existing thumbnail path
    ];

    // Update the specific slider with the edited data
    $data[$slideIndex] = $editedSlide;

    // Write JSON data back to file
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($jsonFile, $jsonData);

    r2(U . "plugin/captive_portal_slider", 's', Lang::T("Slider Updated Successfully"));
  }

  // Assign the existing slider details to the template variable
  $ui->assign('slide', $existingSlide);
  $ui->assign('slideIndex', $slideIndex);

  $ui->display('captive_portal_slider.tpl');
}


// Delete a specific slider
function captive_portal_slider_delete()
{
  global $ui, $routes;
  _admin();
  $admin = Admin::_info();
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }

  // Read JSON data from file
  $jsonFile = 'system/plugin/captive_portal/slider.json';
  $jsonData = file_get_contents($jsonFile);
  $data = json_decode($jsonData, true);

  // Retrieve the slider index or identifier from the request
  $slideIndex = $_GET['slideIndex']; // Modify this based on your URL structure

  // Check if the slide index is valid
  if (!isset($data[$slideIndex])) {
    r2(U . "plugin/captive_portal_slider", 'e', Lang::T("Invalid Slider Index"));
  }

  // Retrieve the slider to be deleted
  $slideToDelete = $data[$slideIndex];

  // Remove the slider from the array
  unset($data[$slideIndex]);

  // Delete the associated image files
  $imagePath = $slideToDelete['image'];
  $thumbnailPath = $slideToDelete['thumbnail'];

  // Delete the image file
  if (file_exists($imagePath)) {
    unlink($imagePath);
  }

  // Delete the thumbnail file
  if (file_exists($thumbnailPath)) {
    unlink($thumbnailPath);
  }

  // Write JSON data back to file
  $jsonData = json_encode($data, JSON_PRETTY_PRINT);
  file_put_contents($jsonFile, $jsonData);

  r2(U . "plugin/captive_portal_slider", 's', Lang::T("Slider Deleted Successfully"));
}

function captive_portal_login()
{
  global $ui;

  /* Iterate through $_POST array and echo key-value pairs
    //foreach ($_POST as $key => $value) {
      //  echo $key . ': ' . $value . '<br>';
    }*/
  $mac = $_POST['mac'];
  $ip = $_POST['ip'];
  $username = $_POST['username'];
  $linklogin = $_POST['link-login'];
  $linkorig = $_POST['link-orig'];
  $error = $_POST['error'];
  $trial = $_POST['trial'];
  $blocked = $_POST['blocked'];
  $loginby = $_POST['login-by'];
  $chapid = $_POST['chap-id'];
  $chapchallenge = $_POST['chap-challenge'];
  $linkloginonly = $_POST['link-login-only'];
  $linkorigesc = $_POST['link-orig-esc'];
  $macesc = $_POST['mac-esc'];
  $identity = $_POST['identity'];
  $bytesinnice = $_POST['bytes-in-nice'];
  $bytesoutnice = $_POST['bytes-out-nice'];
  $sessiontimeleft = $_POST['session-time-left'];
  $uptime = $_POST['uptime'];
  $refreshtimeout = $_POST['refresh-timeout'];
  $refreshtimeoutsecs = $_POST['refresh-timeout-secs'];
  $linkstatus = $_POST['link-status'];
  $linkadvert = $_POST['link-advert'];

  // Read the JSON file
  $jsonFile = 'system/plugin/captive_portal/slider.json';
  $jsonData = file_get_contents($jsonFile);
  // Parse the JSON data
  $slides = json_decode($jsonData, true);

  $configFile = 'system/plugin/captive_portal/config.json';
  $configData = file_get_contents($configFile);
  $config = json_decode($configData, true);

  // Assign the data to Smarty variables
  $ui->assign('slides', $slides);
  $ui->assign('mac', $mac);
  $ui->assign('ip', $ip);
  $ui->assign('username', $username);
  $ui->assign('linklogin', $linklogin);
  $ui->assign('linkorig', $linkorig);
  $ui->assign('error', $error);
  $ui->assign('trial', $trial);
  $ui->assign('blocked', $blocked);
  $ui->assign('chapid', $chapid);
  $ui->assign('loginby', $loginby);
  $ui->assign('chapchallenge', $chapchallenge);
  $ui->assign('linkloginonly', $linkloginonly);
  $ui->assign('linkorigesc', $linkorigesc);
  $ui->assign('macesc', $macesc);
  $ui->assign('identity', $identity);
  $ui->assign('bytesinnice', $bytesinnice);
  $ui->assign('bytesoutnice', $bytesoutnice);
  $ui->assign('sessiontimeleft', $sessiontimeleft);
  $ui->assign('uptime', $uptime);
  $ui->assign('refreshtimeout', $refreshtimeout);
  $ui->assign('refreshtimeoutsecs', $refreshtimeoutsecs);
  $ui->assign('linkstatus', $linkstatus);
  $ui->assign('linkadvert', $linkadvert);
  $ui->assign('config', $config);

  $ui->display('captive_portal_login.tpl');
}

function captive_portal_settings()
{
  global $ui, $routes;
  _admin();
  $admin = Admin::_info();
  $ui->assign('_title', 'Captive Portal General Settings');
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }

  // Load the existing settings from the JSON file
  $configFile = 'system/plugin/captive_portal/config.json';
  $defaultConfig = array(
    'hotspot_title' => '',
    'hotspot_name' => '',
    'favicon' => '',
    'logo' => ''
  );
  $settings = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form values and update the settings
    $hotspot_title = $_POST['title'];
    $hotspot_name = $_POST['name'];
    $hotspot_trial = $_POST['trial'];
    $hotspot_member = $_POST['member'];

    // Process the logo image
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
      $logo_tmp = $_FILES['logo']['tmp_name'];
      $logo_name = $_FILES['logo']['name'];
      $logo_ext = pathinfo($logo_name, PATHINFO_EXTENSION);
      $logo_destination = 'system/plugin/captive_portal/img/logo.' . $logo_ext;
      move_uploaded_file($logo_tmp, $logo_destination);
      $settings['logo'] = $logo_destination;
    }

    // Process the favicon image
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
      $favicon_tmp = $_FILES['favicon']['tmp_name'];
      $favicon_name = $_FILES['favicon']['name'];
      $favicon_ext = pathinfo($favicon_name, PATHINFO_EXTENSION);
      $favicon_destination = 'system/plugin/captive_portal/img/favicon.' . $favicon_ext;
      move_uploaded_file($favicon_tmp, $favicon_destination);
      $settings['favicon'] = $favicon_destination;
    }

    // Update the title and name in the settings array
    $settings['hotspot_title'] = $hotspot_title;
    $settings['hotspot_name'] = $hotspot_name;
    $settings['hotspot_trial'] = $hotspot_trial;
    $settings['hotspot_member'] = $hotspot_member;

    // Save the updated settings to the JSON file
    file_put_contents($configFile, json_encode($settings));

    // Redirect or display a success message
    r2(U . "plugin/captive_portal_settings", 's', Lang::T("Settings Saved"));
  }

  // Pass the settings to the template
  $ui->assign('settings', $settings);

  $ui->display('captive_portal_settings.tpl');
}

function captive_portal_download_login()
{
  global $ui, $routes;
  _admin();
  $admin = Admin::_info();
  $ui->assign('_title', Lang::T('Captive Portal General Settings'));
  $ui->assign('_admin', $admin);
  $action = $routes['1'];

  if ($admin['user_type'] != 'SuperAdmin' && $admin['user_type'] != 'Admin' && $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', Lang::T("You Do Not Have Access"));
  }
  $configFile = 'system/plugin/captive_portal/config.json';
  $configData = file_get_contents($configFile);
  $config = json_decode($configData, true);
  $hotspotTitle = $config['hotspot_title'];
  $hotspotName = $config['hotspot_name'];
  $loginUrl = U . "plugin/captive_portal_login";




  $content = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <title>$hotspotTitle - redirecting..</title>
  <style type="text/css" media="screen">
.ui.button,.ui.menu .item{vertical-align:middle;line-height:1;text-decoration:none;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;-ms-box-sizing:border-box}.ui.button,.ui.center.aligned.segment{text-align:center}.ui.button{cursor:pointer;display:inline-block;min-height:1em;outline:0;border:0;background-color:#FAFAFA;color:gray;margin:0;padding:.8em 1.5em;font-size:1rem;text-transform:uppercase;font-weight:700;font-style:normal;background-image:-webkit-gradient(linear,top left,bottom left,from(rgba(0,0,0,0)),to(rgba(0,0,0,.05)));background-image:-webkit-linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.05));background-image:linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.05));border-radius:0;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.08) inset;box-shadow:0 0 0 1px rgba(0,0,0,.08) inset;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;box-sizing:border-box;-webkit-tap-highlight-color:transparent;-webkit-transition:opacity .25s ease,background-color .25s ease,color .25s ease,background .25s ease,-webkit-box-shadow .25s ease;transition:opacity .25s ease,background-color .25s ease,color .25s ease,background .25s ease,box-shadow .25s ease}.ui.button:hover{background-image:-webkit-gradient(linear,top left,bottom left,from(rgba(0,0,0,0)),to(rgba(0,0,0,.08)));background-image:-webkit-linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.08));background-image:linear-gradient(rgba(0,0,0,0),rgba(0,0,0,.08));color:rgba(0,0,0,.7)}.ui.button:active{background-color:#F1F1F1;color:rgba(0,0,0,.7);-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.05) inset!important;box-shadow:0 0 0 1px rgba(0,0,0,.05) inset!important}.ui.green.button{background-color:#5BBD72;color:#FFF}.ui.green.button:hover{background-color:#58cb73;color:#FFF}.ui.green.button:active{background-color:#4CB164;color:#FFF}.ui.menu,.ui.segment{background-color:#FFF}.ui.header{border:0;margin:1em 0 1rem;padding:0;font-size:1.33em;font-weight:700;line-height:1.33}.ui.header:first-child{margin-top:0}.ui.header:last-child{margin-bottom:0}h1.ui.header{min-height:1rem;line-height:1.33;font-size:2rem}.ui.dividing.header{padding-bottom:.2rem;border-bottom:1px solid rgba(0,0,0,.1)}.ui.loader{display:none;position:absolute;top:50%;left:50%;margin:0;z-index:1000;-webkit-transform:translateX(-50%) translateY(-50%);-ms-transform:translateX(-50%) translateY(-50%);transform:translateX(-50%) translateY(-50%)}.ui.loader.active{display:block}.ui.loader.large{width:64px;height:64px;background-image:url(data:image/gif;base64,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)}.ui.inline.loader{position:static;vertical-align:middle;margin:0;-webkit-transform:none;-ms-transform:none;transform:none}.ui.inline.loader.active{display:inline-block}.ui.menu{margin:1rem 0;font-size:0;font-weight:400;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.1);box-shadow:0 0 0 1px rgba(0,0,0,.1);border-radius:.1875rem}.ui.menu:first-child{margin-top:0}.ui.menu:after{content:".";display:block;height:0;clear:both;visibility:hidden}.ui.menu .item{box-sizing:border-box;-webkit-transition:opacity .2s ease,background .2s ease,-webkit-box-shadow .2s ease;transition:opacity .2s ease,background .2s ease,box-shadow .2s ease;color:rgba(0,0,0,.75);position:relative;display:inline-block;padding:.83em .95em;border-top:0 solid transparent;-webkit-tap-highlight-color:transparent;-moz-user-select:-moz-none;-khtml-user-select:none;-webkit-user-select:none;-ms-user-select:none;user-select:none;font-size:1rem}.ui.menu .menu{margin:0}.ui.menu .menu.right{float:right}.ui.menu .item:before{position:absolute;content:'';top:0;right:0;width:1px;height:100%;background-image:-webkit-gradient(linear,top left,bottom left,from(rgba(0,0,0,.05)),color-stop(50%,rgba(0,0,0,.1)),to(rgba(0,0,0,.05)));background-image:-webkit-linear-gradient(rgba(0,0,0,.05) 0,rgba(0,0,0,.1) 50%,rgba(0,0,0,.05) 100%);background-image:linear-gradient(rgba(0,0,0,.05) 0,rgba(0,0,0,.1) 50%,rgba(0,0,0,.05) 100%)}.ui.menu .item>img:only-child{display:block;max-width:100%;margin:0 auto}.ui.menu.fixed{position:fixed;z-index:10;margin:0;border:0;width:100%}.ui.menu.fixed,.ui.menu.fixed .item:first-child{border-radius:0!important}.ui.segment{position:relative;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.1);box-shadow:0 0 0 1px rgba(0,0,0,.1);margin:1em 0;padding:1em;border-radius:0;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;-ms-box-sizing:border-box;box-sizing:border-box}.ui.segment:first-child,.ui.segment>:first-child{margin-top:0}.ui.segment:last-child,.ui.segment>:last-child{margin-bottom:0}.ui.segment:after{content:'';display:block;height:0;clear:both;visibility:hidden}body,html{font-size:15px;height:100%}body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;background-color:#ffffff;margin:0;padding:0;color:#323a2d;text-rendering:optimizeLegibility;min-width:320px}.container{width:auto;margin-right:100px;margin-left:100px}.main.menu .container{margin-right:0!important}.karmalogo{padding-top:7px!important;padding-bottom:7px!important}.karmalogo img{height:26px}::-webkit-selection{background-color:#FFC;color:#555}::-moz-selection{background-color:#FFC;color:#555}::selection{background-color:#FFC;color:#555}h1::-moz-selection,h2::-moz-selection,h3::-moz-selection{background-color:#F1C1C2;color:#222}h1::selection,h3::selection{background-color:#F1C1C2;color:#222}.ui ::-moz-selection{background-color:#CCE2FF}.ui ::selection{background-color:#CCE2FF}@media only screen and (max-width:1024px){.container{margin-right:3em;margin-left:3em}}@media only screen and (max-width:770px){body{overflow:hidden;overflow-y:auto}.container{margin-right:.3em;margin-left:.3em}h1.header{font-size:1.2rem}}@media only screen and (min-width:1725){.container{width:1200px;margin:0 auto}}body>.segment{margin:0;padding-top:70px;padding-bottom:30px}
  </style>
  <script type="text/javascript">
    // JavaScript code here
  </script>
</head>
<body id="login">
\$(if chap-id)
<noscript>
  <center><b>JavaScript required. Enable JavaScript to continue.</b></center>
</noscript>
\$(endif)

<div class="segment">
  <div class="container symetric">
    <div class="introduction">
      <h1 class="ui dividing header">
        <font color="red"> $hotspotName </font>
      </h1>
    </div>
  </div>
</div>

<div class="main container">
  <div class="ui center aligned segment">
    <h3>
      You will be redirected Shortly...
    </h3>

    <div class="ui active inline large loader" id="loader"></div>

    <form name="redirect" action="$loginUrl" method="post">
    <input type="hidden" name="mac" value="$(mac)">
    <input type="hidden" name="ip" value="$(ip)">
    <input type="hidden" name="username" value="$(username)">
    <input type="hidden" name="link-login" value="$(link-login)">
    <input type="hidden" name="link-orig" value="$(link-orig)">
    <input type="hidden" name="error" value="$(error)">
    <input type="hidden" name="trial" value="$(trial)">
    <input type="hidden" name="chap-id" value="$(chap-id)">
    <input type="hidden" name="chap-challenge" value="$(chap-challenge)">
    <input type="hidden" name="link-login-only" value="$(link-login-only)">
    <input type="hidden" name="link-orig-esc" value="$(link-orig-esc)">
    <input type="hidden" name="mac-esc" value="$(mac-esc)">
    <input type="hidden" name="identity" value="$(identity)">
    <input type="hidden" name="bytes-in-nice" value="$(bytes-in-nice)">
    <input type="hidden" name="bytes-out-nice" value="$(bytes-out-nice)">
    <input type="hidden" name="session-time-left" value="$(session-time-left)">
    <input type="hidden" name="uptime" value="$(uptime)">
    <input type="hidden" name="refresh-timeout" value="$(refresh-timeout)">

        <div id="manual_login">
          <p>If this does not happen automatically, click</p>

          <input type="submit" class="ui green button" value="Continue" id="login_button">
        </div>

        <div id="manual_login_captive" style="display: none">
          <p>If this does not happen automatically, click "<strong>Done</strong>" and open Safari</p>
        </div>
      </form>
  </div>
</div>

<script language="JavaScript">
  document.redirect.submit();
</script>

</body>
</html>
HTML;

  header("Content-Disposition: attachment; filename=\"login.html\"");
  header("Content-Type: application/force-download");
  echo $content;
}
