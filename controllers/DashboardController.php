<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $this->render('backend/layout', ['content' => null]);
    }
}