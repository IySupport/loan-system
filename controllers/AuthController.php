<?php

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', ['error' => null], null);
    }

    public function login(): void
    {
        $username = trim($this->input('username', ''));
        $password = (string) $this->input('password', '');

        if ($username === '' || $password === '') {
            $this->view('auth/login', ['error' => 'Please enter username and password.'], null);
            return;
        }

        if (Auth::attempt($username, $password)) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth/login', ['error' => 'Invalid username or password.'], null);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
