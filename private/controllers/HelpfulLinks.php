<?php

class HelpfulLinks
{

    public static function getApprovedHelpfulLinks()
    {
        return Database::getAll(table: 'helpful_links', where: ['approved' => 1]);
    }

    public static function getUnapprovedHelpfulLinks()
    {
        return Database::getAll(table: 'helpful_links', where: ['approved' => 0]);
    }

    public static function suggestLink($name, $url, $description)
    {
        Database::insert(table: 'helpful_links', columns: ['name', 'url', 'description'], values: [$name, $url, $description]);
    }

    public static function approveLink($linkId)
    {
        return Database::update(table: 'helpful_links', columns: ['approved'], values: [1], where: ['id' => $linkId]);
    }

    public static function disapproveLink($linkId)
    {
        return Database::delete(table: 'helpful_links', where: ['id' => $linkId]);
    }

}