<?php

class SigninController extends SZ_Breeder
{
    public function __construct()
    {
        parent::__construct();
        $this->import->library("oauth");
        $this->import->library("session");
    }

    public function index()
    {
    }

    public function github()
    {
        $this->oauth->service("github", array(
            "client_id"          => $this->env->getConfig("github_client_id"),
            "application_secret" => $this->env->getConfig("github_application_secret"),
            "callback_url"       => page_link("signin/github_callback")
        ));

        if ( $this->oauth->auth2() )
        {
            echo "Authorized.";
            return Signal::finished;
        }

        return Signal::failed;
    }

    public function github_callback()
    {
        $this->oauth->service("github", array(
            "client_id"          => $this->env->getConfig("github_client_id"),
            "application_secret" => $this->env->getConfig("github_application_secret"),
            "callback_url"       => page_link("signin/github_callback")
        ));

        if ( $this->oauth->auth2() )
        {
            $user = $this->oauth->getUser();
            if ( $user )
            {
                // TODO: save user
                return Signal::finished;
            }
        }

        $this->session->setFlash('oauth_error', 1);
        return $this->response->redirect("index");
    }


}
