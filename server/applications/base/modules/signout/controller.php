<?php

class SignoutController extends SZ_Breeder
{
    public function __construct()
    {
        parent::__construct();

        $this->import->model("UserModel");
    }

    public function index()
    {
        $this->userModel->logout();

        return $this->response->redirect("signin");
    }
}
