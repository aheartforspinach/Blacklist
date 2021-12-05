<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'blacklist.php');
require("global.php");
global $db, $templates, $mybb;

if (!isset($mybb->settings['blacklist_applicant'])) die("Die Blacklist ist zur Zeit nicht aktiviert.");

$thisMonth = date("m.Y", time());

if ($mybb->settings['blacklist_guest'] == '0' && $mybb->user['uid'] == 0) error_no_permission();

require_once "inc/datahandlers/blacklist.php";
$blacklistHandler = new BlacklistHandler($mybb->user['uid']);
//Einstellungen holen
$fidIce = intval($mybb->settings['blacklist_ice']);
$fidIceDB = $fidIce == -1 ? '' : 'fid' . $fidIce;
$fidPlayer = intval($mybb->settings['blacklist_player']);

$dayEcho = intval($mybb->settings['blacklist_echo']);
$applicant = intval($mybb->settings['blacklist_applicant']) == 1 ? -1 : intval($mybb->settings['blacklist_applicant_group']);

$invisibleAccounts = '';
$accounts = explode(', ', $db->escape_string($mybb->settings['blacklist_teamaccs']));
foreach ($accounts as $account) {
    if ($account == -1) continue;
    $invisibleAccounts .= 'XOR uid = ' . $account . ' ';
}

//User streichen
if ($_POST["action"] == 'delete') $blacklistHandler->markAsAnnulled($_POST["id"]);

$blacklistUsers = $blacklistHandler->getAllBlacklistCharas();
$blacklistUsersAnnulled = $blacklistHandler->getAllAnnulledCharas($blacklistUsers);

// Charaktere, die auf der Blacklist, auf Eis oder abwesend sind
$users = $db->query("SELECT username, usergroup, displaygroup, uid, " . $fidIceDB . " fid" . $fidPlayer . ", away, as_uid, isOnBlacklist
FROM " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "userfields uf ON(u.uid=uf.ufid)
WHERE NOT usergroup = " . $applicant . " " . $invisibleAccounts . "
ORDER BY username");
$black = "";
$away = "";
$onIce = "";
$deleteButton = "";
while ($user = $db->fetch_array($users)) {
    $username = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']);
    if ($mybb->usergroup['canmodcp'] == 1 && $dayEcho >= date("j", time())) $deleteButton = '<i class="fas fa-user-minus delete buttonBlack" title="' . $user['username'] . ' streichen?" id="' .  $user['uid']  . '"></i>';

    if ($user['fid' . $fidIce . ''] == 'Ja') {
        eval("\$onIce .= \"" . $templates->get("blacklistUser") . "\";");
    } elseif ($user['away'] == 1) {
        if ($user['as_uid'] == 0) {
            $username = build_profile_link($user['fid' . $fidPlayer . ''], $user['uid']);
            $deleteButton = "";
            eval("\$away .= \"" . $templates->get("blacklistUser") . "\";");
        }
    } elseif (in_array($user['uid'], $blacklistUsersAnnulled)) {
        eval("\$userBlack .= \"" . $templates->get("blacklistUserAnnulled") . "\";");
    } elseif (in_array($user['uid'], $blacklistUsers)) {
        eval("\$userBlack .= \"" . $templates->get("blacklistUser") . "\";");
    }
}

if ($mybb->settings['blacklist_ice'] == "-1") { //auf eis ist deaktiviert
    eval("\$page = \"" . $templates->get("blacklist") . "\";");
} else {
    eval("\$page = \"" . $templates->get("blacklistIce") . "\";");
}
output_page($page);
