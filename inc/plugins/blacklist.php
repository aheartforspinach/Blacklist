<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

function blacklist_info()
{
    return array(
        "name"            => "Blacklist",
        "description"    => "erstellt automatisch eine Liste von Charakteren, die auf der Blacklist stehen",
        "author"        => "aheartforspinach",
        "authorsite"    => "https://storming-gates.de/member.php?action=profile&uid=176",
        "version"        => "1.0",
        "compatibility" => "18*"
    );
}

function blacklist_install()
{
    global $db;

    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD isOnBlacklist INT(1) NOT NULL DEFAULT '0';");
    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD isOnBlacklistAnnulled INT(1) NOT NULL DEFAULT '0';");
    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD hasSeenBlacklist INT(1) NOT NULL DEFAULT '0';");

    // Task
    $date = new DateTime('01.' . date("m.Y", strtotime('+1 month')));
    $date->setTime(1, 0, 0);
    $blacklistTask = array(
        'title' => 'Blacklist Reset',
        'description' => 'Automatically resets all fields from the blacklist plugin',
        'file' => 'blacklist',
        'minute' => 0,
        'hour' => 0,
        'day' => 1,
        'month' => '*',
        'weekday' => '*',
        'nextrun' => $date->getTimestamp(),
        'logging' => 1,
        'locked' => 0
    );
    $db->insert_query('tasks', $blacklistTask);

    //Einstellungen 
    $setting_group = array(
        'name' => 'blacklist',
        'title' => 'Blacklist',
        'description' => 'Einstellungen für das Blacklist-Plugin',
        'isdefault' => 0
    );
    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'blacklist_guest' => array(
            'title' => 'Sichtbarkeit',
            'description' => 'Sollen Gäste die Blacklist sehen können?',
            'optionscode' => 'yesno',
            'value' => 1, // Default
            'disporder' => 1
        ),
        'blacklist_applicant' => array(
            'title' => 'Auflistung Bewerber',
            'description' => 'Sollen Bewerber auch gelistet werden?',
            'optionscode' => 'yesno',
            'value' => 0, // Default
            'disporder' => 2
        ),
        'blacklist_applicant_group' => array(
            'title' => 'Bewerbergruppe',
            'description' => 'In welcher Gruppe sind die Bewerber?',
            'optionscode' => 'groupselect',
            'value' => 0, // Default
            'disporder' => 3
        ),
        'blacklist_teamaccs' => array(
            'title' => 'Teamaccount',
            'description' => 'Gib hier mit Komma getrennt die UIDs von den Accounts an, die NICHT gelistet werden sollen. Falls alle gelistet werden sollen, gib -1 ein',
            'optionscode' => 'text',
            'value' => '998, 999', // Default
            'disporder' => 4
        ),
        'blacklist_ice' => array(
            'title' => 'Auf Eis Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, ob der Charakter auf Eis ist. -1 bedeutet, dass du dieses Profilfeld nicht nutzt',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 5
        ),
        'blacklist_player' => array(
            'title' => 'Spieler Profilfeld',
            'description' => 'Gib hier die ID von deinem Profilfeld ein, wo man den Spielernamen einträgt',
            'optionscode' => 'text',
            'value' => '-1', // Default
            'disporder' => 6
        ),
        'blacklist_inplay' => array(
            'title' => 'Inplaykategorie',
            'description' => 'Wähle deine Inplaybereiche aus',
            'optionscode' => 'forumselect',
            'value' => '-1', // Default
            'disporder' =>7
        ),
        'blacklist_archive' => array(
            'title' => 'Archivkategorie',
            'description' => 'Wähle deine Archivkategorie aus.',
            'optionscode' => 'forumselectsingle',
            'value' => '0', // Default
            'disporder' => 8
        ),
        'blacklist_echo' => array(
            'title' => 'Rückmeldezeitraum',
            'description' => 'Bis zu welchen Tag darf man sich zurückmelden? (Hinweis: bis zu diesem Tag wird auch der Hinweis auf dem Index angezeigt)',
            'optionscode' => 'text',
            'value' => '7', // Default
            'disporder' => 9
        ),
    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    //Template blacklist bauen
    $insert_array = array(
        'title'        => 'blacklist',
        'template'    => $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <title>Blacklist</title>
        {$headerinclude}
        </head>
        <body>
        {$header}
        <div class="panel" id="panel">
        <div id="panel">$menu</div>
            {$banner}
            <h1>Blacklist vom 01.{$thisMonth}</h1>
            
        <table style="width:95%; margin:auto;">
            <tr>
                <td width="33%" class="thead">Blacklist</td>
                <td width="33%" class="thead">Abwesend</td>
            </tr>
            <tr>
                <td valign="top">{$userBlack}</td>
                <td valign="top">{$away} </td>
            </tr>
        </table>
            
        </div>
        {$footer}
        </body>
        </html>
        <script>
	   $(\'.delete\').click(function () {
        if (confirm("Möchtest du diesen User streichen?")) {
            $.post("blacklist.php", {
                id: this.id,
                action: \'delete\'
            })
            .done(function() {
            location.reload(); 
            })
        }
    });
    </script>
    <style>
        .buttonBlack{
            cursor: pointer;
            padding-left: 3px;
        }
    </style>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template blacklistIce bauen
    $insert_array = array(
        'title'        => 'blacklistIce',
        'template'    => $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <title>Blacklist</title>
        {$headerinclude}
        </head>
        <body>
        {$header}
        <div class="panel" id="panel">
        <div id="panel">$menu</div>
            {$banner}
            <h1>Blacklist vom 01.{$thisMonth}</h1>
            
        <table style="width:95%; margin:auto;">
            <tr>
                <td width="33%" class="thead">Blacklist</td>
                <td width="33%" class="thead">Abwesend</td>
                <td width="33%" class="thead">Auf Eis</td>
            </tr>
            <tr>
                <td valign="top">{$userBlack}</td>
                <td valign="top">{$away} </td>
                <td valign="top">{$onIce} </td> 
            </tr>
        </table>
            
        </div>
        {$footer}
        </body>
        </html>
        <script>
	   $(\'.delete\').click(function () {
        if (confirm("Möchtest du diesen User streichen?")) {
            $.post("blacklist.php", {
                id: this.id,
                action: \'delete\'
            })
            .done(function() {
            location.reload(); 
            })
        }
    });
    </script>
    <style>
        .buttonBlack{
            cursor: pointer;
            padding-left: 3px;
        }
    </style>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template blacklistUser bauen
    $insert_array = array(
        'title'        => 'blacklistUser',
        'template'    => $db->escape_string('$username $deleteButton<br/>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template blacklistUserGestrichen bauen
    $insert_array = array(
        'title'        => 'blacklistUserAnnulled',
        'template'    => $db->escape_string('<s>$username</s><br/>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template blacklistHeader bauen
    $insert_array = array(
        'title'        => 'blacklistHeader',
        'template'    => $db->escape_string('<div class="pm_alert">Die aktuelle <a href="/blacklist.php">Blacklist</a> ist draußen. Von dir befinden sich <b>keine Charaktere</b> auf dieser. <a href="/blacklist.php?seen=1" title="Nicht mehr anzeigen"><span style="font-size: 14px;margin-top: -2px;float:right;">✕</span></a></div>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //Template blacklistHeaderChara bauen
    $insert_array = array(
        'title'        => 'blacklistHeaderChara',
        'template'    => $db->escape_string('<div class="pm_alert">Die aktuelle <a href="/blacklist.php">Blacklist</a> ist draußen. Von dir stehen auf der Blacklist: <b> {$charanames} </b> <a href="/blacklist.php?seen=1" title="Nicht mehr anzeigen"><span style="font-size: 14px;margin-top: -2px;float:right;">✕</span></a></div>'),
        'sid'        => '-1',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    rebuild_settings();
}

function blacklist_is_installed()
{
    global $db;
    return $db->field_exists('hasSeenBlacklist', 'users') ? true : false;
}

function blacklist_uninstall()
{
    global $db;
    $db->delete_query('settings', "name LIKE 'blacklist_%'");
    $db->delete_query('settinggroups', "name = 'blacklist'");
    $db->delete_query("templates", "title LIKE 'blacklist_%'");
    $db->drop_column('users', 'isOnBlacklist');
    $db->drop_column('users', 'isOnBlacklistAnnulled');
    $db->drop_column('users', 'hasSeenBlacklist');

    // task
    $db->delete_query('tasks', 'title = "Blacklist Reset"');

    rebuild_settings();
}

function blacklist_activate()
{
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$awaitingusers}') . "#i", '{$awaitingusers} {$header_blacklist}');
}

function blacklist_deactivate()
{
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$header_blacklist}') . "#i", '', 0);
}

//Benachrichtung bei Blacklist
$plugins->add_hook('global_intermediate', 'blacklist_alert');
function blacklist_alert()
{
    global $mybb, $templates, $header_blacklist;
    require_once "inc/datahandlers/blacklist.php";

    $alertDays = intval($mybb->settings['blacklist_echo']);
    $blacklistHandler = new blacklistHandler($mybb->user['uid']);

    // als gesehen markieren
    if ($_GET['seen'] == 1) $blacklistHandler->markAsSeen();
    if($mybb->user['hasSeenBlacklist'] == 1) return;

    $header_blacklist = '';
    $charanames = $blacklistHandler->getOwnBlacklistCharas();

    if (date("j", time()) <= $alertDays && $alertDays != -1 && $mybb->user['uid'] != 0 && !$dontSee) {
        if ($charanames == '') {
            eval("\$header_blacklist .= \"" . $templates->get("blacklistHeader") . "\";");
        } else {
            eval("\$header_blacklist .= \"" . $templates->get("blacklistHeaderChara") . "\";");
        }
    }
}