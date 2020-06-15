<?php
define("IN_MYBB", 1);
// define('THIS_SCRIPT', 'blacklist.php');
// require_once "./global.php";
require("global.php");  

global $db, $templates, $mybb;
$email = $mybb->user['email'];
$uid = $mybb->user['uid'];

if(!isset($mybb->settings['blacklist_applicant'])) {
    die("Die Blacklist ist zur Zeit nicht aktiviert.");
}

$username = "";
$thisMonth = date("m.Y",time());
$day = date("d", time());
$blacklistUser = array();
$blacklistUserAnnulled = array();
$blacklistAll = array();
$blacklistHelp = array();

if ($mybb->settings['blacklist_guest'] == "0" && $mybb->user['uid'] == 0){
    error_no_permission();
}

//Einstellungen holen
if($mybb->settings['blacklist_ice'] == "-1"){
    $fidIceDB = "";
}else{
    $fidIceDB = "fid" . intval($mybb->settings['blacklist_ice']) . ",";
    $fidIce = intval($mybb->settings['blacklist_ice']);
}
$fidPlayer = intval($mybb->settings['blacklist_player']);
$inplay = intval($mybb->settings['blacklist_inplay']);
$archiv = intval($mybb->settings['blacklist_archive']);

$dayEcho = intval($mybb->settings['blacklist_echo']);
if($mybb->settings['blacklist_applicant'] == "1"){
    $applicant = -1;
}else{
    $applicant = 2;
}

if($mybb->settings['blacklist_showUser'] == "1"){
    $showOtherUsers = false;
}else{
    $showOtherUsers = true;
}

$invisibleAccounts = "";
$accounts = explode(", ", $db->escape_string($mybb->settings['blacklist_teamaccs']));
foreach($accounts as $account){
    if($account != -1){
        $invisibleAccounts .= "XOR uid = ". $account ." ";
    }
}

if($fidPlayer == -1 || $inplay == -1 || $archiv == -1){
    die("Fülle bitte zuerst die Einstellungen im AdminCP aus. Es muss mind. die FID vom Spielernamenprofilfeld, die ID der Inplaykategorie und die ID der 
    Archivkategorie angebenen werden.");
}

//User streichen
if($_POST["action"] == "delete"){
    $uid = $_POST["id"];
    array_push($blacklistUserAnnulled, $uid);
}

//alle User bekommen
$allUsers = $db->query("SELECT uid FROM ".TABLE_PREFIX."users");
while ($allUser=$db->fetch_array($allUsers)){
    array_push($blacklistAll, $allUser['uid']);
}

//alle auf "nicht auf der Blacklist" setzen (nur am 1.)
if(date("j", time()) == 1){
    foreach($blacklistAll as $user){
        $db->query("UPDATE ".TABLE_PREFIX."users SET isOnBlacklist = 0 WHERE uid = ". $user ."");
    }
}

//Kontrolle, ob Post in diesem Monat oder letzten Monat => nicht Blacklist
$lastIPPosts = $db->query("SELECT uid, dateline
FROM ".TABLE_PREFIX."posts p JOIN ".TABLE_PREFIX."forums f ON f.fid = p.fid
WHERE f.parentlist LIKE '" . $inplay . ",%' OR f.parentlist LIKE '%". $archiv ."%'");
while ($lastIPPost=$db->fetch_array($lastIPPosts)){
    if(!isOlderThanOneMonth($lastIPPost['dateline']) && !isInThisMonth($lastIPPost['dateline'])){
        array_push($blacklistHelp, $lastIPPost['uid']);
    }
}

//Differnez von allen User und denen, die nicht auf der BL sind
$blacklistUser = array_diff($blacklistAll, $blacklistHelp);
foreach($blacklistUser as $user){
    $db->query("UPDATE ".TABLE_PREFIX."users SET isOnBlacklist = 1 WHERE uid = ". $user ."");
}

//gestrichene User finden (die, die gepostet haben und die, die vom Team gestrichen wurden)
$lastIPPosts2 = $db->query("SELECT u.uid, dateline, isOnBlacklistAnnulled
FROM ".TABLE_PREFIX."users u 
JOIN ".TABLE_PREFIX."posts p ON p.uid = u.uid
JOIN ".TABLE_PREFIX."forums f ON f.fid = p.fid
WHERE isOnBlacklistAnnulled = 1 OR (isOnBlacklist = 1 AND (f.parentlist LIKE '" . $inplay . ",%' OR f.parentlist LIKE '%". $archiv ."%'))");
while ($lastIPPost=$db->fetch_array($lastIPPosts2)){
    if(isInThisMonth($lastIPPost['dateline']) || $lastIPPost['isOnBlacklistAnnulled'] == 1){
        array_push($blacklistUserAnnulled, $lastIPPost['uid']);
    }
}

foreach($blacklistUserAnnulled as $user){
    $db->query("UPDATE ".TABLE_PREFIX."users SET isOnBlacklistAnnulled = 1 WHERE uid = ". $user ."");
}

// Charaktere, die auf der Blacklist, auf Eis oder abwesend sind
$users = $db->query("SELECT username,usergroup,displaygroup,uid, " . $fidIceDB . " fid" . $fidPlayer . ",away,as_uid, email
FROM ".TABLE_PREFIX."users u LEFT JOIN ".TABLE_PREFIX."userfields uf ON(u.uid=uf.ufid)
WHERE NOT usergroup = " . $applicant . " " . $invisibleAccounts . "
ORDER BY username");
$black = "";
$away = "";
$onIce = "";
$deleteButton = "";
while($user=$db->fetch_array($users)) {
    if(!$showOtherUsers && $user['email'] == $email || $showOtherUsers){
        $username = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
        if($mybb->usergroup['canmodcp'] == 1 && $dayEcho >= date("j", time())){ //wenn User Admin oder Mod ist
            $deleteButton = '<i class="fas fa-user-minus delete buttonBlack" title="' . $user['username'] . ' streichen?" id="' .  $user['uid']  . '"></i>';
        }
        if($user['fid' . $fidIce . ''] == "Ja"){
            eval("\$onIce .= \"".$templates->get("blacklistUser")."\";");
        }elseif($user['away'] == 1){
            if($user['as_uid'] == 0){
                $username = build_profile_link($user['fid' . $fidPlayer . ''], $user['uid']);
                $deleteButton = "";
                eval("\$away .= \"".$templates->get("blacklistUser")."\";");
            }
        }elseif(in_array($user['uid'], $blacklistUser) && !in_array($user['uid'], $blacklistUserAnnulled)){
            eval("\$userBlack .= \"".$templates->get("blacklistUser")."\";");
        }elseif(in_array($user['uid'], $blacklistUserAnnulled)){
            eval("\$userBlack .= \"".$templates->get("blacklistUserAnnulled")."\";");
        }
    }
}

//Hilfsfunktionen
function isOlderThanOneMonth($dateToTest){
    $oneMonthAgo = strtotime("-1 month");
    $firstOneMonthAgo = "01." . date("m.Y",$oneMonthAgo);

    $UnixFirstOneMonthAgo = strtotime($firstOneMonthAgo);
    if($dateToTest > $UnixFirstOneMonthAgo){
        return false;
    }else{
        return true;
    }
}

function isInThisMonth($dateToTest){
    $thisMonth = date("m", time());
    $monthDateToTest = date("m", $dateToTest);
    if($monthDateToTest == $thisMonth){
        return true;
    }else{
        return false;
    }
}

if($mybb->settings['blacklist_ice'] == "-1"){//auf eis ist deaktiviert
    eval("\$page = \"".$templates->get("blacklist")."\";");
}else{
    eval("\$page = \"".$templates->get("blacklistIce")."\";");
}
output_page($page);