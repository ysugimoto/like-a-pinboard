<?php

class DownloadController extends SZ_Breeder
{
    public function __construct()
    {
        parent::__construct();
        $this->import->library("Session");
        $this->import->model("UserModel");
    }

    public function index()
    {
        $id   = $this->session->get("login_id");
        $user = $this->userModel->getUserByID($id);

        $rcFile  = "{\n";
        $rcFile .= "  \"url\":\"" . page_link() . "\",\n";
        $rcFile .= "  \"token\":\"" . $user->token . "\"\n";
        $rcFile .= "}";

        return $this->response->download($rcFile, "user.laprc", TRUE);
    }
}


