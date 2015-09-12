<?php

include_once ("fckeditor/fckeditor.php");
require ("phpMailer_v2.3/class.phpmailer.php");

set_include_path(get_include_path() . PATH_SEPARATOR . '/home/oehirlevel/domains/oehirlevel.cserkesz.hu/public_html/googleapi');

require_once 'src/Google/autoload.php';

include_once ("account.php");

require_once('smarty-3.1.27/libs/Smarty.class.php');

class HirlevelPage
{
  private $mysqli = null;
  private $messages = array();

  function __construct($username, $password)
  {
    $this->SetupDatabaseConnection($username, $password);
  }

  function SetupDatabaseConnection($username, $password)
  {
    $this->mysqli = new mysqli("localhost", $username, $password, "oehirlevel_hir");
    $this->mysqli->set_charset('utf8');
  }

  function Query($query)
  {
    return $this->mysqli->query($query);
  }

  function Escape($text)
  {
    return $this->mysqli->real_escape_string($text);
  }

  function QsToArray($qs)
  {
    $sql = $qs;

    $result = $this->mysqli->query($sql);

    $array = array();

    if (!$result)
    {
      // debug info: $this->mysqli->error()
      $this->AddMessage("Hiba történt!");
      return $array;
    }

    while ($row = $result->fetch_assoc())
    {
      foreach ($row as $key => $val)
      {
        $row[$key] = stripslashes($val);
      }

      array_push($array, $row);
    }

    $result->free();

    return $array;
  }

  function ArrayToTable($arr, $header = "", $class = "", $rowid = "", $cols = "")
  {
    $result = "";
    $result .= "<table";
    if ($class != "")
    {
      $result .= " class='" . $class . "' ";
    }

    $result .= ">\n";

    if (is_array($header))
    {
      $result .= "<thead>\n<tr>\n";
      foreach ($header as $key => $value)
      {
        $result .= "<th id='" . $cols[$key] . "'>" . $value . "</th>\n";
      }
      $result .= "</thead>\n</tr>\n";

    }

    foreach ($arr as $key => $value)
    {
      $row = $value;

      $result .= "<tr";
      if ($rowid != "")
      {
        $result .= " id='" . $row[$rowid] . "'";
      }

      $result .= ">\n";

      if (is_array($cols))
      {
        foreach ($cols as $key => $value)
        {
          $result .= "<td>" . $row[$value] . "</td>\n";
        }
      }
      else
      {
        foreach ($row as $key => $value)
        {
          $result .= "<td>" . $value . "</td>\n";
        }
      }

      $result .= "</tr>\n";
    }

    $result .= "</table>\n";

    return $result;
  }

  function CreateSelect($name, $arr, $valueComun, $displayColumn, $selectedValue = "")
  {
    $result = "";
    $result .= "<select name='" . $name . "'>";

    foreach ($arr as $key => $value)
    {
      $result .= "<option value=" . $value[$valueComun];

      if ($selectedValue == $value[$valueComun] || (isset($_POST[$name]) && $_POST[$name] == $value[$valueComun]))
      {
        $result .= ' selected="selected"';
      }

      $result .= ">" . $value[$displayColumn] . "</option>";
    }

    $result .= "</select>";

    return $result;
  }

  function TranslateMonth($month)
  {
    switch ($month)
    {
      case 1:
        return "január";
      case 2:
        return "február";
      case 3:
        return "március";
      case 4:
        return "április";
      case 5:
        return "május";
      case 6:
        return "június";
      case 7:
        return "július";
      case 8:
        return "augusztus";
      case 9:
        return "szeptember";
      case 10:
        return "október";
      case 11:
        return "novelmber";
      case 12:
        return "december";
    }
  }

  function GetToday()
  {
    return date("Y") . ". " . $this->TranslateMonth(date("n")) . " " . date("j") . ".";
  }

  function GetTipusok()
  {
      $tipusok = array();
      
      $item = array();
      $item["id"] = 0;
      $item["nev"] = "semmi";
      array_push($tipusok, $item);
      
      $item = array();
      $item["id"] = 1;
      $item["nev"] = "közeli határidő";
      array_push($tipusok, $item);
      
      $item = array();
      $item["id"] = 2;
      $item["nev"] = "továbbításra ajánljuk";
      array_push($tipusok, $item);
      
      $item = array();
      $item["id"] = 3;
      $item["nev"] = "választ várunk";
      array_push($tipusok, $item);

      $item = array();
      $item["id"] = 4;
      $item["nev"] = "kiemelten fontos szövetségi hír";
      array_push($tipusok, $item);

      return $tipusok;    
  }

  function AddMessage($message)
  {
      array_push($this->messages, $message);
  }

  function IsAdmin()
  {
    return isset($_SESSION["user"]) && ($_SESSION["role_id"] == 1);
  }

  function GetActualHirlevel()
  {
    $hirlevelek = $this->QsToArray("SELECT * FROM hir_hirlevel ORDER BY hirlevel_id DESC LIMIT 1");
    $hirlevel = $hirlevelek[0];
    return $hirlevel;
  }

  function GetImageTag($hirTipus, $top)
  {
      $imageTag = "";

      if ($hirTipus != "" && $hirTipus > 0)
      {
        $url = "http://oehirlevel.cserkesz.hu/";
        if ($hirTipus == 1) 
        {
          $url .= "/images/deadline.png";
        } 
        else if ($hirTipus == 2)
        {
          $url .= "/images/share.png";
        } else if ($hirTipus == 3)
        {
          $url .= "/images/respond.png";
        } else if ($hirTipus == 4)
        {
          $url .= "/images/important.png";
        }

        $tipusok = $this->GetTipusok();
        $szoveg = "";
        foreach ($tipusok as $keyt => $valuet)
        {
           if ($valuet["id"] == $hirTipus) 
           {
             $szoveg = $valuet["nev"];
           } 
        }
          
        $imageTag = '<img src="'. $url . '" style="height: 17px; top: ' . $top . 'px; position: relative;" title="' . $szoveg  . '"/>';
      }
    
    return $imageTag;
  }

  function ListazCikkek($mode)
  {

    $result = "";
    $result .= "<div style='background-color:white; padding:10px;'>";
    $result .= "<div style='background:url(http://cserkesz.hu/~kovi/oehirlevel/bg.png); background-repeat:repeat-y; background-color:#c3d4c0;'>";

    $hirlevel = $this->GetActualHirlevel();

    $qs = "SELECT * FROM hir_kategoria 
JOIN hir_hir ON hir_kat_id = kategoria_id 
WHERE hir_hirlev_id = " . $hirlevel["hirlevel_id"] . "
GROUP BY kategoria_id 
ORDER BY kategoria_id";

    $kategoriak = $this->QsToArray($qs);
    $result .= "<style>.link { color:#d87705 /*#ea8106*/; }</style>\n";

    if ($mode == "preview")
    {

      $result .= "<img src='http://oehirlevel.cserkesz.hu/hirlevel_focim_liliom_oe.png'>";
      $result .= "<div style='padding:10px;'>";
      $result .= "<div style='padding-bottom:20px;'></div>";
      $result .= "<a name='top'></a>";
      $result .= "<strong>" . $hirlevel["hirlevel_nev"] . ", " . $this->GetToday() . "</strong>";
      $result .= "<div class='tartalom' style='padding-bottom:20px;'>";
      
      $result .= "<ul>";
      foreach ($kategoriak as $key => $value)
      {
        $result .= "<li><a href=#kat" . $value["kategoria_id"] . " class='link'>" . $value["kategoria_szoveg"] . "</a><br />";
        $hirek = $this->QsToArray("SELECT * FROM hir_hir WHERE hir_kat_id=" . $value["kategoria_id"] . " AND hir_hirlev_id=" . $hirlevel["hirlevel_id"]);

        $result .= "<ul>";

        foreach ($hirek as $key => $value)
        {
          $imageTag = $this->GetImageTag($value["hir_tipus"], 4);
          $result .= "<li><a href=#hir" . $value["hir_id"] . " class='link'>" . $value["hir_cim"] . "</a>&nbsp;" . $imageTag . "</li>";
        }

        $result .= "</ul></li>";
      }
      $result .= "</ul>";

      $result .= "</div>";
    }

    foreach ($kategoriak as $key => $value)
    {
      $result .= "<h2><a name='kat" . $value["kategoria_id"] . "'>" . $value["kategoria_szoveg"] . "</a></h2>";

      $result .= "<div style='padding-bottom:10px; padding-left:10px;'>";

      $hirek = $this->QsToArray("SELECT * FROM hir_hir WHERE hir_kat_id=" . $value["kategoria_id"] . " AND hir_hirlev_id=" . $hirlevel["hirlevel_id"] . " ORDER BY hir_id");

      foreach ($hirek as $key => $value)
      {
        $imageTag = $this->GetImageTag($value["hir_tipus"], 0);

        if ($mode == "preview")
        {
          $result .= "<h3><a name='hir" . $value["hir_id"] . "'>" . $value["hir_cim"] . "</a> ". $imageTag ."</h3>";
          $result .= "<div style='padding-bottom:10px;'>" . $value["hir_szoveg"] . "</div>";
          $result .= "<div style='padding-bottom:10px;'><a href='#top'>[vissza a tetejére]</a></div>";
        }
        else
        {
          $result .= (($value["hir_state"] != 0) ? "[" . $this->HirStateMap($value["hir_state"]) . "] " : "");
          $result .= "<a href='?cmd=edit&hir=" . $value["hir_id"] . "' class='link'>" . $value["hir_cim"] . "</a>&nbsp;" . $imageTag . "&nbsp;";
          $result .= "<a href='?cmd=delete&hir=" . $value["hir_id"] . "' onclick='return confirm(\"Tényleg törlöd a(z) " . $value["hir_cim"] . " cikket?\");' class='link'>[törlés]</a><br />";
        }
      }

      $result .= "</div>";
    }

    if ($mode == "preview")
    {
      $result .= "
<br>
<br>
Jó munkát!<br>
<br>
   Budapest, " . $this->GetToday() . ",<br>
<div align=\"right\">Az Országos Elnökség nevében<br>
Solymosi Balázs cst. <br>
országos vezetőtiszt <br>
</div>
<div align=\"justify\"><br>
</div>
<br>
<br>
-- <br>
Kiadja a Magyar Cserkészszövetség Országos Elnöksége<br>
   <a href=\"http://www.cserkesz.hu/elnokseg/\" target=\"_blank\">http://www.cserkesz.hu/<wbr>elnokseg/</a><br>
<br>";
      $result .= "</div>";
    }

    $result .= "</div>";
    $result .= "</div>";

    return $result;
  }

  function ListazCikkek3()
  {

    $smarty = new Smarty();
    
    $baseDir = '/home/oehirlevel/domains/oehirlevel.cserkesz.hu/public_html/templates/';
    
    $smarty->setTemplateDir($baseDir . 'templates/');
    $smarty->setCompileDir($baseDir . 'templates_c/');
    $smarty->setConfigDir($baseDir . 'configs/');
    $smarty->setCacheDir($baseDir . 'cache/');
    

    $hirlevel = $this->GetActualHirlevel();

    $qs = "SELECT * FROM hir_kategoria 
JOIN hir_hir ON hir_kat_id = kategoria_id 
WHERE hir_hirlev_id = " . $hirlevel["hirlevel_id"] . "
GROUP BY kategoria_id 
ORDER BY kategoria_id, hir_id";

    $kategoriak = $this->QsToArray($qs);

    $hirlevelTitle = $hirlevel["hirlevel_evfolyam"] . "/". $hirlevel["hirlevel_szam"] . ". " . $this->GetToday();
   
    $smarty->assign('hirlevelTitle', $hirlevelTitle);

    $kategoriakAll = array();

    foreach ($kategoriak as $key => $value)
    {
      $kat = array("kategoria_szoveg" => $value["kategoria_szoveg"], "hirek" => array());

      $hirek = $this->QsToArray("SELECT * FROM hir_hir WHERE hir_kat_id=" . $value["kategoria_id"] . " AND hir_hirlev_id=" . $hirlevel["hirlevel_id"] . " ORDER BY hir_id");
     
      foreach ($hirek as $key => $value)
      {
        $imageTag = $this->GetImageTag($value["hir_tipus"], 2);
        array_push($kat["hirek"], array("hir_id" => $value["hir_id"], "hir_cim" => $value["hir_cim"], "hir_szoveg" => $value["hir_szoveg"], "hir_imageTag" => $imageTag));
      }
      array_push($kategoriakAll, $kat);
    }

    $smarty->assign('kategoriak', $kategoriakAll);

    $result = $smarty->fetch('hirlevel.tpl');


    return $result;
  }

  function CerateKulugyiHir()
  {
    $hirlevel = $this->GetActualHirlevel();
    $hir_body = "<a href=\"http://www.google.com/reader/shared/17469660025783777635\">http://www.google.com/reader/shared/17469660025783777635</a>";

    $qs = "INSERT INTO hir_hir VALUES(null, 7, " . $hirlevel["hirlevel_id"] . ", 'Friss nemzetközi hírek - magyar nyelvű összefoglalóval ', '" . $hir_body . "', null, 1, '" . $_SESSION["user_id"] . "',now());";

    Query($qs);
  }

  function CreateAjanlo()
  {
    $hirlevel = $this->GetActualHirlevel();
    $hir_body = "Az oldal célja, hogy minél több olyan filmről vagy színdarabról informálódhassunk, amit mi, cserkészek ajánlunk egymásnak. ";
    $hir_body .= "Amennyiben Te is szívesen ajánlanál ilyen filmet vagy színdarabot, ";
    $hir_body .= "a <a href=\"mailto:programajanlas@cserkesz.hu\">programajanlas@cserkesz.hu</a> címen megteheted.";

    $qs = "INSERT INTO hir_hir VALUES(null, 3, " . $hirlevel["hirlevel_id"] . ", 'Film- és színdarab ajánló', '" . $hir_body . "', null, 1, '" . $_SESSION["user_id"] . "',now());";

    $this->Query($qs);

    $hir_body = "Ezen az oldalon nem feltétlen cserkész szervezésű, de a cserkészet értékrendjével egyező, elég vonzó és igényes programokról tájékozódhatsz. Az átláthatóság kedvéért fejlesztési területek szerint vannak strukturálva.<br><br>";
    $hir_body .= "Amennyiben (a későbbiekben bármikor) hallotok olyan programról, amit érdemes lenne többekkel megosztani, kérjük küldjetek erről tájékoztatást a programajánló címre (<a href=\"mailto:programajanlas@cserkesz.hu\">programajanlas@cserkesz.hu</a>), és feltesszük az oldalra.";

    $qs = "INSERT INTO hir_hir VALUES(null, 3, " . $hirlevel["hirlevel_id"] . ", 'Programajánló', '" . $hir_body . "', null, 1, '" . $_SESSION["user_id"] . "',now());";

    $this->Query($qs);
  }

  function GetHirlevelNev($evfolyam, $szam)
  {
    return "Hírlevél " . $evfolyam . "/" . $szam;
  }

  function HandleActions()
  {
    if ($_GET["cmd"] == "logout")
    {
      $qs = "INSERT INTO hir_log VALUES(null, now(), 'Logging out user " . $_SESSION["user"] . "')";
      $this->Query($qs);

      unset($_SESSION["user"]);
      unset($_SESSION["role_id"]);

      echo "<script>window.location.href = '/hirlevel.php'; </script>";
    }

    // Authentication
    if ($_GET["cmd"] == "auth")
    {
      if ($_GET["code"] != "")
      {
        $code = $_GET["code"];
        //echo $code . "\n";

        $client = new Google_Client();
        $client->setAuthConfigFile('client_secret.json');
        $client->setApplicationName("Client_Library_Examples");
        $client->setRedirectUri('postmessage');
      
      
        $client->authenticate($code);
        
        $accessTokenString = $client->getAccessToken();
        $accessToken = json_decode($accessTokenString);
        $access_Token = $accessToken->access_token;
      
        $userInfoString = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $access_Token);
      
        $userInfo = json_decode($userInfoString);
        
        $email = $userInfo->email;
        echo "email: " . $email . "\n";

        $qs = "INSERT INTO hir_log VALUES(null, now(), 'Logging in user " . $email . "')";
        $this->Query($qs);

        if ($email != "")
        {
          $qs = "SELECT * FROM hir_user 
JOIN hir_role ON user_role=role_id 
WHERE (user_email='" . $email . "') 
AND (user_del = 0)";
      
          $users = $this->QsToArray($qs);

          if (count($users) > 0)
          {
            $_SESSION["user"] = $users[0]["user_email"];
            $_SESSION["user_id"] = $users[0]["user_id"];
            $_SESSION["role_id"] = $users[0]["user_role"];
            $_SESSION["role_name"] = $users[0]["role_name"];
    
            $qs = "UPDATE hir_user SET user_lastlogin= now() WHERE user_email='" . $users[0]["user_email"] . "'";
            $this->Query($qs);
            echo "user logged in.\n";
          }
          else
          {
            echo "Hm, ez a felhasználó nincs regisztrálva. Esetleg próbáld meg itt: <a href='https://www.google.com/accounts/IssuedAuthSubTokens'>";
            echo "https://www.google.com/accounts/IssuedAuthSubTokens</a> visszavonni a hozzáférést a cserkesz.hu-tól, és utána ";
            echo "újra bejelentkezni.";
          }
        }
        
        exit();
        return;
      }
        
      $email = urldecode($_GET["openid_ext1_value_email"]);
      $identity = $_GET["openid_identity"];

      $qs = "SELECT * FROM hir_user 
JOIN hir_role ON user_role=role_id 
WHERE ((user_email='" . $email . "' AND user_key='') 
OR (user_key='" . $identity . "') 
OR (user_email='" . $email . "'". /*" AND user_key='" . $identity . "'".*/")) AND (user_del = 0)";

      $users = $this->QsToArray($qs);

      if (count($users) > 0)
      {
        $_SESSION["user"] = $users[0]["user_email"];
        $_SESSION["user_id"] = $users[0]["user_id"];
        $_SESSION["role_id"] = $users[0]["user_role"];
        $_SESSION["role_name"] = $users[0]["role_name"];

        $qs = "UPDATE hir_user SET user_lastlogin= now() WHERE user_email='" . $users[0]["user_email"] . "'";
        $this->Query($qs);

        if ($users[0]["user_key"] == "")
        {
          $qs = "UPDATE hir_user SET user_key='" . $identity . "' WHERE user_email='" . $users[0]["user_email"] . "'";
          $this->Query($qs);

          echo "Most léptél be először, regisztráltalak.";
        }
      }
      else
      {
        echo "Hm, ez a felhasználó nincs regisztrálva. Esetleg próbáld meg itt: <a href='https://www.google.com/accounts/IssuedAuthSubTokens'>";
        echo "https://www.google.com/accounts/IssuedAuthSubTokens</a> visszavonni a hozzáférést a cserkesz.hu-tól, és utána ";
        echo "újra bejelentkezni.";
      }
    }

    if (($_GET["cmd"] == "login") && (!isset($_SESSION["user"])))
    {

      $address = "https://www.google.com/accounts/o8/ud
?openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0
&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
&openid.return_to=http%3A%2F%2Foehirlevel.cserkesz.hu%2Fhirlevel.php%3Fcmd%3Dauth
&openid.realm=http%3A%2F%2Foehirlevel.cserkesz.hu%2Fhirlevel.php%3Fcmd%3Dauth
&openid.assoc_handle=ABSmpf6DNMw
&openid.mode=checkid_setup
&openid.ns.ext1=http%3A%2F%2Fopenid.net%2Fsrv%2Fax%2F1.0
&openid.ext1.mode=fetch_request
&openid.ext1.type.email=http%3A%2F%2Faxschema.org%2Fcontact%2Femail
&openid.ext1.required=email
&openid_shutdown_ack=2015-04-20";

      echo '<html>
    <head>
    <meta http-equiv="refresh" content="0; URL=' . $address . '">
    </head>
    <body></body>
    </html>
    ';

      return;
    }

    if ($_POST["send"] == "Küld" && $this->IsAdmin())
    {
      $mail = new PHPMailer();

      $mail->From = "kovi@cserkesz.hu";
      $mail->FromName = "Kovi";
      // $mail->Host     = "cserkesz.hu";
      $mail->Mailer = "smtp";

      $akthirlevel = $this->GetActualHirlevel();

      $mail->Body = $this->ListazCikkek("preview");
      $mail->Subject = $akthirlevel["hirlevel_nev"];
      $mail->ContentType = "text/html; charset=utf-8";
      $mail->CharSet = "utf-8";
      $mail->AddAddress($_POST["mailcim"], $_POST["mailcim"]);

      if (!$mail->Send())
      {
        echo "There has been a mail error.";
      }
    }

    if ($_POST["send2"] == "Küld2" && $this->IsAdmin())
    {
      $mail = new PHPMailer();

      $mail->From = "kovi@cserkesz.hu";
      $mail->FromName = "Kovi";
      // $mail->Host     = "cserkesz.hu";
      $mail->Mailer = "smtp";

      $akthirlevel = $this->GetActualHirlevel();

      $mail->Body = $this->ListazCikkek3();
      $mail->Subject = $akthirlevel["hirlevel_nev"];
      $mail->ContentType = "text/html; charset=utf-8";
      $mail->CharSet = "utf-8";
      $mail->AddAddress($_POST["mailcim2"], $_POST["mailcim2"]);

      if (!$mail->Send())
      {
        echo "There has been a mail error. ";
        echo $mail->ErrorInfo;
      }
    }

    if ($_POST["savehir"] == "Ment")
    {
      $qs = "UPDATE hir_hir SET hir_state = 1, hir_cim='" . $_POST["cim"] . "', hir_szoveg='" . $_POST["FCKeditor1"] . "', hir_tipus = " . $this->Escape($_POST["tipus"]) . " WHERE hir_id=" . $_POST["hirid"];

      $this->Query($qs);
    }

    if (isset($_POST["hircontrolled"]))
    {

      $qs = "UPDATE hir_hir SET hir_state=2 WHERE hir_id=" . $_POST["hirid"];

      $this->Query($qs);
    }

    if ($_POST["addhir"] == "Létrehoz")
    {

      $hirlevel = $this->GetActualHirlevel();

      $qs = "INSERT INTO hir_hir VALUES(null, " . $_POST["kategoria"] . ", " . $hirlevel["hirlevel_id"] . ", '" . $_POST["cim"] . "', '', null, 0, '" . $_SESSION["user_id"] . "',now());";

      $this->Query($qs);
    }

    if ($_POST['adduser'] == "Mehet")
    {
      if ($this->IsAdmin())
      {
        $email = $this->Escape($_POST["email"]);
        $role = $this->Escape($_POST["role"]);
        
        $qs = "SELECT * FROM hir_user WHERE user_del = 0 AND user_email = '" . $email . "'";
        $result = $this->QsToArray($qs);
        if (count($result) > 0)
        {
          $this->AddMessage("Nem sikerült hozzáadni, már van ilyen felhasználó.");
          return;
        }

        $qs = "SELECT * FROM hir_user WHERE user_del = 1 AND user_email = '" . $email . "'";
        $result = $this->QsToArray($qs);
        if (count($result) > 0)
        {
          $userId = $result[0]["user_id"];
          $this->Query("UPDATE hir_user SET user_del = 0 WHERE user_id = " . $userId);
          return;
        }

        $qs = "INSERT INTO hir_user VALUES(null," . $role . ", '" . $email . "','', null, 0)";

        $this->Query($qs);
      }
    }

    if ($_GET["cmd"] == "deluser")
    {
      if ($this->IsAdmin())
      {
        $qs = "UPDATE hir_user SET user_del = 1 WHERE user_id = " . $_GET["user"];
        $this->Query($qs);
      }
    }

    if ($_POST['addhirlevel'] == "Lezárás")
    {
      $hirlevel = $this->GetActualHirlevel();

      $qs = "UPDATE hir_hirlevel SET hirlevel_closedby = " . $_SESSION["user_id"] . ", hirlevel_closedtime = now()" . " WHERE hirlevel_id=" . $hirlevel["hirlevel_id"];
      $this->Query($qs);

      // TODO: what if there is no $hirlevel?

      $szam = $hirlevel["hirlevel_szam"] + 1;
      $newName = $this->GetHirlevelNev($hirlevel["hirlevel_evfolyam"], $szam);
      $qs = "INSERT INTO hir_hirlevel VALUES(null, '" . $newName . "', null, null, '" . $hirlevel["hirlevel_evfolyam"] . "', " . $szam . ")";
      $this->Query($qs);

      $this->CerateKulugyiHir();
      $this->CreateAjanlo();
    }

    if ($_POST['renamehirlevel'] == "Átnevez")
    {
      $hirlevel = $this->GetActualHirlevel();

      $newName = $this->Escape($_POST["hirlevelnev"]);

      $qs = "UPDATE hir_hirlevel SET hirlevel_nev = '" . $newName . "' WHERE hirlevel_id=" . $hirlevel["hirlevel_id"];
      $this->Query($qs);
    }

    if ($_POST['renumberhirlevel'] == "Módosít")
    {
      $hirlevel = $this->GetActualHirlevel();

      $newEvfolyam = $this->Escape($_POST["hirlevelevfolyam"]);
      $newSzam = intval($this->Escape($_POST["hirlevelszam"]));

      if (is_int($newSzam))
      {
        $newName = $this->GetHirlevelNev($newEvfolyam, $newSzam);

        $qs = "UPDATE hir_hirlevel SET hirlevel_evfolyam = '" . $newEvfolyam . "', hirlevel_szam = " . $newSzam . ", hirlevel_nev='" . $newName . "'" . " WHERE hirlevel_id=" . $hirlevel["hirlevel_id"];
        $this->Query($qs);
      }
      else
      {
        echo "A hírlevél számának egész számnak kell lennie!";
      }
    }

    if ($_GET["cmd"] == "delete")
    {
      $qs = "DELETE FROM hir_hir WHERE hir_id=" . $_GET["hir"];

      $this->Query($qs);
    }

    if ($_GET["cmd"] == "generate")
    {
      echo $this->ListazCikkek3();
      exit();
    }
  }

  function LoginControlBox()
  {
    $result = "";
    $result .= "<div class='box'>";
    if (isset($_SESSION["user"]))
    {
      $result .= "Szia <strong>" . $_SESSION["user"] . "</strong>! ";
      $result .= "Te <strong>" . $_SESSION["role_name"] . "</strong> vagy.";
      $result .= " <a href='?cmd=logout' onclick='logutClicked = true; gapi.auth.signOut(); return false;' >[kilépes]</a>";
    }
    else
    {
      $result .= "Szia! A szerkesztő használatához lépj be! <a href='?cmd=login'>[belépés]</a>";
    }
      /*
        https://developers.google.com/+/web/people/
        https://developers.google.com/+/quickstart/php
        http://stackoverflow.com/questions/11606101/how-to-get-user-email-from-google-plus-oauth
        https://github.com/google/google-api-php-client
        https://developers.google.com/oauthplayground
        */
            
      $result .= '<div id="gConnect" class="button"';
      if (isset($_SESSION["user"]))
      {
        $result .= ' style="display:none;"';
      }      
      $result .= '>
      <button class="g-signin"
          data-scope="email"
          data-clientid="78201325967-6olgu8bu42958oqc0481u86j59i48fs8.apps.googleusercontent.com"
          data-callback="onSignInCallback"
          data-theme="dark"
          data-cookiepolicy="single_host_origin">
      </button>
      </div>';
    
    $result .= "<script type=\"text/javascript\">
  function onSignInCallback(resp) {
      var authResult = resp;
      
      console.log('authResult:');
      console.log(authResult);
            
      if (authResult['code']) {
        $('#gConnect').attr('style', 'display: none');
";
            
            if (!isset($_SESSION["user"]))
            {
              $result .=  "
                  
        $.ajax({
          type: 'GET',
          url: '/hirlevel.php?cmd=auth&code=' + authResult['code'],
          contentType: 'application/octet-stream; charset=utf-8',
          success: function(result) {
              console.log(result);
            location.reload();
          }
        });
";
            }
            
            // logutClicked is needed, because sometimes a user_logged_out was received after login without the user clicking the [logout]
            
            $result .=  "        
      } else if ((authResult['error']) && (typeof logutClicked != 'undefined')) {
        ";
            if (isset($_SESSION["user"]))
            {
              $result .=  "window.location.href = '/hirlevel.php?cmd=logout';";
            }
                
        $result .=  "
      }
  }
  </script>";
    
    $result .= "</div>";

    return $result;
  }

  function MenuBar()
  {
    $result = "";
    $result .= "<div style='padding:5px; background-color:#fff1f1; margin:5px;'>";
    $result .= "<a href='" . $_SERVER["PHP_SELF"] . "'>[Kezdőlap]<a/>&nbsp;<a href='?cmd=preview'>[Előnézet]</a>&nbsp;<a href='?cmd=preview2'>[Előnézet2]</a>";
    $result .= "</div>";
    return $result;
  }

  function HirStateMap($state)
  {
    switch ($state)
    {
      case 0:
        return "létrehozva";
      case 1:
        return "lezárva";
      case 2:
        return "ellenőrizve";
    }
  }

  function EditHir()
  {
    $hir_id = $_GET["hir"];
    $hirek = $this->QsToArray("SELECT * FROM hir_hir LEFT JOIN hir_user ON hir_creator = user_id WHERE hir_id=" . $hir_id);
    $hir = $hirek[0];

    $result = "";
    $result .= "<div class='box'>";

    $result .= "<table style='margin-bottom:10px;'>";
    $result .= "<tr>";
    $result .= "<td>A hír címe:</td>";
    $result .= "<td>" . $hir["hir_cim"] . "</td>";
    $result .= "</tr>";
    $result .= "<tr>";
    $result .= "<td>A hírt hozzáadta:</td>";
    $result .= "<td>" . $hir["user_email"] . "</td>";
    $result .= "</tr>";
    $result .= "<tr>";
    $result .= "<td>A hír állapota:</td>";
    $result .= "<td>" . $this->HirStateMap($hir["hir_state"]) . "</td>";
    $result .= "</tr>";
    $result .= "</table>";

    if ((($hir["hir_state"] == 0) && ($hir["hir_creator"] == $_SESSION["user_id"])) || $this->IsAdmin())
    {

      $result .= "<h2>Hír szereksztése</h2>";

      $result .= "<form method=POST action=" . $_SERVER["PHP_SELF"] . ">";
      $result .= "<input type=hidden name='hirid' value='" . $hir_id . "'>";

      $result .= "<table style='margin-bottom:10px;'>";
      $result .= "<tr>";
      $result .= "<td>Típus:</td><td>";

      $tipusok = $this->GetTipusok();
      
      $result .= $this->CreateSelect("tipus", $tipusok, "id", "nev", $hir["hir_tipus"]);
      $result .= "</td>";
      $result .= "</tr>";
      $result .= "<tr>";
      $result .= "<td><input type=submit value='Ment' name='savehir'></td>";
      if ($this->IsAdmin())
      {
        $result .= "<td><input type=submit value='Hír ellenőrizve' name='hircontrolled' onclick='return confirm(\"Tényleg?\");'></td>";
      }
      $result .= "</tr>";
      $result .= "</table>";

      $result .= "<table>";
      $result .= "<tr>";
      $result .= "<td>Cím:</td><td><input type=text name='cim' size=60 value='" . $hir["hir_cim"] . "'></td>";
      $result .= "</tr>";

      $result .= "</table>";

      $result .= "<div style='height:600px; padding-top:10px;'>";

      $oFCKeditor = new FCKeditor('FCKeditor1');
      $oFCKeditor->BasePath = 'fckeditor/';
      $oFCKeditor->Config["CustomConfigurationsPath"] = "myconfig.js";
      $oFCKeditor->Height = '100%';
      $oFCKeditor->Value = $hir["hir_szoveg"];
      $oFCKeditor->Config['EnterMode'] = 'br';
      $oFCKeditor->Config['ForcePasteAsPlainText'] = true;
      $oFCKeditor->ToolbarSet = "Basic";

      $result .= $oFCKeditor->CreateHtml();

      $result .= "</div>";

      $result .= "</form>";
    }
    else
    {

      $result .= "A hír le van zárva.";
      $result .= "<div class='box' style='background-color:white;'>";
      $result .= $hir["hir_szoveg"];
      $result .= "</div>";
    }

    $result .= "</div>";

    return $result;
  }

  function GetKategoriak($level = 0, $parentId = null)
  {
    $result = array();

    $condition = $parentId == null ? "IS NULL" : "=" . $parentId;

    $kategoriak = $this->QsToArray("SELECT * FROM hir_kategoria WHERE kategoria_inactive = 0 AND kategoria_parent " . $condition . " ORDER BY kategoria_id");

    foreach ($kategoriak as $row)
    {
      for ($i = 0; $i < $level; $i++)
      {
        $row["kategoria_szoveg"] = "&nbsp;&nbsp;" . $row["kategoria_szoveg"];
      }
      array_push($result, $row);
      $alkategoriak = $this->GetKategoriak($level + 1, $row["kategoria_id"]);
      foreach ($alkategoriak as $subrow)
      {
        array_push($result, $subrow);
      }
    }

    return $result;
  }

  function UjHir()
  {
    $result = "";
    $result .= "<div class='box'>";
    $result .= "<form method=POST action='" . $_SERVER["PHP_SELF"] . "'>";
    $result .= "<h3>Új hír</h3>";
    $result .= "<div class='formRow'>Kategória: ";

    $kategoriak = $this->GetKategoriak();

    $result .= $this->CreateSelect("kategoria", $kategoriak, "kategoria_id", "kategoria_szoveg");

    $result .= "</div>";
    $result .= "<div class='formRow'>Cím: <input type=text size=60 name='cim' onkeyup='if (this.value!=\"\") {document.getElementById(\"addhir\").disabled=false;} else { document.getElementById(\"addhir\").disabled=true;}'></div>";
    $result .= "<div class='formRow'><input type=submit id='addhir' value='Létrehoz' name='addhir' disabled='disabled'></div>";
    $result .= "</form>";
    $result .= "</div>";

    return $result;
  }

  function AdminUserColtrolBox()
  {
    $result = "";
    $result .= "<div class='box admin'>";
    $result .= "<h3>Felhasználók</h3>";
    $users = $this->QsToArray("SELECT user_email, role_name, user_lastlogin, user_id  FROM hir_user JOIN hir_role ON user_role=role_id WHERE user_del = 0 ORDER BY user_lastlogin DESC");

    for ($i = 0; $i < count($users); $i++)
    {
      $users[$i]["user_id"] = "<a href='" . $_SERVER["PHP_SELF"] . "?cmd=deluser&user=" . $users[$i]["user_id"] . "' onclick='return confirm(\"Tényleg törlöd a(z) " . $users[$i]["user_email"] . " felhasználót?\");'>[törlés]</a>";
    }

    $result .= $this->ArrayToTable($users, array("e-mail cím", "szerep", "utolsó belépés", "törlés"));

    $result .= "<div class='box'>";
    $result .= "<form method=POST action=" . $_SERVER["PHP_SELF"] . ">";
    $result .= "Új felhasználó: google account e-mail:<input type=text size=40 name=email ";
    $result .= "onkeyup='if (this.value!=\"\") {document.getElementById(\"adduser\").disabled=false;} else { document.getElementById(\"adduser\").disabled=true;}'> ";

    $roles = $this->QsToArray("SELECT * FROM hir_role ORDER BY role_id DESC");

    $result .= $this->CreateSelect("role", $roles, "role_id", "role_name");

    $result .= "<input type=submit value='Mehet' name='adduser' id='adduser' disabled='disabled'>";
    $result .= "</form>";
    $result .= "</div>";

    $result .= "</div>";

    return $result;
  }

  function AdminHirlevelPropertiesControlBox()
  {
    $hirlevel = $this->GetActualHirlevel();
    $result = "";
    $result .= "<div class='box admin'>";
    $result .= "<h3>Hírlevél tulajdonságai</h3>";
    $result .= "<form method=post action=" . $_SERVER["PHP_SELF"] . ">";

    $result .= "<div>";
    $result .= "A hírlevél neve: <input type=text name='hirlevelnev' onkeyup='document.getElementById(\"renamehirlevel\").disabled=this.value==\"\";' ";
    $result .= "value='" . $hirlevel["hirlevel_nev"] . "'> ";
    $result .= "<input type='submit' name='renamehirlevel' id='renamehirlevel' value='Átnevez'>";
    $result .= "</div>";

    $result .= "<div>";
    $result .= "Évfolyam: <input type=text name='hirlevelevfolyam' size='5' value='" . $hirlevel["hirlevel_evfolyam"] . "'> ";
    $result .= "Szám: <input type=text name='hirlevelszam' size='3' value='" . $hirlevel["hirlevel_szam"] . "'> ";
    $result .= "<input type='submit' name='renumberhirlevel' id='renumberhirlevel' value='Módosít'> ";
    $result .= "Ez a hírlevél nevét is frissíti.";
    $result .= "</div>";

    $result .= "</form>";

    $result .= "</div>";

    return $result;
  }

  function AdminHirlevelControlBox()
  {
    $result = "";
    $result .= "<div class='box admin'>";
    $result .= "<h3>Hírlevél lezárása</h3>";
    $result .= "<form method=post action=" . $_SERVER["PHP_SELF"] . ">";
    $result .= "A hírlevél lezárásakor új hírlevél aktiválódik. Előtte küldd el magadnak e-mailben. ";
    $result .= "<input type='submit' name='addhirlevel' id='addhirlevel' value='Lezárás' ";
    $result .= "onclick='return confirm(\"Tényleg lezárod az aktuális hírlevelet? Utána már nem tudod e-mailben elküldeni.\");'>";

    $result .= "</form>";

    $result .= "<div class='box'>";
    $result .= "<form method=post action='?'>Küldes ide: <input type=text name='mailcim' value='" . $_SESSION["user"] . "' size=30>";
    $result .= "<input type=submit name=send value='Küld'></form>";
    $result .= "</div>";

    $result .= "<div class='box'>";
    $result .= "<form method=post action='?'>Küldes ide2: <input type=text name='mailcim2' value='" . $_SESSION["user"] . "' size=30>";
    $result .= "<input type=submit name=send2 value='Küld2'></form>";
    $result .= "</div>";

    $result .= "</div>";
    return $result;
  }

  function HirKeres()
  {
    $result = "";
    $result .= "<div class='box'>";
    $result .= "<h3>Hír keresése</h3>";

    $result .= "<form method=get action=" . $_SERVER["PHP_SELF"] . ">";

    $result .= "Keresés: <input type=text name='keres' size=40> ";
    $result .= "<input type='submit' name='hirkeres' id='hirkeres' value='Mehet'>";

    $result .= "</form>";

    if ($_GET["hirkeres"] == "Mehet")
    {
      $result .= "<h3>Találatok:</h3>";

      $qs = "SELECT * FROM hir_hir WHERE hir_cim LIKE '%" . $_GET["keres"] . "%' OR hir_szoveg LIKE '%" . $_GET["keres"] . "%'";
      $hirek = $this->QsToArray($qs);

      foreach ($hirek as $key => $value)
      {
        $result .= "<a href='?cmd=edit&hir=" . $value["hir_id"] . "'>" . $value["hir_cim"] . "</a>&nbsp;<br>";
      }
    }
    $result .= "</div>";

    return $result;
  }

  function PrintHeader()
  {
    $result = "";

    $result .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
    $result .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="hu" lang="hu" dir="ltr">' . "\n";
    $result .= "<head>";
    $result .= "<title>OE hírlevel szerkesztő</title>";
    $result .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

    $result .= "<style>";
    $result .= "h1, h3, h2 {margin-top: 0px; margin-bottom:10px;}";
    $result .= ".formRow { padding: 2px;}";
    $result .= ".box {border: 1px solid ; padding: 5px; margin:5px;}";
    $result .= "form {margin:0px;}";
    $result .= "table {border:1px solid; border-collapse:collapse;}";
    $result .= "td {border:1px solid; padding:4px;}";
    $result .= ".admin {background-color: yellow;}";
    $result .= "</style>";
    $result .= '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>';
    $result .= '<script src="https://apis.google.com/js/client:platform.js" async defer></script>';
    $result .= "</head>";

    $result .= "<body style='background-color:#e0e0ff; height:100%;'>";
    $result .= "<h1>Az OE hírlevel szerkesztése</h1>";

    return $result;
  }

  function PrintMessages()
  {
    $result = "";

    if (count($this->messages) > 0)
    {
      $result .= "<div class='box' style='background-color: orange;'>";

      foreach ($this->messages as $message) 
      {
        $result .= "<div>" . $message . "</div>";
      }

      $result .= "</div>";
    }

    return $result;
  }

  function Process()
  {
    session_start();

    header('Content-type: text/html; charset=utf-8');

    $this->HandleActions();

    echo $this->PrintHeader();

    if (isset($_SESSION["user"]))
    {
      echo $this->PrintMessages();
      echo $this->MenuBar();
    }

    echo $this->LoginControlBox();

    if (isset($_SESSION["user"]))
    {
      $hirlevel = $this->GetActualHirlevel();

      echo "<div class='box'>";
      echo "Az aktuális hírlevél: <strong>" . $hirlevel["hirlevel_nev"] . "</strong>";
      echo "</div>";

      if ($_GET["cmd"] == "edit")
      {
        echo $this->EditHir();
      }
      else if ($_GET["cmd"] == "preview")
      {
        echo $this->ListazCikkek("preview");
      }
      else if ($_GET["cmd"] == "preview2")
      {
        echo '<iframe src="?cmd=generate" width="100%" height="1000"></iframe>';  //$this->ListazCikkek3();
      }
      else
      {
        if ($this->IsAdmin())
        {
          echo $this->AdminUserColtrolBox();
          echo $this->AdminHirlevelPropertiesControlBox();
          echo $this->AdminHirlevelControlBox();
        }

        echo $this->UjHir();

        echo $this->HirKeres();

        echo $this->ListazCikkek("default");
      }
    }

    echo "</body></html>";
  }

}

$hirlevelPage = new HirlevelPage($username, $password);
$hirlevelPage->Process();
?>
