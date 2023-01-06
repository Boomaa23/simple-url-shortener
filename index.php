<?php 
include 'env.php'; 
function doAuth() {
  if (!isset($_SERVER['PHP_AUTH_PW']) || !isset($_SERVER['PHP_AUTH_USER']) ||
      $_SERVER['PHP_AUTH_PW'] !== $PASSWORD ||
      $_SERVER['PHP_AUTH_USER'] !== $USERNAME) {
    header('WWW-Authenticate: Basic realm="Secure Site"');
    header('HTTP/1.0 401 Unauthorized');
    die('This site requires authentication');
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Link Shortener</title>
  <style>
    input { padding: 5px; margin: 5px; }
    label { font-size: 14px; }
    .footer {position: fixed; text-align: center; bottom: 0px; width: 100%; background-color:white;font-size:10px;}
    .center { text-align: center; }
    #title { font-weight: bold; font-size: 22px; }
    .nostyle { text-decoration:none; color: black; }
    body { font-family: sans-serif; animation: 2s popIn; }
    #remove, #track { margin: 10px 0 10px 10px; }
    <?php
    if (isset($_GET["dest"])) {
      echo '
      @keyframes popIn {
      0% { opacity: 0; }
      99% { opacity: 0; }
      100% { opacity: 1; }
      }';
    }
    ?>
  </style>
</head>
<body>
<div class="center" id="main">
  <div id="title">Link Shortener</div>
  <?php
  $linksJson = json_decode(file_get_contents("links.json"), true);
  $clicksJson = json_decode(file_get_contents("clicks.json"), true);

  if (isset($_POST["link"])) {
    doAuth();
    if (isset($_POST["remove"])) {
      $fn = $_POST["custom"];
      unset($linksJson[$fn]);
      unset($clicksJson[$fn]);
      $urlFn = $ROOT_URL . $fn;

      echo 'Removed Shortened Link: <a href="' . $urlFn . '">' . $urlFn . '</a><br /><br />';
      echo '<button><a href="' . $ROOT_URL . '" class="nostyle">Shorten Another</a></button>';
    } else {
      $length = 6; //length of final shortened random url
      $randChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
      $rand = substr(str_shuffle(str_repeat($randChars, ceil($length / strlen($randChars)))), 1, $length);

      if (isset($_POST["custom"]) && trim($_POST["custom"]) !== "") {
        $rand = $_POST["custom"];
      }

      $url = $_POST["link"];
      $linksJson[$rand] = $url;

      if (isset($_POST["track"])) {
        $clicksJson[$rand] = array();
      }

      $shortUrl = $ROOT_URL . $rand;

      echo 'Shortened Link: <a href="' . $shortUrl . '">' . $shortUrl . '</a><br /><br />';
      echo '<button><a href="' . $ROOT_URL . '" class="nostyle">Shorten Another</a></button>';
    }
    file_put_contents("links.json", json_encode($linksJson, JSON_PRETTY_PRINT));
    file_put_contents("clicks.json", json_encode($clicksJson, JSON_PRETTY_PRINT));
  } else if (isset($_GET["dest"])) {
    $url;
    if (array_key_exists($_GET["dest"], $linksJson)) {
      $url = $linksJson[$_GET["dest"]];
    } else if (empty($_GET["dest"])) {
      $url = $ROOT_URL;
    }

    if (isset($url)) {
      $ip = 'IP';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    if (!empty($_GET["dest"]) && array_key_exists($_GET["dest"], $clicksJson) && !in_array($ip, $IGNORE_IPS)) {
      $clicksJson[$_GET["dest"]][date('c', time())] = $ip;
      file_put_contents("clicks.json", json_encode($clicksJson, JSON_PRETTY_PRINT));
    }

    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=\'' . $url . '\'" /></head><body>' .
      '<p>Please follow this link: <a href="' . $url . '">' . $url . '</a></p></body></html>';
    } else {
      echo 'Could not find a URL redirect for "' . $_GET["dest"] . '"<br /><br />';
      echo '<button><a href="' . $ROOT_URL . '" class="nostyle">Shorten a Link</a></button>';
    }
  } else {
    doAuth();
    echo '
    <form method="post">
    <input type="text" name="link" placeholder="Link" /><br />
    <input type="text" name="custom" placeholder="Custom URL" /><br />
    <input type="checkbox" name="remove" id="remove"/>
    <label for="remove">Remove</label>

    <input type="checkbox" name="track" id="track" checked="checked" />
    <label for="track">Track</label><br />
    <input type="submit" value="       Shorten       " />

    </form>';
  }
  ?>
</div>
</body>

<div class="footer">Copyright &copy; 2020-2023 Boomaa23. All Rights Reserved.</div>

<script type="text/javascript">
  function center() {
    document.getElementById("main").style.paddingTop = (document.documentElement.clientHeight * 0.35) + "px";
    setTimeout(center, 1000);
  }
  center();
</script>

</html>

