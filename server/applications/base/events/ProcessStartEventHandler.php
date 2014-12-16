<?php

class ProcessStartEventHandler
{
    private $whiteListSegment = array(
        "signin"
    );

    public function checkLogin()
    {
        $session = Seezoo::$Importer->library("session");
        if ( ! $session->get("login_id") )
        {
            $segment = Seezoo::getRequest()->segment(1);
            if ( ! in_array($segment, $this->whiteListSegment) )
            {
                $buffer = Application::fork(SZ_MODE_MVC, "signin");
                exit($buffer);
            }
        }
    }
}
