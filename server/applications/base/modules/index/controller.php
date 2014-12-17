<?php

class IndexController extends SZ_Breeder
{
    public function __construct()
    {
        parent::__construct();

        $this->import->model("UserModel");
        $this->import->library("Session");

        $this->view->assign("title", "Like A Pinboard");
    }

    public function index()
    {
        $id   = $this->session->get("login_id");
        $user = $this->userModel->getUserByID($id);

        $auth = $this->session->getFlash("oauth_error");

        $this->view->assign("user", $user);
        $this->view->assign("auth_result", $auth);
    }
}

