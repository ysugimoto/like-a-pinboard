<?php

class RecentController extends SZ_Breeder
{
    public function __construct()
    {
        parent::__construct();
        $this->import->model("PinModel");

        $this->view->assign("title", "Recent");
    }

    public function pins()
    {
        $pins = $this->pinModel->getRecentPins();
        $tags = $this->pinModel->getRecentTags();

        $this->view->assign("pins", $pins);
        $this->view->assign("tags", $tags);
    }
}

