<?php

class UserModel extends SZ_Kennel
{
    /**
     * Autoload signature property
     */
    protected $db;

    /**
     * Model using table name
     */
    protected $table    = "pb_users";
    protected $facebook = "pb_facebook_account";
    protected $github   = "pb_github_account";
    protected $twitter  = "pb_twitter_account";

    /**
     * Get user ID
     *
     * @public
     * @return int
     */
    public function getUserID()
    {
        $sess = Seezoo::$Importer->library("session");
        return (int)$sess->get("login_id");
    }

    /**
     * Check user logged in
     *
     * @public
     * @return bool
     */
    public function isLoggedIn()
    {
        return ( $this->getUserID() > 0 ) ? TRUE : FALSE;
    }

    /**
     * Log out
     *
     * @public
     * @return void
     */
    public function logout()
    {
        $sess = Seezoo::$Importer->library("session");
        $sess->remove("login_id");
    }

    /**
     * Get user info from ID
     *
     * @public
     * @param int $userID
     * @return mixed
     */
    public function getUserByID($userID = 0)
    {
        $sql   = $this->_genUserFindSQL();
        $sql  .= "WHERE U.id = ? LIMIT 1 ";
        $query = $this->db->query($sql, array((int)$userID));

        return  ( $query->row() ) ? $query->row() : FALSE;
    }

    /**
     * Get user info from geneated token
     *
     * @public
     * @param string $token
     * @return mixed
     */
    public function getUserByToken($token)
    {
        $sql   = $this->_genUserFindSQL();
        $sql  .= "WHERE U.token = ? LIMIT 1 ";
        $query = $this->db->query($sql, array($token));

        return  ( $query->row() ) ? $query->row() : FALSE;
    }

    /**
     * Create getter base SQL
     *
     * @protected
     * @return string $sql
     */
    protected function _genUserFindSQL()
    {
        $sql = "SELECT "
               .    "U.*, "
               .    "F.facebook_id, "
               .    "G.github_id, "
               .    "T.twitter_id "
               ."FROM "
               .    $this->table . " as U "
               ."LEFT OUTER JOIN " . $this->facebook . " as F ON ( "
               .    "U.id = F.user_id "
               .") "
               ."LEFT OUTER JOIN " . $this->github . " as G ON ( "
               .    "U.id = G.user_id "
               .") "
               ."LEFT OUTER JOIN " . $this->twitter . " as T ON ( "
               .    "U.id = T.user_id "
               .") "
                ;

        return $sql;
    }

    /**
     * Create new user
     *
     * @public
     * @param array $user
     * @return int $userID
     */
    public function createUser($user)
    {
        $date = new DateTime();

        // Insert main user table
        $user["created_at"] = $date->format("Y-m-d H:i:s");
        $user["token"]      = $this->_genToken();
        $user["last_login"] = $date->format("Y-m-d H:i:s");

        return $this->db->insert($this->table, $user, TRUE);
    }

    /**
     * Update last login
     *
     * @public
     * @param int $userID
     */
    public function updateLastLogin($userID)
    {
        $date = new DateTime();

        $user["last_login"] = $date->format("Y-m-d H:i:s");

        $this->db->update($this->table, $user, array("id" => (int)$userID));
    }

    /**
     * Generate token
     *
     * @public
     * @return string
     */
    public function _genToken()
    {
        // TODO: need to create more safety token?
        return sha1(uniqid(mt_rand(), TRUE));
    }

    /**
     * Register user with facebook authenticate
     *
     * @public
     * @param int    $facebookID
     * @param string $facebookName
     * @param string $facebookAuthToken
     * @return int
     */
    public function registerWithFacebook($facebookID, $facebookName, $facebookAuthToken)
    {
        $this->db->transaction();

        // Does User already registered?
        $sql = $this->_genUserFindSQL();
        $sql .= "WHERE U.facebook_access_token = ? LIMIT 1";

        $query = $this->db->query($sql, array($facebookAuthToken));
        if ( $query->row() )
        {
            $id = $query->row()->id;
            $this->updateLastLogin($id);
            $this->db->commit();
            return $id;
        }

        $userID = $this->createUser(array(
            "name"                  => $facebookName,
            "facebook_access_token" => $facebookAuthToken
        ));

        if ( ! $userID )
        {
            $this->db->rollback();
            return 0;
        }

        // Insert relation table
        $data = array(
            "user_id"       => $userID,
            "facebook_id"   => $facebookID,
            "facebook_name" => $facebookName,
            "access_token"  => $facebookAuthToken
        );

        if ( $this->db->insert($this->facebook, $data) )
        {
            $this->db->commit();
            return $userID;
        }

        $this->db->rollback();
        return 0;
    }

    /**
     * Register user with github authenticate
     *
     * @public
     * @param int    $githubID
     * @param string $githubName
     * @param string $githubAuthToken
     * @return int
     */
    public function registerWithGithub($githubID, $githubName, $githubAuthToken)
    {
        $this->db->transaction();

        // Does User already registered?
        $sql = $this->_genUserFindSQL();
        $sql .= "WHERE U.github_access_token = ? LIMIT 1";

        $query = $this->db->query($sql, array($githubAuthToken));
        if ( $query->row() )
        {
            $id = $query->row()->id;
            $this->updateLastLogin($id);
            $this->db->commit();
            return $id;
        }

        $userID = $this->createUser(array(
            "name"                => $githubName,
            "github_access_token" => $githubAuthToken
        ));

        if ( ! $userID )
        {
            $this->db->rollback();
            return 0;
        }

        // Insert relation table
        $data = array(
            "user_id"      => $userID,
            "github_id"    => $githubID,
            "github_name"  => $githubName,
            "access_token" => $githubAuthToken
        );

        if ( $this->db->insert($this->github, $data) )
        {
            $this->db->commit();
            return $userID;
        }

        $this->db->rollback();
        return 0;
    }

    /**
     * Register user with twitter authenticate
     *
     * @public
     * @param int    $twitterID
     * @param string $twitterName
     * @param string $twitterAuthToken
     * @return int
     */
    public function registerWithTwitter($twitterID, $twitterName, $twitterAuthToken)
    {
        $this->db->transaction();

        // Does User already registered?
        $sql = $this->_genUserFindSQL();
        $sql .= "WHERE U.twitter_access_token = ? LIMIT 1";

        $query = $this->db->query($sql, array($twitterAuthToken));
        if ( $query->row() )
        {
            $id = $query->row()->id;
            $this->updateLastLogin($id);
            $this->db->commit();
            return $id;
        }

        $userID = $this->createUser(array(
            "name"                 => $twitterName,
            "twitter_access_token" => $twitterAuthToken
        ));

        if ( ! $userID )
        {
            $this->db->rollback();
            return 0;
        }

        // Insert relation table
        $data = array(
            "user_id"      => $userID,
            "twitter_id"   => $twitterID,
            "twitter_name" => $twitterName,
            "access_token" => $twitterAuthToken
        );

        if ( $this->db->insert($this->twitter, $data) )
        {
            $this->db->commit();
            return $userID;
        }

        $this->db->rollback();
        return 0;
    }
}
