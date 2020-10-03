<?php

if (!defined("IN_MYBB")) die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
global $plugins;

class BlacklistHandler
{
    private $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     *
     * @return array with all UID from one User
     */
    public function getUidArrayFromAllCharacters()
    {
        global $db;
        $uids = array();
        $user = get_user($this->uid);
        if ($user['as_uid'] != 0) $this->uid = $user['as_uid'];
        $mainUid = $this->uid;
        array_push($uids, $mainUid);

        $query = $db->simple_select('users', 'uid', 'as_uid = ' . $mainUid);
        while ($result = $db->fetch_array($query)) {
            array_push($uids, (int)$result['uid']);
        }
        return $uids;
    }

    /**
     *
     * @return string with all UID from one User
     */
    public function getUidSetFromAllCharacters()
    {
        return implode(',', $this->getUidArrayFromAllCharacters());
    }

    /**
     *
     * @return string with all usernames from own blacklist characters
     */
    public function getOwnBlacklistCharas()
    {
        global $db, $mybb;
        $dontShowApplicants = $mybb->settings['blacklist_applicant'] == '0' ? 'and usergroup != '. $mybb->settings['blacklist_applicant_group']  : '';

        $charas = $db->simple_select('users', 'username, uid', 'find_in_set(uid, "' . $this->getUidSetFromAllCharacters() . '") AND isOnBlacklist = 1 AND isOnBlacklistAnnulled = 0 AND away = 0 ' . $dontShowApplicants, array('order_by' => 'username'));
        $invisibleAccounts = explode(", ", $db->escape_string($mybb->settings['blacklist_teamaccs']));
        $blacklistCharas = array();
        while ($chara = $db->fetch_array($charas)) {
            if (!is_numeric(array_search($chara['uid'], $invisibleAccounts)))
                array_push($blacklistCharas, $chara['username']);
        }
        return implode(', ', $blacklistCharas);
    }

    /**
     *
     * @return array with all usernames from all blacklist characters
     */
    public function getAllBlacklistCharas()
    {
        global $db, $mybb;
        $fidsInplay = $mybb->settings['blacklist_inplay'];
        $fidArchive = intval($mybb->settings['blacklist_archive']);

        $allUsers = array();
        $query = $db->simple_select('users', 'uid');
        while ($uid = $db->fetch_array($query)) {
            array_push($allUsers, $uid['uid']);
        }

        $notOnBlacklist = array();
        //Kontrolle, ob Post in diesem Monat oder letzten Monat => nicht Blacklist
        $lastIPPosts = $db->query("SELECT uid, dateline
        FROM " . TABLE_PREFIX . "posts p JOIN " . TABLE_PREFIX . "forums f ON f.fid = p.fid
        WHERE find_in_set(f.fid, '" . $fidsInplay . "') or find_in_set(" . $fidArchive . ", parentlist)");
        while ($lastIPPost = $db->fetch_array($lastIPPosts)) {
            if (!$this->isOlderThanOneMonth($lastIPPost['dateline']) && !$this->isInThisMonth($lastIPPost['dateline'])) {
                array_push($notOnBlacklist, $lastIPPost['uid']);
            }
        }

        //Differnez von allen User und denen, die nicht auf der BL sind
        $blacklistUsers = array_diff($allUsers, $notOnBlacklist);
        foreach ($blacklistUsers as $user) $db->update_query('users', array('isOnBlacklist' => 1), 'uid =' . $user);
        return $blacklistUsers;
    }

    public function getAllAnnulledCharas()
    {
        global $db, $mybb;
        $fidsInplay = $mybb->settings['blacklist_inplay'];
        $fidArchive = intval($mybb->settings['blacklist_archive']);

        $blacklistUsersAnnulled = array();
        $lastIPPosts2 = $db->query("SELECT u.uid, dateline, isOnBlacklistAnnulled
        FROM " . TABLE_PREFIX . "users u 
        JOIN " . TABLE_PREFIX . "posts p ON p.uid = u.uid
        JOIN " . TABLE_PREFIX . "forums f ON f.fid = p.fid
        WHERE isOnBlacklistAnnulled = 1 OR (isOnBlacklist = 1 AND (find_in_set(f.fid, '" . $fidsInplay . "') or find_in_set(" . $fidArchive . ", parentlist)))");
        while ($lastIPPost = $db->fetch_array($lastIPPosts2)) {
            if (!in_array($lastIPPost['uid'], $blacklistUsersAnnulled) && ($this->isInThisMonth($lastIPPost['dateline']) || $lastIPPost['isOnBlacklistAnnulled'] == '1'))
                array_push($blacklistUsersAnnulled, $lastIPPost['uid']);
        }

        foreach ($blacklistUsersAnnulled as $uid) $this->markAsAnnulled($uid);

        return $blacklistUsersAnnulled;
    }

    public function markAsSeen()
    {
        global $db;
        $update = array('hasSeenBlacklist' => 1);
        $uids = $this->getUidSetFromAllCharacters($this->uid);
        $db->update_query('users', $update, 'find_in_set(uid, "' . $uids . '") or find_in_set(as_uid, "' . $uids . '")');
    }

    public function markAsAnnulled($uid)
    {
        global $db;
        $update = array('isOnBlacklistAnnulled' => 1);
        $db->update_query('users', $update, 'uid = ' . $uid);
    }

    // helper functions
    private function isOlderThanOneMonth($dateToTest)
    {
        $oneMonthAgo = strtotime("-1 month");
        $firstOneMonthAgo = "01." . date("m.Y", $oneMonthAgo);

        $UnixFirstOneMonthAgo = strtotime($firstOneMonthAgo);
        return $dateToTest > $UnixFirstOneMonthAgo ? false : true;
    }

    private function isInThisMonth($dateToTest)
    {
        $thisMonth = date('m.Y', time());
        $monthDateToTest = date('m.Y', $dateToTest);
        return $monthDateToTest == $thisMonth;
    }
}
