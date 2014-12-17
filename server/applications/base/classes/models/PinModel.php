<?php

class PinModel extends SZ_Kennel
{
    protected $db;

    protected $table = "pb_urls";
    protected $tag   = "pb_tags";

    public function getRecentPins($limit = 20, $offset = 0)
    {
        $userModel = Seezoo::$Importer->model("UserModel");
        $records   = array();

        $sql = "SELECT "
                .   "id, "
                .   "title, "
                .   "url, "
                .   "readed "
                . "FROM "
                .   $this->table . " "
                . "WHERE "
                .   "user_id = ? "
                . "ORDER BY id DESC "
                . "LIMIT ? "
                . "OFFSET ? "
                ;

        $query = $this->db->query($sql, array($userModel->getUserID(), (int)$limit, (int)$offset));
        if ( $query )
        {
            $records = array_map(function($row) {
                $row->tags = $this->getTagsByID($row->id);
                return $row;
            }, $query->result());
        }

        return $records;
    }

    public function getTagsByID($urlID)
    {
        $sql = "SELECT "
                .   "name "
                . "FROM "
                .   $this->tag . " "
                . "WHERE "
                .   "url_id = ?"
                ;

        $query = $this->db->query($sql, array((int)$urlID));
        return ( $query->numRows() > 0 ) ? $query->result() : array();
    }

    public function getRecentTags()
    {
        $userModel = Seezoo::$Importer->model("UserModel");

        $sql = "SELECT "
                .   "(SELECT "
                .       "COUNT(name) "
                .   "FROM "
                .       $this->tag . " as CT "
                .   "WHERE "
                .       "CT.name = T.name "
                .   "LIMIT 1) as cnt, "
                .   "T.name "
                . "FROM "
                .   $this->tag . " as T "
                . "JOIN " . $this->table . " as P ON ( "
                .   "T.url_id = P.id "
                . ") "
                . "WHERE "
                .   "P.user_id = ? "
                . "GROUP BY T.name "
                . "ORDER BY cnt DESC"
                ;

        $query = $this->db->query($sql, array($userModel->getUserID()));
        return $query->result();
    }

}

